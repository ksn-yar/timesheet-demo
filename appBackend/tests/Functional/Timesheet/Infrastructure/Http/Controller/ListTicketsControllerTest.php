<?php

declare(strict_types=1);

namespace App\Tests\Functional\Timesheet\Infrastructure\Http\Controller;

use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

/**
 * Функциональные тесты контроллера списка тикетов учёта времени.
 *
 * @internal
 *
 * @coversNothing
 */
class ListTicketsControllerTest extends FunctionalTestCase
{
    private const string BASE_URL = '/api/timesheet/tickets';

    #[Test]
    public function returnsOkWithEmptyList(): void
    {
        $this->authenticatedRequest('GET', self::BASE_URL);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $body = $this->getResponseJson();
        self::assertSame([], $body['items']);
        self::assertSame(0, $body['total']);
    }

    #[Test]
    public function returnsOkWithPaginationStructure(): void
    {
        $this->authenticatedRequest('GET', self::BASE_URL . '?page=1&perPage=10');

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $body = $this->getResponseJson();
        self::assertArrayHasKey('items', $body);
        self::assertArrayHasKey('total', $body);
        self::assertArrayHasKey('page', $body);
        self::assertArrayHasKey('perPage', $body);
        self::assertSame(1, $body['page']);
        self::assertSame(10, $body['perPage']);
    }

    #[Test]
    public function returnsTicketsWhenTheyExist(): void
    {
        $employeeId = $this->createUser('emp@test.com', 'Сотрудник', 'employee', self::FIXTURE_USER_ID_1);
        $clientId = $this->createClientEntity();
        $projectId = $this->createProject($clientId);
        $taskId = $this->createTask('Задача', $projectId, null, self::FIXTURE_TASK_ID_1);
        $workId = $this->createWork();
        $this->createTicket($employeeId, $taskId, $workId);

        $this->authenticatedRequest('GET', self::BASE_URL);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $body = $this->getResponseJson();
        self::assertCount(1, $body['items']);
        self::assertSame(1, $body['total']);
        self::assertSame(self::FIXTURE_TICKET_ID_1, $body['items'][0]['id']);
    }

    #[Test]
    public function returnsFilteredTicketsByEmployeeId(): void
    {
        $employee1Id = $this->createUser('emp1@test.com', 'Сотрудник 1', 'employee', self::FIXTURE_USER_ID_1);
        $employee2Id = $this->createUser('emp2@test.com', 'Сотрудник 2', 'employee', self::FIXTURE_USER_ID_2);
        $clientId = $this->createClientEntity();
        $projectId = $this->createProject($clientId);
        $taskId = $this->createTask('Задача', $projectId, null, self::FIXTURE_TASK_ID_1);
        $workId = $this->createWork();
        $this->createTicket($employee1Id, $taskId, $workId, id: self::FIXTURE_TICKET_ID_1);
        $this->createTicket($employee2Id, $taskId, $workId, id: self::FIXTURE_TICKET_ID_2);

        $this->authenticatedRequest('GET', self::BASE_URL . '?employeeId=' . $employee1Id);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $body = $this->getResponseJson();
        self::assertCount(1, $body['items']);
        self::assertSame($employee1Id, $body['items'][0]['employeeId']);
    }

    #[Test]
    public function returnsUnauthorizedWithoutToken(): void
    {
        $this->client->request('GET', self::BASE_URL);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
