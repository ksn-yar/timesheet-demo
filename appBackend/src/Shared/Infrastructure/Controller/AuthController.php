<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Controller;

use App\Shared\Infrastructure\Exception\AuthException;
use App\Shared\Infrastructure\Service\AuthService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** Контроллер аутентификации. Обрабатывает вход, обновление и отзыв JWT-токенов. */
#[OA\Tag(name: 'Auth')]
final class AuthController extends AbstractController
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    #[OA\Post(
        path: '/api/auth/login',
        description: 'Аутентифицирует пользователя по email и паролю. Возвращает JWT access token, refresh token и данные пользователя.',
        summary: 'Войти в систему',
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['email', 'password'],
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                new OA\Property(property: 'password', type: 'string', format: 'password', example: 'secret'),
            ],
        ),
    )]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Успешная аутентификация.',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'token', description: 'JWT access token.', type: 'string'),
                new OA\Property(property: 'refresh_token', type: 'string'),
                new OA\Property(
                    property: 'user',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'email', type: 'string', format: 'email'),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'systemRole', type: 'string'),
                    ],
                    type: 'object',
                ),
            ],
        ),
    )]
    #[OA\Response(
        response: Response::HTTP_BAD_REQUEST,
        description: 'Не указан email или пароль.',
    )]
    #[OA\Response(
        response: Response::HTTP_UNAUTHORIZED,
        description: 'Неверный email или пароль.',
    )]
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
            $result = $this->authService->login($email, $password);
        } catch (AuthException $e) {
            return new JsonResponse(
                ['error' => $e->getMessage()],
                Response::HTTP_UNAUTHORIZED,
            );
        }

        return new JsonResponse($result);
    }

    #[OA\Post(
        path: '/api/auth/refresh',
        description: 'Выдаёт новую пару access/refresh токенов по действующему refresh token. Старый refresh token инвалидируется (single use).',
        summary: 'Обновить токены',
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['refresh_token'],
            properties: [
                new OA\Property(property: 'refresh_token', type: 'string'),
            ],
        ),
    )]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Токены успешно обновлены.',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'token', description: 'Новый JWT access token.', type: 'string'),
                new OA\Property(property: 'refresh_token', description: 'Новый refresh token.', type: 'string'),
            ],
        ),
    )]
    #[OA\Response(
        response: Response::HTTP_BAD_REQUEST,
        description: 'Не передан refresh_token.',
    )]
    #[OA\Response(
        response: Response::HTTP_UNAUTHORIZED,
        description: 'Refresh token не найден, истёк или пользователь не найден.',
    )]
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

        try {
            $result = $this->authService->refreshTokens($refreshTokenValue);
        } catch (AuthException $e) {
            return new JsonResponse(
                ['error' => $e->getMessage()],
                Response::HTTP_UNAUTHORIZED,
            );
        }

        return new JsonResponse($result);
    }

    #[OA\Post(
        path: '/api/auth/logout',
        description: 'Отзывает refresh token. Идемпотентен — если токен уже удалён, возвращает 204. Требует валидного JWT access token в заголовке Authorization.',
        summary: 'Выйти из системы',
        security: [['Bearer' => []]],
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['refresh_token'],
            properties: [
                new OA\Property(property: 'refresh_token', type: 'string'),
            ],
        ),
    )]
    #[OA\Response(
        response: Response::HTTP_NO_CONTENT,
        description: 'Выход выполнен успешно.',
    )]
    #[OA\Response(
        response: Response::HTTP_BAD_REQUEST,
        description: 'Не передан refresh_token.',
    )]
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

        $this->authService->revokeRefreshToken($refreshTokenValue);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
