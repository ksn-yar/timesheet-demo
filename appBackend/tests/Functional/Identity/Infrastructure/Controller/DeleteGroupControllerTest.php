<?php

declare(strict_types=1);

namespace App\Tests\Functional\Identity\Infrastructure\Controller;

use App\Persistence\Entity\Group as GroupOrmEntity;
use App\Persistence\Entity\User as UserOrmEntity;
use App\Tests\Functional\FunctionalTestCase;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Функциональные тесты контроллера удаления группы.
 * Проверяет мягкое удаление, 404 и 422 при наличии активных пользователей.
 *
 * @internal
 *
 * @coversNothing
 */
class DeleteGroupControllerTest extends FunctionalTestCase
{
    #[Test]
    public function returnsNoContentOnSuccess(): void
    {
        $groupId = $this->createGroup();

        $this->authenticatedRequest('DELETE', '/api/identity/groups/' . $groupId);

        self::assertSame(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsNotFoundWhenGroupDoesNotExist(): void
    {
        $this->authenticatedRequest('DELETE', '/api/identity/groups/f0000000-0000-4000-a000-000000000001');

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertSame('Группа не найдена.', $body['error']);
    }

    #[Test]
    public function returnsUnprocessableWhenGroupAlreadyDeleted(): void
    {
        $groupId = $this->createGroup();

        // Удаляем первый раз
        $this->authenticatedRequest('DELETE', '/api/identity/groups/' . $groupId);
        self::assertSame(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());

        // Повторное удаление — группа уже удалена
        $this->authenticatedRequest('DELETE', '/api/identity/groups/' . $groupId);
        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsUnprocessableWhenGroupHasActiveUsers(): void
    {
        $groupId = $this->createGroup('Группа с участниками', self::FIXTURE_GROUP_ID_4);

        $em = $this->getEntityManager();

        /** @var UserPasswordHasherInterface $hasher */
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $group = $em->find(GroupOrmEntity::class, $groupId);

        $user = new UserOrmEntity();
        $user->setId(self::FIXTURE_USER_ID_5);
        $user->setName('Пользователь в группе');
        $user->setEmail('user-in-group@example.com');
        $user->setPasswordHash($hasher->hashPassword($user, 'secret123'));
        $user->setSystemRole('employee');
        $user->setGroup($group);
        $user->setIsActive(true);
        $user->setCreatedAt(new DateTimeImmutable());

        $em->persist($user);
        $em->flush();

        $this->authenticatedRequest('DELETE', '/api/identity/groups/' . $groupId);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsUnauthorizedWithoutToken(): void
    {
        $groupId = $this->createGroup();

        $this->client->request('DELETE', '/api/identity/groups/' . $groupId);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
