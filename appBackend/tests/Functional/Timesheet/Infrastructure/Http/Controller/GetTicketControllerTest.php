<?php

declare(strict_types=1);

namespace App\Tests\Functional\Timesheet\Infrastructure\Http\Controller;

use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

/**
 * Функциональные тесты контроллера получения тикета по идентификатору.
 *
 * @internal
 *
 * @coversNothing
 */
class GetTicketControllerTest extends FunctionalTestCase
{
    private const string BASE_URL = '/api/timesheet/tickets';

    #[Test]
    public function returnsOkWithTicketData(): void
    {
        $employeeId = $this->createUser('emp@test.com', 'Сотрудник', 'employee', self::FIXTURE_USER_ID_1);
        $clientId = $this->createClientEntity();
        $projectId = $this->createProject($clientId);
        $taskId = $this->createTask('Задача', $projectId, null, self::FIXTURE_TASK_ID_1);
        $workId = $this->createWork();
        $ticketId = $this->createTicket($employeeId, $taskId, $workId);

        $this->authenticatedRequest('GET', self::BASE_URL . '/' . $ticketId);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $body = $this->getResponseJson();
        self::assertSame($ticketId, $body['id']);
        self::assertSame($employeeId, $body['employeeId']);
        self::assertSame($taskId, $body['taskId']);
        self::assertSame($workId, $body['workId']);
        self::assertSame('2026-01-15', $body['date']);
        self::assertSame('8.00', $body['hours']);
        self::assertArrayHasKey('isEditable', $body);
    }

    #[Test]
    public function returnsNotFoundForMissingTicket(): void
    {
        $this->authenticatedRequest('GET', self::BASE_URL . '/aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee');

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsForbiddenWhenEmployeeAccessesOtherUserTicket(): void
    {
        $employee1Id = $this->createUser('emp1@test.com', 'Сотрудник 1', 'employee', self::FIXTURE_USER_ID_1);
        $employee2Id = $this->createUser('emp2@test.com', 'Сотрудник 2', 'employee', self::FIXTURE_USER_ID_2);
        $clientId = $this->createClientEntity();
        $projectId = $this->createProject($clientId);
        $taskId = $this->createTask('Задача', $projectId, null, self::FIXTURE_TASK_ID_1);
        $workId = $this->createWork();
        $ticketId = $this->createTicket($employee1Id, $taskId, $workId);

        $employee2Jwt = $this->generateJwtForUser($employee2Id);

        $this->client->request(
            method: 'GET',
            uri: self::BASE_URL . '/' . $ticketId,
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $employee2Jwt,
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
        );

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsUnauthorizedWithoutToken(): void
    {
        $this->client->request('GET', self::BASE_URL . '/aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee');

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
