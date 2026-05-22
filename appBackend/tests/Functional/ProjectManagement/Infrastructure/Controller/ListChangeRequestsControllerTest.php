<?php

declare(strict_types=1);

namespace App\Tests\Functional\ProjectManagement\Infrastructure\Controller;

use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

/**
 * Функциональные тесты контроллера получения списка запросов на изменение.
 * Проверяет структуру JSON-ответа и фильтрацию по проекту.
 *
 * @internal
 *
 * @coversNothing
 */
class ListChangeRequestsControllerTest extends FunctionalTestCase
{
    private const string URL = '/api/project-management/change-requests';

    #[Test]
    public function returnsOkWithCorrectSchema(): void
    {
        $clientId = $this->createClientEntity();
        $projectId = $this->createProject($clientId);
        $this->createChangeRequest($projectId, 'CR-001', self::FIXTURE_CR_ID_1);

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
        $projectId = $this->createProject($clientId);
        $this->createChangeRequest($projectId, 'CR-001', self::FIXTURE_CR_ID_1);

        $this->authenticatedRequest('GET', self::URL);

        $body = $this->getResponseJson();
        $item = $body['items'][0];

        self::assertArrayHasKey('id', $item);
        self::assertArrayHasKey('name', $item);
        self::assertSame('CR-001', $item['name']);
    }

    #[Test]
    public function filtersByProjectId(): void
    {
        $clientId = $this->createClientEntity();
        $projectId1 = $this->createProject($clientId, 'Проект 1', 'active', self::FIXTURE_PROJECT_ID_1);
        $projectId2 = $this->createProject($clientId, 'Проект 2', 'active', self::FIXTURE_PROJECT_ID_2);
        $this->createChangeRequest($projectId1, 'CR проекта 1', self::FIXTURE_CR_ID_1);
        $this->createChangeRequest($projectId2, 'CR проекта 2', self::FIXTURE_CR_ID_2);

        $this->authenticatedRequest('GET', self::URL . '?projectId=' . $projectId1);

        $body = $this->getResponseJson();
        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertSame(1, $body['total']);
        self::assertSame('CR проекта 1', $body['items'][0]['name']);
    }

    #[Test]
    public function returnsUnauthorizedWithoutToken(): void
    {
        $this->client->request('GET', self::URL);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
