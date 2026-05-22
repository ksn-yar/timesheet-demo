<?php

declare(strict_types=1);

namespace App\Tests\Functional\WorkCatalog\Infrastructure\Controller;

use App\Persistence\Entity\Rate as RateOrmEntity;
use App\Persistence\Entity\Task as TaskOrmEntity;
use App\Persistence\Entity\Ticket as TicketOrmEntity;
use App\Persistence\Entity\Work as WorkOrmEntity;
use App\Tests\Functional\FunctionalTestCase;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

/**
 * Функциональные тесты контроллера удаления вида работ.
 * Проверяет мягкое удаление, 404 и 422 при наличии связанных тикетов.
 *
 * @internal
 *
 * @coversNothing
 */
class DeleteWorkControllerTest extends FunctionalTestCase
{
    private const string BASE_URL = '/api/work-catalog/works/';

    #[Test]
    public function returnsNoContentOnSuccess(): void
    {
        $workId = $this->createWork();

        $this->authenticatedRequest('DELETE', self::BASE_URL . $workId);

        self::assertSame(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsNotFoundWhenWorkDoesNotExist(): void
    {
        $this->authenticatedRequest('DELETE', self::BASE_URL . 'f0000000-0000-4000-a000-000000000001');

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsUnprocessableWhenWorkHasLinkedTickets(): void
    {
        $em = $this->getEntityManager();

        $workId = $this->createWork();
        $clientId = $this->createClientEntity();
        $projectId = $this->createProject($clientId);
        $taskId = $this->createTask('Задача', $projectId, null, self::FIXTURE_TASK_ID_1);
        $userId = $this->createUser('worker@example.com', 'Работник', 'employee', self::FIXTURE_USER_ID_1);

        $work = $em->find(WorkOrmEntity::class, $workId);
        \assert(null !== $work);
        $task = $em->find(TaskOrmEntity::class, $taskId);
        \assert(null !== $task);
        $user = $em->find(\App\Persistence\Entity\User::class, $userId);
        \assert(null !== $user);

        $ticket = new TicketOrmEntity();
        $ticket->setId('f9999999-89ab-4cde-8f01-234567890001');
        $ticket->setEmployee($user);
        $ticket->setTask($task);
        $ticket->setWork($work);
        $ticket->setDate(new DateTimeImmutable('2026-01-15'));
        $ticket->setHours('8.00');
        $ticket->setRateSnapshot('1500.00');
        $ticket->setType('manual');
        $ticket->setCreatedAt(new DateTimeImmutable());
        $em->persist($ticket);
        $em->flush();

        $this->authenticatedRequest('DELETE', self::BASE_URL . $workId);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsUnauthorizedWithoutToken(): void
    {
        $workId = $this->createWork();

        $this->client->request('DELETE', self::BASE_URL . $workId);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
