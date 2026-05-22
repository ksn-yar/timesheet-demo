<?php

declare(strict_types=1);

namespace App\Tests\Functional\Identity\Infrastructure\Controller;

use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

/**
 * Функциональные тесты контроллера получения списка пользователей.
 * Проверяет структуру JSON-ответа, пагинацию и валидацию параметров запроса.
 *
 * @internal
 *
 * @coversNothing
 */
class ListUsersControllerTest extends FunctionalTestCase
{
    private const string URL = '/api/identity/users';

    #[Test]
    public function returnsOkWithCorrectSchema(): void
    {
        $this->createUser('user1@example.com', 'Иван Иванов', 'employee', self::FIXTURE_USER_ID_2);
        $this->createUser('user2@example.com', 'Мария Петрова', 'manager', self::FIXTURE_USER_ID_3);

        $this->authenticatedRequest('GET', self::URL);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();

        self::assertArrayHasKey('items', $body);
        self::assertArrayHasKey('total', $body);
        self::assertArrayHasKey('page', $body);
        self::assertArrayHasKey('perPage', $body);
        self::assertIsArray($body['items']);
        // Список включает auth-пользователя + 2 созданных
        self::assertSame(3, $body['total']);
        self::assertSame(1, $body['page']);
    }

    #[Test]
    public function returnsItemsWithCorrectFields(): void
    {
        $this->createUser('user1@example.com', 'Иван Иванов', 'employee', self::FIXTURE_USER_ID_2);

        $this->authenticatedRequest('GET', self::URL);

        $body = $this->getResponseJson();
        $userItems = array_filter($body['items'], static fn ($u) => 'user1@example.com' === $u['email']);
        $user = array_values($userItems)[0];

        self::assertArrayHasKey('id', $user);
        self::assertArrayHasKey('name', $user);
        self::assertArrayHasKey('email', $user);
        self::assertArrayHasKey('systemRole', $user);
        self::assertArrayHasKey('isActive', $user);
        self::assertSame('Иван Иванов', $user['name']);
        self::assertSame('employee', $user['systemRole']);
        self::assertTrue($user['isActive']);
    }

    #[Test]
    public function respectsPaginationParameters(): void
    {
        $this->createUser('user1@example.com', 'Иван 1', 'employee', self::FIXTURE_USER_ID_2);
        $this->createUser('user2@example.com', 'Иван 2', 'employee', self::FIXTURE_USER_ID_3);
        $this->createUser('user3@example.com', 'Иван 3', 'employee', self::FIXTURE_USER_ID_4);

        $this->authenticatedRequest('GET', self::URL . '?page=1&perPage=2');

        $body = $this->getResponseJson();
        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertCount(2, $body['items']);
        self::assertSame(2, $body['perPage']);
        self::assertSame(1, $body['page']);
    }

    #[Test]
    public function returnsBadRequestWhenPageInvalid(): void
    {
        $this->authenticatedRequest('GET', self::URL . '?page=0');

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsBadRequestWhenPerPageOutOfRange(): void
    {
        $this->authenticatedRequest('GET', self::URL . '?perPage=200');

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsUnauthorizedWithoutToken(): void
    {
        $this->client->request('GET', self::URL);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
