<?php

declare(strict_types=1);

namespace App\Tests\Functional\WorkCatalog\Infrastructure\Controller;

use App\Persistence\Entity\Rate as RateOrmEntity;
use App\Persistence\Entity\Role as RoleOrmEntity;
use App\Tests\Functional\FunctionalTestCase;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

/**
 * Функциональные тесты контроллера удаления роли.
 * Проверяет мягкое удаление, 404 и 422 при наличии активных ставок или привязанных пользователей.
 *
 * @internal
 *
 * @coversNothing
 */
class DeleteRoleControllerTest extends FunctionalTestCase
{
    private const string BASE_URL = '/api/work-catalog/roles/';

    #[Test]
    public function returnsNoContentOnSuccess(): void
    {
        $roleId = $this->createRole();

        $this->authenticatedRequest('DELETE', self::BASE_URL . $roleId);

        self::assertSame(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsNotFoundWhenRoleDoesNotExist(): void
    {
        $this->authenticatedRequest('DELETE', self::BASE_URL . 'f0000000-0000-4000-a000-000000000001');

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsUnprocessableWhenRoleHasActiveRates(): void
    {
        $roleId = $this->createRole();
        $this->createRate('1500.00', 'RUB', '2026-01-01', $roleId, null, self::FIXTURE_RATE_ID_1);

        $this->authenticatedRequest('DELETE', self::BASE_URL . $roleId);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsUnprocessableWhenRoleHasLinkedUsers(): void
    {
        $em = $this->getEntityManager();
        $roleId = $this->createRole();
        $userId = $this->createUser('employee@example.com', 'Сотрудник', 'employee', self::FIXTURE_USER_ID_1);

        $role = $em->find(RoleOrmEntity::class, $roleId);
        \assert(null !== $role);
        $user = $em->find(\App\Persistence\Entity\User::class, $userId);
        \assert(null !== $user);
        $user->setRole($role);
        $em->flush();

        $this->authenticatedRequest('DELETE', self::BASE_URL . $roleId);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsUnauthorizedWithoutToken(): void
    {
        $roleId = $this->createRole();

        $this->client->request('DELETE', self::BASE_URL . $roleId);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
