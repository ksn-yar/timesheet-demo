<?php

declare(strict_types=1);

namespace App\Tests\Functional\ProjectManagement\Infrastructure\Controller;

use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

/**
 * Функциональные тесты контроллера удаления клиента.
 * Проверяет мягкое удаление, 404 и 422 при наличии активных проектов.
 *
 * @internal
 *
 * @coversNothing
 */
class DeleteClientControllerTest extends FunctionalTestCase
{
    private const string BASE_URL = '/api/project-management/clients/';

    #[Test]
    public function returnsNoContentOnSuccess(): void
    {
        $clientId = $this->createClientEntity();

        $this->authenticatedRequest('DELETE', self::BASE_URL . $clientId);

        self::assertSame(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsNotFoundWhenClientDoesNotExist(): void
    {
        $this->authenticatedRequest('DELETE', self::BASE_URL . 'f0000000-0000-4000-a000-000000000001');

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertSame('Клиент не найден.', $body['error']);
    }

    #[Test]
    public function returnsUnprocessableWhenClientHasActiveProjects(): void
    {
        $clientId = $this->createClientEntity();
        $this->createProject($clientId, 'Активный проект', 'active', self::FIXTURE_PROJECT_ID_1);

        $this->authenticatedRequest('DELETE', self::BASE_URL . $clientId);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsUnauthorizedWithoutToken(): void
    {
        $clientId = $this->createClientEntity();

        $this->client->request('DELETE', self::BASE_URL . $clientId);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
