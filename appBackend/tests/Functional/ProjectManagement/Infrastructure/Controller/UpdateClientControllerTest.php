<?php

declare(strict_types=1);

namespace App\Tests\Functional\ProjectManagement\Infrastructure\Controller;

use App\Persistence\Entity\Client as ClientOrmEntity;
use App\Tests\Functional\FunctionalTestCase;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

/**
 * Функциональные тесты контроллера обновления клиента.
 * Проверяет 404, валидацию и 422 при удалённом клиенте.
 *
 * @internal
 *
 * @coversNothing
 */
class UpdateClientControllerTest extends FunctionalTestCase
{
    private const string BASE_URL = '/api/project-management/clients/';

    #[Test]
    public function returnsNoContentOnSuccess(): void
    {
        $clientId = $this->createClientEntity('Старое название');

        $this->authenticatedRequest('PUT', self::BASE_URL . $clientId, [
            'name' => 'Новое название',
        ]);

        self::assertSame(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsBadRequestWhenNameIsBlank(): void
    {
        $clientId = $this->createClientEntity();

        $this->authenticatedRequest('PUT', self::BASE_URL . $clientId, [
            'name' => '',
        ]);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsNotFoundWhenClientDoesNotExist(): void
    {
        $this->authenticatedRequest('PUT', self::BASE_URL . 'f0000000-0000-4000-a000-000000000001', [
            'name' => 'Новое название',
        ]);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertSame('Клиент не найден.', $body['error']);
    }

    #[Test]
    public function returnsUnprocessableWhenClientDeleted(): void
    {
        $clientId = $this->createClientEntity();

        $em = $this->getEntityManager();
        $client = $em->find(ClientOrmEntity::class, $clientId);
        \assert(null !== $client);
        $client->setDeletedAt(new DateTimeImmutable());
        $em->flush();

        $this->authenticatedRequest('PUT', self::BASE_URL . $clientId, [
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

        $this->client->request('PUT', self::BASE_URL . $clientId, [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['name' => 'Новое название'], \JSON_THROW_ON_ERROR));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
