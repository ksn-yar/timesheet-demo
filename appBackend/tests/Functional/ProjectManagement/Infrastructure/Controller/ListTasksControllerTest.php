<?php

declare(strict_types=1);

namespace App\Tests\Functional\ProjectManagement\Infrastructure\Controller;

use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

/**
 * Функциональные тесты контроллера получения списка задач.
 * Проверяет структуру JSON-ответа и фильтрацию по проекту / CR.
 *
 * @internal
 *
 * @coversNothing
 */
class ListTasksControllerTest extends FunctionalTestCase
{
    private const string URL = '/api/project-management/tasks';

    #[Test]
    public function returnsOkWithCorrectSchema(): void
    {
        $clientId = $this->createClientEntity();
        $projectId = $this->createProject($clientId);
        $this->createTask('Задача 1', $projectId, null, self::FIXTURE_TASK_ID_1);

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
        $this->createTask('Задача Альфа', $projectId, null, self::FIXTURE_TASK_ID_1);

        $this->authenticatedRequest('GET', self::URL);

        $body = $this->getResponseJson();
        $item = $body['items'][0];

        self::assertArrayHasKey('id', $item);
        self::assertArrayHasKey('name', $item);
        self::assertSame('Задача Альфа', $item['name']);
    }

    #[Test]
    public function filtersByProjectId(): void
    {
        $clientId = $this->createClientEntity();
        $projectId1 = $this->createProject($clientId, 'Проект 1', 'active', self::FIXTURE_PROJECT_ID_1);
        $projectId2 = $this->createProject($clientId, 'Проект 2', 'active', self::FIXTURE_PROJECT_ID_2);
        $this->createTask('Задача проекта 1', $projectId1, null, self::FIXTURE_TASK_ID_1);
        $this->createTask('Задача проекта 2', $projectId2, null, self::FIXTURE_TASK_ID_2);

        $this->authenticatedRequest('GET', self::URL . '?projectId=' . $projectId1);

        $body = $this->getResponseJson();
        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertSame(1, $body['total']);
        self::assertSame('Задача проекта 1', $body['items'][0]['name']);
    }

    #[Test]
    public function filtersByCrId(): void
    {
        $clientId = $this->createClientEntity();
        $projectId = $this->createProject($clientId);
        $crId = $this->createChangeRequest($projectId, 'CR-001', self::FIXTURE_CR_ID_1);
        $this->createTask('Задача CR', null, $crId, self::FIXTURE_TASK_ID_1);
        $this->createTask('Задача проекта', $projectId, null, self::FIXTURE_TASK_ID_2);

        $this->authenticatedRequest('GET', self::URL . '?crId=' . $crId);

        $body = $this->getResponseJson();
        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertSame(1, $body['total']);
        self::assertSame('Задача CR', $body['items'][0]['name']);
    }

    #[Test]
    public function returnsUnauthorizedWithoutToken(): void
    {
        $this->client->request('GET', self::URL);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
