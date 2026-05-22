<?php

declare(strict_types=1);

namespace App\Tests\Functional\Identity\Infrastructure\Controller;

use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

/**
 * Функциональные тесты контроллера удаления пользователя.
 * Проверяет мягкое удаление, 404 и 422 сценарии с реальной БД.
 *
 * @internal
 *
 * @coversNothing
 */
class DeleteUserControllerTest extends FunctionalTestCase
{
    #[Test]
    public function returnsNoContentOnSuccess(): void
    {
        $userId = $this->createUser('delete-user@example.com');

        $this->authenticatedRequest('DELETE', '/api/identity/users/' . $userId);

        self::assertSame(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsNotFoundWhenUserDoesNotExist(): void
    {
        $this->authenticatedRequest('DELETE', '/api/identity/users/f0000000-0000-4000-a000-000000000001');

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertSame('Пользователь не найден.', $body['error']);
    }

    #[Test]
    public function returnsUnprocessableWhenUserAlreadyDeleted(): void
    {
        $userId = $this->createUser('delete-user@example.com');

        // Удаляем пользователя первый раз
        $this->authenticatedRequest('DELETE', '/api/identity/users/' . $userId);
        self::assertSame(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());

        // Повторная попытка удаления — пользователь уже удалён
        $this->authenticatedRequest('DELETE', '/api/identity/users/' . $userId);
        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsUnauthorizedWithoutToken(): void
    {
        $userId = $this->createUser('delete-user@example.com');

        $this->client->request('DELETE', '/api/identity/users/' . $userId);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
