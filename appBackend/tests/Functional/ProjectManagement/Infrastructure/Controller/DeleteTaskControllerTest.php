<?php

declare(strict_types=1);

namespace App\Tests\Functional\ProjectManagement\Infrastructure\Controller;

use App\Persistence\Entity\Task as TaskOrmEntity;
use App\Persistence\Entity\Ticket as TicketOrmEntity;
use App\Persistence\Entity\User;
use App\Persistence\Entity\Work as WorkOrmEntity;
use App\Tests\Functional\FunctionalTestCase;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

/**
 * Функциональные тесты контроллера удаления задачи.
 * Проверяет мягкое удаление, 404 и 422 при наличии связанных тикетов.
 *
 * @internal
 *
 * @coversNothing
 */
class DeleteTaskControllerTest extends FunctionalTestCase
{
    private const string BASE_URL = '/api/project-management/tasks/';

    #[Test]
    public function returnsNoContentOnSuccess(): void
    {
        $clientId = $this->createClientEntity();
        $projectId = $this->createProject($clientId);
        $taskId = $this->createTask('Задача', $projectId, null, self::FIXTURE_TASK_ID_1);

        $this->authenticatedRequest('DELETE', self::BASE_URL . $taskId);

        self::assertSame(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsNotFoundWhenTaskDoesNotExist(): void
    {
        $this->authenticatedRequest('DELETE', self::BASE_URL . 'f0000000-0000-4000-a000-000000000001');

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertSame('Задача не найдена.', $body['error']);
    }

    #[Test]
    public function returnsUnprocessableWhenTaskHasLinkedTickets(): void
    {
        $clientId = $this->createClientEntity();
        $projectId = $this->createProject($clientId);
        $taskId = $this->createTask('Задача с тикетом', $projectId, null, self::FIXTURE_TASK_ID_1);

        $em = $this->getEntityManager();

        $work = new WorkOrmEntity();
        $work->setId('b9999999-89ab-4cde-8f01-234567890001');
        $work->setName('Разработка');
        $work->setCreatedAt(new DateTimeImmutable());
        $em->persist($work);

        $task = $em->find(TaskOrmEntity::class, $taskId);
        \assert(null !== $task);

        $employee = $em->find(User::class, self::AUTH_USER_ID);
        \assert(null !== $employee);

        $ticket = new TicketOrmEntity();
        $ticket->setId('b8888888-89ab-4cde-8f01-234567890001');
        $ticket->setEmployee($employee);
        $ticket->setTask($task);
        $ticket->setWork($work);
        $ticket->setDate(new DateTimeImmutable());
        $ticket->setHours('8.00');
        $ticket->setRateSnapshot('0.00');
        $ticket->setType('manual');
        $ticket->setCreatedAt(new DateTimeImmutable());
        $em->persist($ticket);
        $em->flush();

        $this->authenticatedRequest('DELETE', self::BASE_URL . $taskId);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsUnauthorizedWithoutToken(): void
    {
        $clientId = $this->createClientEntity();
        $projectId = $this->createProject($clientId);
        $taskId = $this->createTask('Задача', $projectId, null, self::FIXTURE_TASK_ID_1);

        $this->client->request('DELETE', self::BASE_URL . $taskId);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
