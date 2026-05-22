<?php

declare(strict_types=1);

namespace App\Tests\Functional\ProjectManagement\Infrastructure\Controller;

use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

/**
 * Функциональные тесты контроллера создания задачи.
 * Проверяет валидацию DTO, правило ExactlyOneParent, 404 при отсутствии родителя и JWT-аутентификацию.
 *
 * @internal
 *
 * @coversNothing
 */
class CreateTaskControllerTest extends FunctionalTestCase
{
    private const string URL = '/api/project-management/tasks';

    #[Test]
    public function returnsCreatedWithProjectId(): void
    {
        $clientId = $this->createClientEntity();
        $projectId = $this->createProject($clientId);

        $this->authenticatedRequest('POST', self::URL, [
            'projectId' => $projectId,
            'name' => 'Задача для проекта',
        ]);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsCreatedWithCrId(): void
    {
        $clientId = $this->createClientEntity();
        $projectId = $this->createProject($clientId);
        $crId = $this->createChangeRequest($projectId);

        $this->authenticatedRequest('POST', self::URL, [
            'crId' => $crId,
            'name' => 'Задача для CR',
        ]);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsBadRequestWhenNameMissing(): void
    {
        $clientId = $this->createClientEntity();
        $projectId = $this->createProject($clientId);

        $this->authenticatedRequest('POST', self::URL, [
            'projectId' => $projectId,
        ]);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsBadRequestWhenBothParentsMissing(): void
    {
        $this->authenticatedRequest('POST', self::URL, [
            'name' => 'Задача без родителя',
        ]);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsBadRequestWhenBothParentsProvided(): void
    {
        $clientId = $this->createClientEntity();
        $projectId = $this->createProject($clientId);
        $crId = $this->createChangeRequest($projectId);

        $this->authenticatedRequest('POST', self::URL, [
            'projectId' => $projectId,
            'crId' => $crId,
            'name' => 'Задача с двумя родителями',
        ]);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsNotFoundWhenProjectDoesNotExist(): void
    {
        $this->authenticatedRequest('POST', self::URL, [
            'projectId' => 'f0000000-0000-4000-a000-000000000001',
            'name' => 'Задача несуществующего проекта',
        ]);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsNotFoundWhenCrDoesNotExist(): void
    {
        $this->authenticatedRequest('POST', self::URL, [
            'crId' => 'f0000000-0000-4000-a000-000000000001',
            'name' => 'Задача несуществующего CR',
        ]);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsUnauthorizedWithoutToken(): void
    {
        $this->client->request('POST', self::URL, [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'projectId' => self::FIXTURE_PROJECT_ID_1,
            'name' => 'Задача',
        ], \JSON_THROW_ON_ERROR));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
