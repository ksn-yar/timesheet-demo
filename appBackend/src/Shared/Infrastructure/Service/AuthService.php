<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Service;

use App\Persistence\Entity\User;
use App\Persistence\Entity\UserRefreshToken;
use App\Shared\Infrastructure\Exception\AuthException;
use Exception;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class AuthService
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
     * Аутентифицирует пользователя, возвращает access token, refresh token и данные пользователя.
     *
     * @return array{token: string, refresh_token: string, user: array{id: mixed, email: mixed, name: mixed, systemRole: mixed}}
     *
     * @throws AuthException
     */
    public function login(string $email, string $password): array
    {
        try {
            /** @var User $user */
            $user = $this->userProvider->loadUserByIdentifier($email);
        } catch (Exception) {
            // Намеренно возвращаем одинаковое сообщение — не раскрываем информацию о существовании аккаунта
            throw new AuthException('Неверный email или пароль.');
        }

        if (!$this->passwordHasher->isPasswordValid($user, $password)) {
            throw new AuthException('Неверный email или пароль.');
        }

        $accessToken = $this->jwtTokenManager->create($user);
        $refreshToken = $this->refreshTokenGenerator->createForUserWithTtl($user, $this->refreshTokenTtl);
        \assert($refreshToken instanceof UserRefreshToken);
        $refreshToken->setUser($user);
        $this->refreshTokenManager->save($refreshToken);

        return [
            'token' => $accessToken,
            'refresh_token' => $refreshToken->getRefreshToken(),
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'systemRole' => $user->getSystemRole(),
            ],
        ];
    }

    /**
     * Обновляет пару токенов. Удаляет старый refresh token (single_use).
     *
     * @return array{token: string, refresh_token: string}
     *
     * @throws AuthException
     */
    public function refreshTokens(string $refreshTokenValue): array
    {
        $refreshToken = $this->refreshTokenManager->get($refreshTokenValue);

        if (null === $refreshToken) {
            throw new AuthException('Refresh token не найден.');
        }

        if (!$refreshToken->isValid()) {
            // Удаляем просроченный токен из БД при попытке его использования
            $this->refreshTokenManager->delete($refreshToken);

            throw new AuthException('Refresh token истёк.');
        }

        try {
            /** @var User $user */
            $user = $this->userProvider->loadUserByIdentifier((string) $refreshToken->getUsername());
        } catch (Exception) {
            $this->refreshTokenManager->delete($refreshToken);

            throw new AuthException('Пользователь не найден.');
        }

        $this->refreshTokenManager->delete($refreshToken);

        $newAccessToken = $this->jwtTokenManager->create($user);
        $newRefreshToken = $this->refreshTokenGenerator->createForUserWithTtl($user, $this->refreshTokenTtl);
        \assert($newRefreshToken instanceof UserRefreshToken);
        $newRefreshToken->setUser($user);
        $this->refreshTokenManager->save($newRefreshToken);

        return [
            'token' => $newAccessToken,
            'refresh_token' => $newRefreshToken->getRefreshToken(),
        ];
    }

    /**
     * Отзывает refresh token. Идемпотентен — не выбрасывает исключение, если токен уже удалён.
     */
    public function revokeRefreshToken(string $refreshTokenValue): void
    {
        $refreshToken = $this->refreshTokenManager->get($refreshTokenValue);

        if (null === $refreshToken) {
            return;
        }

        $this->refreshTokenManager->delete($refreshToken);
    }
}
