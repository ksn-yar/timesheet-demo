<?php

declare(strict_types=1);

namespace App\Tests\Functional\ProjectManagement\Infrastructure\Controller;

use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

/**
 * Функциональные тесты контроллера удаления запроса на изменение.
 * Проверяет мягкое удаление, 404 и 422 при наличии связанных задач.
 *
 * @internal
 *
 * @coversNothing
 */
class DeleteChangeRequestControllerTest extends FunctionalTestCase
{
    private const string BASE_URL = '/api/project-management/change-requests/';

    #[Test]
    public function returnsNoContentOnSuccess(): void
    {
        $clientId = $this->createClientEntity();
        $projectId = $this->createProject($clientId);
        $crId = $this->createChangeRequest($projectId);

        $this->authenticatedRequest('DELETE', self::BASE_URL . $crId);

        self::assertSame(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsNotFoundWhenChangeRequestDoesNotExist(): void
    {
        $this->authenticatedRequest('DELETE', self::BASE_URL . 'f0000000-0000-4000-a000-000000000001');

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertSame('Запрос на изменение не найден.', $body['error']);
    }

    #[Test]
    public function returnsUnprocessableWhenChangeRequestHasLinkedTasks(): void
    {
        $clientId = $this->createClientEntity();
        $projectId = $this->createProject($clientId);
        $crId = $this->createChangeRequest($projectId);
        $this->createTask('Задача CR', null, $crId, self::FIXTURE_TASK_ID_1);

        $this->authenticatedRequest('DELETE', self::BASE_URL . $crId);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsUnauthorizedWithoutToken(): void
    {
        $clientId = $this->createClientEntity();
        $projectId = $this->createProject($clientId);
        $crId = $this->createChangeRequest($projectId);

        $this->client->request('DELETE', self::BASE_URL . $crId);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
