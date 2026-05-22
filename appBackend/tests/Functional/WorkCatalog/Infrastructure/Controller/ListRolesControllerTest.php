<?php

declare(strict_types=1);

namespace App\Tests\Functional\WorkCatalog\Infrastructure\Controller;

use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

/**
 * Функциональные тесты контроллера получения списка ролей.
 * Проверяет структуру ответа и данные.
 *
 * @internal
 *
 * @coversNothing
 */
class ListRolesControllerTest extends FunctionalTestCase
{
    private const string BASE_URL = '/api/work-catalog/roles';

    #[Test]
    public function returnsCorrectSchema(): void
    {
        $this->authenticatedRequest('GET', self::BASE_URL);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertArrayHasKey('items', $body);
        self::assertArrayHasKey('total', $body);
        self::assertArrayHasKey('page', $body);
        self::assertArrayHasKey('perPage', $body);
    }

    #[Test]
    public function returnsCorrectRoleFields(): void
    {
        $this->createRole('Разработчик');

        $this->authenticatedRequest('GET', self::BASE_URL);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertCount(1, $body['items']);

        $item = $body['items'][0];
        self::assertArrayHasKey('id', $item);
        self::assertArrayHasKey('name', $item);
        self::assertSame('Разработчик', $item['name']);
    }

    #[Test]
    public function returnsEmptyListWhenNoRoles(): void
    {
        $this->authenticatedRequest('GET', self::BASE_URL);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertSame([], $body['items']);
        self::assertSame(0, $body['total']);
    }

    #[Test]
    public function returnsUnauthorizedWithoutToken(): void
    {
        $this->client->request('GET', self::BASE_URL);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
