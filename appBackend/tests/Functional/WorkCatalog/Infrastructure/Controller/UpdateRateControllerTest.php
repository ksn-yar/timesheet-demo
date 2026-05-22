<?php

declare(strict_types=1);

namespace App\Tests\Functional\WorkCatalog\Infrastructure\Controller;

use App\Persistence\Entity\Rate as RateOrmEntity;
use App\Persistence\Entity\Task as TaskOrmEntity;
use App\Persistence\Entity\Ticket as TicketOrmEntity;
use App\Persistence\Entity\User as UserOrmEntity;
use App\Persistence\Entity\Work as WorkOrmEntity;
use App\Tests\Functional\FunctionalTestCase;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

/**
 * Функциональные тесты контроллера обновления ставки.
 * Проверяет успешное обновление, валидацию, 404 и 422 при бизнес-нарушениях.
 *
 * @internal
 *
 * @coversNothing
 */
class UpdateRateControllerTest extends FunctionalTestCase
{
    private const string BASE_URL = '/api/work-catalog/rates/';

    #[Test]
    public function returnsOkOnSuccess(): void
    {
        $rateId = $this->createRate();

        $this->authenticatedRequest('PUT', self::BASE_URL . $rateId, [
            'amount' => '2000.00',
        ]);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsOkWhenUpdatingCurrency(): void
    {
        $rateId = $this->createRate();

        $this->authenticatedRequest('PUT', self::BASE_URL . $rateId, [
            'currency' => 'USD',
        ]);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsBadRequestWhenAmountIsNegative(): void
    {
        $rateId = $this->createRate();

        $this->authenticatedRequest('PUT', self::BASE_URL . $rateId, [
            'amount' => '-100',
        ]);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsBadRequestWhenCurrencyIsInvalidLength(): void
    {
        $rateId = $this->createRate();

        $this->authenticatedRequest('PUT', self::BASE_URL . $rateId, [
            'currency' => 'RU',
        ]);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsBadRequestWhenEffectiveFromIsInvalidDate(): void
    {
        $rateId = $this->createRate();

        $this->authenticatedRequest('PUT', self::BASE_URL . $rateId, [
            'effectiveFrom' => 'not-a-date',
        ]);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsBadRequestWhenRoleIdIsInvalidUuid(): void
    {
        $rateId = $this->createRate();

        $this->authenticatedRequest('PUT', self::BASE_URL . $rateId, [
            'roleId' => 'not-a-uuid',
        ]);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsNotFoundWhenRateDoesNotExist(): void
    {
        $this->authenticatedRequest('PUT', self::BASE_URL . 'f0000000-0000-4000-a000-000000000001', [
            'amount' => '2000.00',
        ]);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsNotFoundWhenRoleDoesNotExist(): void
    {
        $rateId = $this->createRate();

        $this->authenticatedRequest('PUT', self::BASE_URL . $rateId, [
            'roleId' => 'f0000000-0000-4000-a000-000000000001',
        ]);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsUnprocessableWhenRateIsDeleted(): void
    {
        $em = $this->getEntityManager();
        $rateId = $this->createRate();

        $rate = $em->find(RateOrmEntity::class, $rateId);
        \assert(null !== $rate);
        $rate->setDeletedAt(new DateTimeImmutable());
        $em->flush();

        $this->authenticatedRequest('PUT', self::BASE_URL . $rateId, [
            'amount' => '2000.00',
        ]);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsUnprocessableWhenChangingEffectiveFromOfAppliedRate(): void
    {
        $em = $this->getEntityManager();

        $workId = $this->createWork('Разработка', self::FIXTURE_WORK_ID_1);
        $rateId = $this->createRate('1500.00', 'RUB', '2026-01-01', null, null, self::FIXTURE_RATE_ID_1);
        $clientId = $this->createClientEntity();
        $projectId = $this->createProject($clientId);
        $taskId = $this->createTask('Задача', $projectId, null, self::FIXTURE_TASK_ID_1);
        $userId = $this->createUser('worker@example.com', 'Работник', 'employee', self::FIXTURE_USER_ID_1);

        $work = $em->find(WorkOrmEntity::class, $workId);
        \assert(null !== $work);
        $rate = $em->find(RateOrmEntity::class, $rateId);
        \assert(null !== $rate);
        $task = $em->find(TaskOrmEntity::class, $taskId);
        \assert(null !== $task);
        $user = $em->find(UserOrmEntity::class, $userId);
        \assert(null !== $user);

        $ticket = new TicketOrmEntity();
        $ticket->setId('f9999999-89ab-4cde-8f01-234567890099');
        $ticket->setEmployee($user);
        $ticket->setTask($task);
        $ticket->setWork($work);
        $ticket->setRate($rate);
        $ticket->setDate(new DateTimeImmutable('2026-01-15'));
        $ticket->setHours('8.00');
        $ticket->setRateSnapshot('1500.00');
        $ticket->setType('manual');
        $ticket->setCreatedAt(new DateTimeImmutable());
        $em->persist($ticket);
        $em->flush();

        $this->authenticatedRequest('PUT', self::BASE_URL . $rateId, [
            'effectiveFrom' => '2026-06-01',
        ]);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsUnauthorizedWithoutToken(): void
    {
        $rateId = $this->createRate();

        $this->client->request('PUT', self::BASE_URL . $rateId, [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['amount' => '2000.00'], \JSON_THROW_ON_ERROR));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
