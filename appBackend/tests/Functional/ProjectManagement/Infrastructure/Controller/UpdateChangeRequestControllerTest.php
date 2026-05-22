<?php

declare(strict_types=1);

namespace App\Tests\Functional\ProjectManagement\Infrastructure\Controller;

use App\Persistence\Entity\ChangeRequest as ChangeRequestOrmEntity;
use App\Tests\Functional\FunctionalTestCase;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

/**
 * Функциональные тесты контроллера обновления запроса на изменение.
 * Проверяет 404, валидацию и 422 при удалённом CR.
 *
 * @internal
 *
 * @coversNothing
 */
class UpdateChangeRequestControllerTest extends FunctionalTestCase
{
    private const string BASE_URL = '/api/project-management/change-requests/';

    #[Test]
    public function returnsNoContentOnSuccess(): void
    {
        $clientId = $this->createClientEntity();
        $projectId = $this->createProject($clientId);
        $crId = $this->createChangeRequest($projectId, 'Старое название');

        $this->authenticatedRequest('PUT', self::BASE_URL . $crId, [
            'name' => 'Новое название CR',
        ]);

        self::assertSame(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsBadRequestWhenNameIsBlank(): void
    {
        $clientId = $this->createClientEntity();
        $projectId = $this->createProject($clientId);
        $crId = $this->createChangeRequest($projectId);

        $this->authenticatedRequest('PUT', self::BASE_URL . $crId, [
            'name' => '',
        ]);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsNotFoundWhenChangeRequestDoesNotExist(): void
    {
        $this->authenticatedRequest('PUT', self::BASE_URL . 'f0000000-0000-4000-a000-000000000001', [
            'name' => 'Новое название',
        ]);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertSame('Запрос на изменение не найден.', $body['error']);
    }

    #[Test]
    public function returnsUnprocessableWhenChangeRequestDeleted(): void
    {
        $clientId = $this->createClientEntity();
        $projectId = $this->createProject($clientId);
        $crId = $this->createChangeRequest($projectId);

        $em = $this->getEntityManager();
        $cr = $em->find(ChangeRequestOrmEntity::class, $crId);
        \assert(null !== $cr);
        $cr->setDeletedAt(new DateTimeImmutable());
        $em->flush();

        $this->authenticatedRequest('PUT', self::BASE_URL . $crId, [
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
        $crId = $this->createChangeRequest($projectId);

        $this->client->request('PUT', self::BASE_URL . $crId, [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['name' => 'Новое название'], \JSON_THROW_ON_ERROR));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
