<?php

declare(strict_types=1);

namespace App\Tests\Functional\ProjectManagement\Infrastructure\Controller;

use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

/**
 * Функциональные тесты контроллера получения списка клиентов.
 * Проверяет структуру JSON-ответа, пагинацию и фильтрацию.
 *
 * @internal
 *
 * @coversNothing
 */
class ListClientsControllerTest extends FunctionalTestCase
{
    private const string URL = '/api/project-management/clients';

    #[Test]
    public function returnsOkWithCorrectSchema(): void
    {
        $this->createClientEntity('Клиент Альфа', self::FIXTURE_CLIENT_ID_1);
        $this->createClientEntity('Клиент Бета', self::FIXTURE_CLIENT_ID_2);

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
        $this->createClientEntity('Клиент Альфа', self::FIXTURE_CLIENT_ID_1);

        $this->authenticatedRequest('GET', self::URL);

        $body = $this->getResponseJson();
        self::assertCount(1, $body['items']);

        $item = $body['items'][0];
        self::assertArrayHasKey('id', $item);
        self::assertArrayHasKey('name', $item);
        self::assertSame('Клиент Альфа', $item['name']);
    }

    #[Test]
    public function respectsPaginationParameters(): void
    {
        $this->createClientEntity('Клиент 1', self::FIXTURE_CLIENT_ID_1);
        $this->createClientEntity('Клиент 2', self::FIXTURE_CLIENT_ID_2);

        $this->authenticatedRequest('GET', self::URL . '?page=1&perPage=1');

        $body = $this->getResponseJson();
        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertCount(1, $body['items']);
        self::assertSame(2, $body['total']);
        self::assertSame(1, $body['perPage']);
    }

    #[Test]
    public function returnsEmptyListWhenNoClients(): void
    {
        $this->authenticatedRequest('GET', self::URL);

        $body = $this->getResponseJson();
        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertSame(0, $body['total']);
        self::assertCount(0, $body['items']);
    }

    #[Test]
    public function returnsUnauthorizedWithoutToken(): void
    {
        $this->client->request('GET', self::URL);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
