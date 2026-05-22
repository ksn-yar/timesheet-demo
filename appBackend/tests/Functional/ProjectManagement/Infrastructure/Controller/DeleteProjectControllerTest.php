<?php

declare(strict_types=1);

namespace App\Tests\Functional\ProjectManagement\Infrastructure\Controller;

use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

/**
 * Функциональные тесты контроллера удаления проекта.
 * Проверяет мягкое удаление, 404 и 422 при наличии связанных сущностей.
 *
 * @internal
 *
 * @coversNothing
 */
class DeleteProjectControllerTest extends FunctionalTestCase
{
    private const string BASE_URL = '/api/project-management/projects/';

    #[Test]
    public function returnsNoContentOnSuccess(): void
    {
        $clientId = $this->createClientEntity();
        $projectId = $this->createProject($clientId);

        $this->authenticatedRequest('DELETE', self::BASE_URL . $projectId);

        self::assertSame(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsNotFoundWhenProjectDoesNotExist(): void
    {
        $this->authenticatedRequest('DELETE', self::BASE_URL . 'f0000000-0000-4000-a000-000000000001');

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertSame('Проект не найден.', $body['error']);
    }

    #[Test]
    public function returnsUnprocessableWhenProjectHasLinkedChangeRequests(): void
    {
        $clientId = $this->createClientEntity();
        $projectId = $this->createProject($clientId);
        $this->createChangeRequest($projectId, 'CR-001', self::FIXTURE_CR_ID_1);

        $this->authenticatedRequest('DELETE', self::BASE_URL . $projectId);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsUnprocessableWhenProjectHasLinkedTasks(): void
    {
        $clientId = $this->createClientEntity();
        $projectId = $this->createProject($clientId);
        $this->createTask('Задача проекта', $projectId, null, self::FIXTURE_TASK_ID_1);

        $this->authenticatedRequest('DELETE', self::BASE_URL . $projectId);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsUnauthorizedWithoutToken(): void
    {
        $clientId = $this->createClientEntity();
        $projectId = $this->createProject($clientId);

        $this->client->request('DELETE', self::BASE_URL . $projectId);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
