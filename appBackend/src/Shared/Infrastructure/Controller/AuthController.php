<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Controller;

use App\Persistence\Entity\User;
use App\Persistence\Entity\UserRefreshToken;
use Exception;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Контроллер аутентификации. Обрабатывает вход, обновление и отзыв JWT-токенов
 * в рамках stateless API.
 */
final class AuthController extends AbstractController
{
    public function __construct(
        /** @var UserProviderInterface<User> */
        private readonly UserProviderInterface $userProvider,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly JWTTokenManagerInterface $jwtTokenManager,
        private readonly RefreshTokenGeneratorInterface $refreshTokenGenerator,
        private readonly RefreshTokenManagerInterface $refreshTokenManager,
        #[Autowire('%env(int:JWT_REFRESH_TOKEN_TTL)%')]
        private readonly int $refreshTokenTtl,
    ) {}

    /**
     * Аутентифицирует пользователя по email и паролю.
     * Возвращает JWT access token, refresh token и данные пользователя.
     */
    #[Route('/api/auth/login', name: 'api_auth_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = $request->toArray();

        $email = trim((string) ($data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if ('' === $email || '' === $password) {
            return new JsonResponse(
                ['error' => 'Необходимо указать email и пароль.'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        try {
            /** @var User $user */
            $user = $this->userProvider->loadUserByIdentifier($email);
        } catch (Exception) {
            // Намеренно возвращаем одинаковое сообщение — не раскрываем информацию о существовании аккаунта
            return new JsonResponse(
                ['error' => 'Неверный email или пароль.'],
                Response::HTTP_UNAUTHORIZED,
            );
        }

        if (!$this->passwordHasher->isPasswordValid($user, $password)) {
            return new JsonResponse(
                ['error' => 'Неверный email или пароль.'],
                Response::HTTP_UNAUTHORIZED,
            );
        }

        $accessToken = $this->jwtTokenManager->create($user);
        $refreshToken = $this->refreshTokenGenerator->createForUserWithTtl($user, $this->refreshTokenTtl);
        \assert($refreshToken instanceof UserRefreshToken);
        $refreshToken->setUser($user);
        $this->refreshTokenManager->save($refreshToken);

        return new JsonResponse([
            'token' => $accessToken,
            'refresh_token' => $refreshToken->getRefreshToken(),
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'systemRole' => $user->getSystemRole(),
            ],
        ]);
    }

    /**
     * Обновляет пару токенов по действующему refresh token.
     * При single_use=true старый refresh token инвалидируется, выдаётся новый.
     */
    #[Route('/api/auth/refresh', name: 'api_auth_refresh', methods: ['POST'])]
    public function refresh(Request $request): JsonResponse
    {
        $data = $request->toArray();
        $refreshTokenValue = trim((string) ($data['refresh_token'] ?? ''));

        if ('' === $refreshTokenValue) {
            return new JsonResponse(
                ['error' => 'Необходимо передать refresh_token.'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $refreshToken = $this->refreshTokenManager->get($refreshTokenValue);

        if (null === $refreshToken) {
            return new JsonResponse(
                ['error' => 'Refresh token не найден.'],
                Response::HTTP_UNAUTHORIZED,
            );
        }

        if (!$refreshToken->isValid()) {
            // Удаляем просроченный токен из БД при попытке его использования
            $this->refreshTokenManager->delete($refreshToken);

            return new JsonResponse(
                ['error' => 'Refresh token истёк.'],
                Response::HTTP_UNAUTHORIZED,
            );
        }

        $username = $refreshToken->getUsername();

        try {
            /** @var User $user */
            $user = $this->userProvider->loadUserByIdentifier((string) $username);
        } catch (Exception) {
            $this->refreshTokenManager->delete($refreshToken);

            return new JsonResponse(
                ['error' => 'Пользователь не найден.'],
                Response::HTTP_UNAUTHORIZED,
            );
        }

        // Удаляем старый refresh token (single_use: логика инвалидации)
        $this->refreshTokenManager->delete($refreshToken);

        $newAccessToken = $this->jwtTokenManager->create($user);
        $newRefreshToken = $this->refreshTokenGenerator->createForUserWithTtl($user, $this->refreshTokenTtl);
        \assert($newRefreshToken instanceof UserRefreshToken);
        $newRefreshToken->setUser($user);
        $this->refreshTokenManager->save($newRefreshToken);

        return new JsonResponse([
            'token' => $newAccessToken,
            'refresh_token' => $newRefreshToken->getRefreshToken(),
        ]);
    }

    /**
     * Отзывает refresh token пользователя. Требует валидного access token в заголовке.
     */
    #[Route('/api/auth/logout', name: 'api_auth_logout', methods: ['POST'])]
    public function logout(Request $request): JsonResponse
    {
        $data = $request->toArray();
        $refreshTokenValue = trim((string) ($data['refresh_token'] ?? ''));

        if ('' === $refreshTokenValue) {
            return new JsonResponse(
                ['error' => 'Необходимо передать refresh_token.'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $refreshToken = $this->refreshTokenManager->get($refreshTokenValue);

        if (null === $refreshToken) {
            // Идемпотентное поведение — если токен уже удалён, считаем logout успешным
            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        $this->refreshTokenManager->delete($refreshToken);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
