<?php

declare(strict_types=1);

namespace App\Tests\Functional\ProjectManagement\Infrastructure\Controller;

use App\Persistence\Entity\Task as TaskOrmEntity;
use App\Tests\Functional\FunctionalTestCase;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

/**
 * Функциональные тесты контроллера обновления задачи.
 * Проверяет 404, валидацию и 422 при удалённой задаче.
 *
 * @internal
 *
 * @coversNothing
 */
class UpdateTaskControllerTest extends FunctionalTestCase
{
    private const string BASE_URL = '/api/project-management/tasks/';

    #[Test]
    public function returnsNoContentOnSuccess(): void
    {
        $clientId = $this->createClientEntity();
        $projectId = $this->createProject($clientId);
        $taskId = $this->createTask('Старое название', $projectId, null, self::FIXTURE_TASK_ID_1);

        $this->authenticatedRequest('PUT', self::BASE_URL . $taskId, [
            'name' => 'Новое название задачи',
            'estimate' => 4.5,
        ]);

        self::assertSame(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsBadRequestWhenNameIsBlank(): void
    {
        $clientId = $this->createClientEntity();
        $projectId = $this->createProject($clientId);
        $taskId = $this->createTask('Задача', $projectId, null, self::FIXTURE_TASK_ID_1);

        $this->authenticatedRequest('PUT', self::BASE_URL . $taskId, [
            'name' => '',
        ]);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsBadRequestWhenEstimateNotPositive(): void
    {
        $clientId = $this->createClientEntity();
        $projectId = $this->createProject($clientId);
        $taskId = $this->createTask('Задача', $projectId, null, self::FIXTURE_TASK_ID_1);

        $this->authenticatedRequest('PUT', self::BASE_URL . $taskId, [
            'estimate' => -1,
        ]);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsNotFoundWhenTaskDoesNotExist(): void
    {
        $this->authenticatedRequest('PUT', self::BASE_URL . 'f0000000-0000-4000-a000-000000000001', [
            'name' => 'Новое название',
        ]);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertSame('Задача не найдена.', $body['error']);
    }

    #[Test]
    public function returnsUnprocessableWhenTaskDeleted(): void
    {
        $clientId = $this->createClientEntity();
        $projectId = $this->createProject($clientId);
        $taskId = $this->createTask('Задача', $projectId, null, self::FIXTURE_TASK_ID_1);

        $em = $this->getEntityManager();
        $task = $em->find(TaskOrmEntity::class, $taskId);
        \assert(null !== $task);
        $task->setDeletedAt(new DateTimeImmutable());
        $em->flush();

        $this->authenticatedRequest('PUT', self::BASE_URL . $taskId, [
            'name' => 'Новое название',
        ]);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsUnauthorizedWithoutToken(): void
    {
        $clientId = $this->createClientEntity();
        $projectId = $this->createProject($clientId);
        $taskId = $this->createTask('Задача', $projectId, null, self::FIXTURE_TASK_ID_1);

        $this->client->request('PUT', self::BASE_URL . $taskId, [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['name' => 'Новое название'], \JSON_THROW_ON_ERROR));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
