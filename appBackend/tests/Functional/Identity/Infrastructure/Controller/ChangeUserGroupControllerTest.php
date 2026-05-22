<?php

declare(strict_types=1);

namespace App\Tests\Functional\Identity\Infrastructure\Controller;

use App\Persistence\Entity\User as UserOrmEntity;
use App\Tests\Functional\FunctionalTestCase;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

/**
 * Функциональные тесты контроллера изменения группы пользователя.
 * Проверяет назначение, снятие группы и граничные сценарии 404/422.
 *
 * @internal
 *
 * @coversNothing
 */
class ChangeUserGroupControllerTest extends FunctionalTestCase
{
    #[Test]
    public function returnsNoContentWhenAssigningGroup(): void
    {
        $userId = $this->createUser('group-change-user@example.com');
        $groupId = $this->createGroup('Новая группа', self::FIXTURE_GROUP_ID_2);

        $this->authenticatedRequest('PATCH', '/api/identity/users/' . $userId . '/group', [
            'groupId' => $groupId,
        ]);

        self::assertSame(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsNoContentWhenRemovingGroup(): void
    {
        $userId = $this->createUser('group-change-user@example.com');

        $this->authenticatedRequest('PATCH', '/api/identity/users/' . $userId . '/group', [
            'groupId' => null,
        ]);

        self::assertSame(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsBadRequestWhenGroupIdIsNotUuid(): void
    {
        $userId = $this->createUser('group-change-user@example.com');

        $this->authenticatedRequest('PATCH', '/api/identity/users/' . $userId . '/group', [
            'groupId' => 'not-a-uuid',
        ]);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsNotFoundWhenUserDoesNotExist(): void
    {
        $groupId = $this->createGroup();

        $this->authenticatedRequest('PATCH', '/api/identity/users/f0000000-0000-4000-a000-000000000001/group', [
            'groupId' => $groupId,
        ]);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsNotFoundWhenGroupDoesNotExist(): void
    {
        $userId = $this->createUser('group-change-user@example.com');

        $this->authenticatedRequest('PATCH', '/api/identity/users/' . $userId . '/group', [
            'groupId' => 'f0000000-0000-4000-a000-000000000001',
        ]);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsUnprocessableWhenUserDeleted(): void
    {
        $userId = $this->createUser('group-change-user@example.com');
        $groupId = $this->createGroup();

        $em = $this->getEntityManager();
        $user = $em->find(UserOrmEntity::class, $userId);
        \assert(null !== $user);
        $user->setDeletedAt(new DateTimeImmutable());
        $em->flush();

        $this->authenticatedRequest('PATCH', '/api/identity/users/' . $userId . '/group', [
            'groupId' => $groupId,
        ]);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsUnauthorizedWithoutToken(): void
    {
        $userId = $this->createUser('group-change-user@example.com');

        $this->client->request('PATCH', '/api/identity/users/' . $userId . '/group', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['groupId' => null], \JSON_THROW_ON_ERROR));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
