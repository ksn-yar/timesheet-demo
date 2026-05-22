<?php

declare(strict_types=1);

namespace App\Tests\Functional\WorkCatalog\Infrastructure\Controller;

use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

/**
 * Функциональные тесты контроллера создания ставки.
 * Проверяет успешное создание, валидацию и 404 при отсутствии роли/вида работ.
 *
 * @internal
 *
 * @coversNothing
 */
class CreateRateControllerTest extends FunctionalTestCase
{
    private const string BASE_URL = '/api/work-catalog/rates';

    #[Test]
    public function returnsCreatedWithRole(): void
    {
        $roleId = $this->createRole();

        $this->authenticatedRequest('POST', self::BASE_URL, [
            'amount' => '1500.00',
            'currency' => 'RUB',
            'effectiveFrom' => '2026-01-01',
            'roleId' => $roleId,
        ]);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsCreatedWithWork(): void
    {
        $workId = $this->createWork();

        $this->authenticatedRequest('POST', self::BASE_URL, [
            'amount' => '2000.00',
            'currency' => 'USD',
            'effectiveFrom' => '2026-01-01',
            'workId' => $workId,
        ]);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsCreatedWithoutParent(): void
    {
        $this->authenticatedRequest('POST', self::BASE_URL, [
            'amount' => '1000.00',
            'currency' => 'EUR',
            'effectiveFrom' => '2026-06-01',
        ]);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsBadRequestWhenAmountIsMissing(): void
    {
        $this->authenticatedRequest('POST', self::BASE_URL, [
            'currency' => 'RUB',
            'effectiveFrom' => '2026-01-01',
        ]);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsBadRequestWhenAmountIsNegative(): void
    {
        $this->authenticatedRequest('POST', self::BASE_URL, [
            'amount' => '-100',
            'currency' => 'RUB',
            'effectiveFrom' => '2026-01-01',
        ]);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsBadRequestWhenCurrencyIsMissing(): void
    {
        $this->authenticatedRequest('POST', self::BASE_URL, [
            'amount' => '1500.00',
            'effectiveFrom' => '2026-01-01',
        ]);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsBadRequestWhenCurrencyIsInvalidLength(): void
    {
        $this->authenticatedRequest('POST', self::BASE_URL, [
            'amount' => '1500.00',
            'currency' => 'RU',
            'effectiveFrom' => '2026-01-01',
        ]);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsBadRequestWhenEffectiveFromIsMissing(): void
    {
        $this->authenticatedRequest('POST', self::BASE_URL, [
            'amount' => '1500.00',
            'currency' => 'RUB',
        ]);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsBadRequestWhenEffectiveFromIsInvalidDate(): void
    {
        $this->authenticatedRequest('POST', self::BASE_URL, [
            'amount' => '1500.00',
            'currency' => 'RUB',
            'effectiveFrom' => 'not-a-date',
        ]);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsBadRequestWhenRoleIdIsInvalidUuid(): void
    {
        $this->authenticatedRequest('POST', self::BASE_URL, [
            'amount' => '1500.00',
            'currency' => 'RUB',
            'effectiveFrom' => '2026-01-01',
            'roleId' => 'not-a-uuid',
        ]);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsNotFoundWhenRoleDoesNotExist(): void
    {
        $this->authenticatedRequest('POST', self::BASE_URL, [
            'amount' => '1500.00',
            'currency' => 'RUB',
            'effectiveFrom' => '2026-01-01',
            'roleId' => 'f0000000-0000-4000-a000-000000000001',
        ]);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsNotFoundWhenWorkDoesNotExist(): void
    {
        $this->authenticatedRequest('POST', self::BASE_URL, [
            'amount' => '1500.00',
            'currency' => 'RUB',
            'effectiveFrom' => '2026-01-01',
            'workId' => 'f0000000-0000-4000-a000-000000000002',
        ]);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsUnprocessableWhenRoleIsDeleted(): void
    {
        $em = $this->getEntityManager();
        $roleId = $this->createRole();
        $role = $em->find(\App\Persistence\Entity\Role::class, $roleId);
        \assert(null !== $role);
        $role->setDeletedAt(new \DateTimeImmutable());
        $em->flush();

        $this->authenticatedRequest('POST', self::BASE_URL, [
            'amount' => '1500.00',
            'currency' => 'RUB',
            'effectiveFrom' => '2026-01-01',
            'roleId' => $roleId,
        ]);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsUnauthorizedWithoutToken(): void
    {
        $this->client->request('POST', self::BASE_URL);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
