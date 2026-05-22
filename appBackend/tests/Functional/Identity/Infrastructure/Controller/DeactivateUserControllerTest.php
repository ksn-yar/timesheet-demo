<?php

declare(strict_types=1);

namespace App\Tests\Functional\Identity\Infrastructure\Controller;

use App\Persistence\Entity\User as UserOrmEntity;
use App\Tests\Functional\FunctionalTestCase;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

/**
 * Функциональные тесты контроллера деактивации пользователя.
 * Проверяет сценарии 404, 422 и требование JWT-аутентификации.
 *
 * @internal
 *
 * @coversNothing
 */
class DeactivateUserControllerTest extends FunctionalTestCase
{
    #[Test]
    public function returnsNoContentOnSuccess(): void
    {
        $userId = $this->createUser('deactivate-user@example.com');

        $this->authenticatedRequest('POST', '/api/identity/users/' . $userId . '/deactivate');

        self::assertSame(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsNotFoundWhenUserDoesNotExist(): void
    {
        $this->authenticatedRequest('POST', '/api/identity/users/f0000000-0000-4000-a000-000000000001/deactivate');

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertSame('Пользователь не найден.', $body['error']);
    }

    #[Test]
    public function returnsUnprocessableWhenUserAlreadyDeactivated(): void
    {
        $userId = $this->createUser('deactivate-user@example.com');

        // Деактивируем первый раз
        $this->authenticatedRequest('POST', '/api/identity/users/' . $userId . '/deactivate');
        self::assertSame(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());

        // Повторная деактивация — уже деактивирован
        $this->authenticatedRequest('POST', '/api/identity/users/' . $userId . '/deactivate');
        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsUnprocessableWhenUserDeleted(): void
    {
        $userId = $this->createUser('deactivate-user@example.com');

        $em = $this->getEntityManager();
        $user = $em->find(UserOrmEntity::class, $userId);
        \assert(null !== $user);
        $user->setDeletedAt(new DateTimeImmutable());
        $em->flush();

        $this->authenticatedRequest('POST', '/api/identity/users/' . $userId . '/deactivate');

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsUnauthorizedWithoutToken(): void
    {
        $userId = $this->createUser('deactivate-user@example.com');

        $this->client->request('POST', '/api/identity/users/' . $userId . '/deactivate');

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
