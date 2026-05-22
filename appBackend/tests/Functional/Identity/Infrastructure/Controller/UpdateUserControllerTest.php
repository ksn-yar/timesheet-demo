<?php

declare(strict_types=1);

namespace App\Tests\Functional\Identity\Infrastructure\Controller;

use App\Persistence\Entity\User as UserOrmEntity;
use App\Tests\Functional\FunctionalTestCase;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

/**
 * Функциональные тесты контроллера обновления пользователя.
 * Проверяет полный стек: валидацию DTO, 404 и 422 сценарии, аутентификацию.
 *
 * @internal
 *
 * @coversNothing
 */
class UpdateUserControllerTest extends FunctionalTestCase
{
    #[Test]
    public function returnsNoContentOnSuccess(): void
    {
        $userId = $this->createUser('update-user@example.com');

        $this->authenticatedRequest('PUT', '/api/identity/users/' . $userId, [
            'name' => 'Обновлённое Имя',
            'systemRole' => 'employee',
        ]);

        self::assertSame(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsBadRequestWhenNameIsBlankString(): void
    {
        $userId = $this->createUser('update-user@example.com');

        $this->authenticatedRequest('PUT', '/api/identity/users/' . $userId, [
            'name' => '',
            'systemRole' => 'employee',
        ]);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsBadRequestWhenSystemRoleInvalid(): void
    {
        $userId = $this->createUser('update-user@example.com');

        $this->authenticatedRequest('PUT', '/api/identity/users/' . $userId, [
            'name' => 'Имя Пользователя',
            'systemRole' => 'unknown-role',
        ]);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsNotFoundWhenUserDoesNotExist(): void
    {
        $this->authenticatedRequest('PUT', '/api/identity/users/f0000000-0000-4000-a000-000000000001', [
            'name' => 'Обновлённое Имя',
            'systemRole' => 'employee',
        ]);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertSame('Пользователь не найден.', $body['error']);
    }

    #[Test]
    public function returnsUnprocessableWhenUserDeleted(): void
    {
        $userId = $this->createUser('update-user@example.com');

        $em = $this->getEntityManager();
        $user = $em->find(UserOrmEntity::class, $userId);
        \assert(null !== $user);
        $user->setDeletedAt(new DateTimeImmutable());
        $em->flush();

        $this->authenticatedRequest('PUT', '/api/identity/users/' . $userId, [
            'name' => 'Обновлённое Имя',
            'systemRole' => 'employee',
        ]);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsUnauthorizedWithoutToken(): void
    {
        $userId = $this->createUser('update-user@example.com');

        $this->client->request('PUT', '/api/identity/users/' . $userId, [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['name' => 'Обновлённое Имя', 'systemRole' => 'employee'], \JSON_THROW_ON_ERROR));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
