<?php

declare(strict_types=1);

namespace App\Tests\Functional\Timesheet\Infrastructure\Http\Controller;

use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

/**
 * Функциональные тесты контроллера обновления тикета учёта времени.
 *
 * @internal
 *
 * @coversNothing
 */
class UpdateManualTicketControllerTest extends FunctionalTestCase
{
    private const string BASE_URL = '/api/timesheet/tickets';

    #[Test]
    public function returnsNoContentOnSuccess(): void
    {
        $employeeId = $this->createUser('emp@test.com', 'Сотрудник', 'employee', self::FIXTURE_USER_ID_1);
        $clientId = $this->createClientEntity();
        $projectId = $this->createProject($clientId);
        $taskId = $this->createTask('Задача', $projectId, null, self::FIXTURE_TASK_ID_1);
        $workId = $this->createWork();
        $ticketId = $this->createTicket($employeeId, $taskId, $workId);

        $this->authenticatedRequest('PUT', self::BASE_URL . '/' . $ticketId, [
            'hours' => '4',
            'date' => '2026-01-20',
        ]);

        self::assertSame(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsNotFoundForMissingTicket(): void
    {
        $this->authenticatedRequest('PUT', self::BASE_URL . '/aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee', [
            'hours' => '4',
        ]);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsUnprocessableWhenTicketNotEditable(): void
    {
        $employeeId = $this->createUser('emp@test.com', 'Сотрудник', 'employee', self::FIXTURE_USER_ID_1);
        $clientId = $this->createClientEntity();
        $projectId = $this->createProject($clientId);
        $taskId = $this->createTask('Задача', $projectId, null, self::FIXTURE_TASK_ID_1);
        $workId = $this->createWork();
        $ticketId = $this->createTicket($employeeId, $taskId, $workId, isEditable: false);

        $this->authenticatedRequest('PUT', self::BASE_URL . '/' . $ticketId, [
            'hours' => '4',
        ]);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsForbiddenWhenEmployeeUpdatesOtherUserTicket(): void
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
            method: 'PUT',
            uri: self::BASE_URL . '/' . $ticketId,
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $employee2Jwt,
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            content: json_encode(['hours' => '4'], \JSON_THROW_ON_ERROR),
        );

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsUnauthorizedWithoutToken(): void
    {
        $this->client->request('PUT', self::BASE_URL . '/aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee');

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
