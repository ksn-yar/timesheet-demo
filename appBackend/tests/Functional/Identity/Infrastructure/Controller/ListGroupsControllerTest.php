<?php

declare(strict_types=1);

namespace App\Tests\Functional\Identity\Infrastructure\Controller;

use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

/**
 * Функциональные тесты контроллера получения списка групп.
 * Проверяет структуру JSON-ответа, пагинацию и валидацию параметров запроса.
 *
 * @internal
 *
 * @coversNothing
 */
class ListGroupsControllerTest extends FunctionalTestCase
{
    private const string URL = '/api/identity/groups';

    #[Test]
    public function returnsOkWithCorrectSchema(): void
    {
        $this->createGroup('Разработка', self::FIXTURE_GROUP_ID_1);
        $this->createGroup('Дизайн', self::FIXTURE_GROUP_ID_2);

        $this->authenticatedRequest('GET', self::URL);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();

        self::assertArrayHasKey('items', $body);
        self::assertArrayHasKey('total', $body);
        self::assertArrayHasKey('page', $body);
        self::assertArrayHasKey('perPage', $body);
        self::assertIsArray($body['items']);
        self::assertSame(2, $body['total']);
    }

    #[Test]
    public function returnsItemsWithCorrectFields(): void
    {
        $this->createGroup('Разработка', self::FIXTURE_GROUP_ID_1);

        $this->authenticatedRequest('GET', self::URL);

        $body = $this->getResponseJson();
        self::assertCount(1, $body['items']);
        $group = $body['items'][0];

        self::assertArrayHasKey('id', $group);
        self::assertArrayHasKey('name', $group);
        self::assertSame('Разработка', $group['name']);
    }

    #[Test]
    public function returnsEmptyListWhenNoGroups(): void
    {
        $this->authenticatedRequest('GET', self::URL);

        $body = $this->getResponseJson();
        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertCount(0, $body['items']);
        self::assertSame(0, $body['total']);
    }

    #[Test]
    public function respectsPaginationParameters(): void
    {
        $this->createGroup('Группа 1', self::FIXTURE_GROUP_ID_1);
        $this->createGroup('Группа 2', self::FIXTURE_GROUP_ID_2);
        $this->createGroup('Группа 3', self::FIXTURE_GROUP_ID_3);

        $this->authenticatedRequest('GET', self::URL . '?page=1&perPage=2');

        $body = $this->getResponseJson();
        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertCount(2, $body['items']);
        self::assertSame(3, $body['total']);
        self::assertSame(2, $body['perPage']);
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
