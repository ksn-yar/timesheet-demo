<?php

declare(strict_types=1);

namespace App\Tests\Functional\ProjectManagement\Infrastructure\Controller;

use App\Persistence\Entity\Project as ProjectOrmEntity;
use App\Tests\Functional\FunctionalTestCase;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

/**
 * Функциональные тесты контроллера обновления проекта.
 * Проверяет 404, валидацию статуса и 422 при удалённом проекте.
 *
 * @internal
 *
 * @coversNothing
 */
class UpdateProjectControllerTest extends FunctionalTestCase
{
    private const string BASE_URL = '/api/project-management/projects/';

    #[Test]
    public function returnsNoContentOnSuccess(): void
    {
        $clientId = $this->createClientEntity();
        $projectId = $this->createProject($clientId, 'Старое название');

        $this->authenticatedRequest('PUT', self::BASE_URL . $projectId, [
            'name' => 'Новое название',
            'status' => 'closed',
        ]);

        self::assertSame(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsBadRequestWhenStatusInvalid(): void
    {
        $clientId = $this->createClientEntity();
        $projectId = $this->createProject($clientId);

        $this->authenticatedRequest('PUT', self::BASE_URL . $projectId, [
            'status' => 'unknown',
        ]);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsBadRequestWhenNameIsBlank(): void
    {
        $clientId = $this->createClientEntity();
        $projectId = $this->createProject($clientId);

        $this->authenticatedRequest('PUT', self::BASE_URL . $projectId, [
            'name' => '',
        ]);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsNotFoundWhenProjectDoesNotExist(): void
    {
        $this->authenticatedRequest('PUT', self::BASE_URL . 'f0000000-0000-4000-a000-000000000001', [
            'name' => 'Новое название',
        ]);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertSame('Проект не найден.', $body['error']);
    }

    #[Test]
    public function returnsUnprocessableWhenProjectDeleted(): void
    {
        $clientId = $this->createClientEntity();
        $projectId = $this->createProject($clientId);

        $em = $this->getEntityManager();
        $project = $em->find(ProjectOrmEntity::class, $projectId);
        \assert(null !== $project);
        $project->setDeletedAt(new DateTimeImmutable());
        $em->flush();

        $this->authenticatedRequest('PUT', self::BASE_URL . $projectId, [
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

        $this->client->request('PUT', self::BASE_URL . $projectId, [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['name' => 'Новое название'], \JSON_THROW_ON_ERROR));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
