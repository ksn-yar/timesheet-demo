<?php

declare(strict_types=1);

namespace App\Tests\Functional\ProjectManagement\Infrastructure\Controller;

use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

/**
 * Функциональные тесты контроллера создания проекта.
 * Проверяет валидацию DTO, 404 при отсутствии клиента и JWT-аутентификацию.
 *
 * @internal
 *
 * @coversNothing
 */
class CreateProjectControllerTest extends FunctionalTestCase
{
    private const string URL = '/api/project-management/projects';

    #[Test]
    public function returnsCreatedOnSuccess(): void
    {
        $clientId = $this->createClientEntity();

        $this->authenticatedRequest('POST', self::URL, [
            'clientId' => $clientId,
            'name' => 'Проект Альфа',
            'status' => 'active',
        ]);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsBadRequestWhenNameMissing(): void
    {
        $clientId = $this->createClientEntity();

        $this->authenticatedRequest('POST', self::URL, [
            'clientId' => $clientId,
            'status' => 'active',
        ]);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsBadRequestWhenStatusInvalid(): void
    {
        $clientId = $this->createClientEntity();

        $this->authenticatedRequest('POST', self::URL, [
            'clientId' => $clientId,
            'name' => 'Проект Альфа',
            'status' => 'unknown',
        ]);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsBadRequestWhenClientIdNotUuid(): void
    {
        $this->authenticatedRequest('POST', self::URL, [
            'clientId' => 'not-a-uuid',
            'name' => 'Проект Альфа',
            'status' => 'active',
        ]);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsNotFoundWhenClientDoesNotExist(): void
    {
        $this->authenticatedRequest('POST', self::URL, [
            'clientId' => 'f0000000-0000-4000-a000-000000000001',
            'name' => 'Проект Альфа',
            'status' => 'active',
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
            'clientId' => self::FIXTURE_CLIENT_ID_1,
            'name' => 'Проект Альфа',
            'status' => 'active',
        ], \JSON_THROW_ON_ERROR));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
