<?php

declare(strict_types=1);

namespace App\Tests\Functional\ProjectManagement\Infrastructure\Controller;

use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

/**
 * Функциональные тесты контроллера создания запроса на изменение.
 * Проверяет валидацию DTO, 404 при отсутствии проекта и JWT-аутентификацию.
 *
 * @internal
 *
 * @coversNothing
 */
class CreateChangeRequestControllerTest extends FunctionalTestCase
{
    private const string URL = '/api/project-management/change-requests';

    #[Test]
    public function returnsCreatedOnSuccess(): void
    {
        $clientId = $this->createClientEntity();
        $projectId = $this->createProject($clientId);

        $this->authenticatedRequest('POST', self::URL, [
            'projectId' => $projectId,
            'name' => 'CR-001: Расширение функциональности',
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
    public function returnsBadRequestWhenProjectIdNotUuid(): void
    {
        $this->authenticatedRequest('POST', self::URL, [
            'projectId' => 'not-a-uuid',
            'name' => 'CR-001',
        ]);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsNotFoundWhenProjectDoesNotExist(): void
    {
        $this->authenticatedRequest('POST', self::URL, [
            'projectId' => 'f0000000-0000-4000-a000-000000000001',
            'name' => 'CR-001',
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
            'name' => 'CR-001',
        ], \JSON_THROW_ON_ERROR));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
