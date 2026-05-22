<?php

declare(strict_types=1);

namespace App\Tests\Functional\ProjectManagement\Infrastructure\Controller;

use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

/**
 * Функциональные тесты контроллера получения списка проектов.
 * Проверяет структуру JSON-ответа, фильтрацию по клиенту и статусу.
 *
 * @internal
 *
 * @coversNothing
 */
class ListProjectsControllerTest extends FunctionalTestCase
{
    private const string URL = '/api/project-management/projects';

    #[Test]
    public function returnsOkWithCorrectSchema(): void
    {
        $clientId = $this->createClientEntity();
        $this->createProject($clientId, 'Проект Альфа', 'active', self::FIXTURE_PROJECT_ID_1);

        $this->authenticatedRequest('GET', self::URL);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();

        self::assertArrayHasKey('items', $body);
        self::assertArrayHasKey('total', $body);
        self::assertArrayHasKey('page', $body);
        self::assertArrayHasKey('perPage', $body);
        self::assertSame(1, $body['total']);
    }

    #[Test]
    public function returnsItemsWithCorrectFields(): void
    {
        $clientId = $this->createClientEntity();
        $this->createProject($clientId, 'Проект Альфа', 'active', self::FIXTURE_PROJECT_ID_1);

        $this->authenticatedRequest('GET', self::URL);

        $body = $this->getResponseJson();
        $item = $body['items'][0];

        self::assertArrayHasKey('id', $item);
        self::assertArrayHasKey('name', $item);
        self::assertArrayHasKey('status', $item);
        self::assertSame('Проект Альфа', $item['name']);
        self::assertSame('active', $item['status']);
    }

    #[Test]
    public function filtersByClientId(): void
    {
        $clientId1 = $this->createClientEntity('Клиент 1', self::FIXTURE_CLIENT_ID_1);
        $clientId2 = $this->createClientEntity('Клиент 2', self::FIXTURE_CLIENT_ID_2);
        $this->createProject($clientId1, 'Проект клиента 1', 'active', self::FIXTURE_PROJECT_ID_1);
        $this->createProject($clientId2, 'Проект клиента 2', 'active', self::FIXTURE_PROJECT_ID_2);

        $this->authenticatedRequest('GET', self::URL . '?clientId=' . $clientId1);

        $body = $this->getResponseJson();
        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertSame(1, $body['total']);
        self::assertSame('Проект клиента 1', $body['items'][0]['name']);
    }

    #[Test]
    public function returnsUnauthorizedWithoutToken(): void
    {
        $this->client->request('GET', self::URL);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
