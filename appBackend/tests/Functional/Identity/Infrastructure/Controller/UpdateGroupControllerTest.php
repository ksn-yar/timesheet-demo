<?php

declare(strict_types=1);

namespace App\Tests\Functional\Identity\Infrastructure\Controller;

use App\Persistence\Entity\Group as GroupOrmEntity;
use App\Tests\Functional\FunctionalTestCase;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

/**
 * Функциональные тесты контроллера обновления группы.
 * Проверяет 404, дублирование имени, 422 при удалённой группе и аутентификацию.
 *
 * @internal
 *
 * @coversNothing
 */
class UpdateGroupControllerTest extends FunctionalTestCase
{
    #[Test]
    public function returnsNoContentOnSuccess(): void
    {
        $groupId = $this->createGroup('Старое Название');

        $this->authenticatedRequest('PUT', '/api/identity/groups/' . $groupId, [
            'name' => 'Новое Название',
        ]);

        self::assertSame(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsBadRequestWhenNameIsBlankString(): void
    {
        $groupId = $this->createGroup();

        $this->authenticatedRequest('PUT', '/api/identity/groups/' . $groupId, [
            'name' => '',
        ]);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsNotFoundWhenGroupDoesNotExist(): void
    {
        $this->authenticatedRequest('PUT', '/api/identity/groups/f0000000-0000-4000-a000-000000000001', [
            'name' => 'Новое Название',
        ]);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertSame('Группа не найдена.', $body['error']);
    }

    #[Test]
    public function returnsUnprocessableWhenDuplicateName(): void
    {
        $groupId = $this->createGroup('Первая Группа', self::FIXTURE_GROUP_ID_1);
        $this->createGroup('Вторая Группа', self::FIXTURE_GROUP_ID_2);

        $this->authenticatedRequest('PUT', '/api/identity/groups/' . $groupId, [
            'name' => 'Вторая Группа',
        ]);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsUnprocessableWhenGroupDeleted(): void
    {
        $groupId = $this->createGroup();

        $em = $this->getEntityManager();
        $group = $em->find(GroupOrmEntity::class, $groupId);
        \assert(null !== $group);
        $group->setDeletedAt(new DateTimeImmutable());
        $em->flush();

        $this->authenticatedRequest('PUT', '/api/identity/groups/' . $groupId, [
            'name' => 'Новое Название',
        ]);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsUnauthorizedWithoutToken(): void
    {
        $groupId = $this->createGroup();

        $this->client->request('PUT', '/api/identity/groups/' . $groupId, [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['name' => 'Новое Название'], \JSON_THROW_ON_ERROR));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
