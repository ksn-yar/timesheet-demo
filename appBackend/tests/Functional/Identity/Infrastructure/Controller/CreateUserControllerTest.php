<?php

declare(strict_types=1);

namespace App\Tests\Functional\Identity\Infrastructure\Controller;

use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

/**
 * Функциональные тесты контроллера создания пользователя.
 * Проверяет полный HTTP-стек: валидацию DTO, доменные правила и JWT-аутентификацию.
 *
 * @internal
 *
 * @coversNothing
 */
class CreateUserControllerTest extends FunctionalTestCase
{
    private const string URL = '/api/identity/users';

    #[Test]
    public function returnsCreatedOnSuccess(): void
    {
        $this->authenticatedRequest('POST', self::URL, [
            'name' => 'Пётр Петров',
            'email' => 'petr@example.com',
            'password' => 'secret123',
            'systemRole' => 'employee',
        ]);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsBadRequestWhenNameMissing(): void
    {
        $this->authenticatedRequest('POST', self::URL, [
            'email' => 'petr@example.com',
            'password' => 'secret123',
            'systemRole' => 'employee',
        ]);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsBadRequestWhenEmailInvalid(): void
    {
        $this->authenticatedRequest('POST', self::URL, [
            'name' => 'Пётр Петров',
            'email' => 'not-an-email',
            'password' => 'secret123',
            'systemRole' => 'employee',
        ]);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsBadRequestWhenPasswordTooShort(): void
    {
        $this->authenticatedRequest('POST', self::URL, [
            'name' => 'Пётр Петров',
            'email' => 'petr@example.com',
            'password' => '123',
            'systemRole' => 'employee',
        ]);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsBadRequestWhenSystemRoleInvalid(): void
    {
        $this->authenticatedRequest('POST', self::URL, [
            'name' => 'Пётр Петров',
            'email' => 'petr@example.com',
            'password' => 'secret123',
            'systemRole' => 'superuser',
        ]);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsUnprocessableWhenDuplicateEmail(): void
    {
        $this->createUser('petr@example.com', 'Пётр Существующий', 'employee', self::FIXTURE_USER_ID_2);

        $this->authenticatedRequest('POST', self::URL, [
            'name' => 'Пётр Петров',
            'email' => 'petr@example.com',
            'password' => 'secret123',
            'systemRole' => 'employee',
        ]);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsUnprocessableWhenGroupNotFound(): void
    {
        $this->authenticatedRequest('POST', self::URL, [
            'name' => 'Пётр Петров',
            'email' => 'petr@example.com',
            'password' => 'secret123',
            'systemRole' => 'employee',
            // Несуществующий, но валидный UUID v4
            'groupId' => 'f0000000-0000-4000-a000-000000000001',
        ]);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsUnauthorizedWithoutToken(): void
    {
        $this->client->request('POST', self::URL, [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'Пётр Петров',
            'email' => 'petr@example.com',
            'password' => 'secret123',
            'systemRole' => 'employee',
        ], \JSON_THROW_ON_ERROR));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
