<?php

declare(strict_types=1);

namespace App\Tests\Functional\Timesheet\Infrastructure\Http\Controller;

use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

/**
 * Функциональные тесты контроллера создания тикета учёта времени вручную.
 *
 * @internal
 *
 * @coversNothing
 */
class CreateManualTicketControllerTest extends FunctionalTestCase
{
    private const string BASE_URL = '/api/timesheet/tickets';

    #[Test]
    public function returnsCreatedOnSuccess(): void
    {
        $employeeId = $this->createUser('emp@test.com', 'Сотрудник', 'employee', self::FIXTURE_USER_ID_1);
        $clientId = $this->createClientEntity();
        $projectId = $this->createProject($clientId);
        $taskId = $this->createTask('Задача', $projectId, null, self::FIXTURE_TASK_ID_1);
        $workId = $this->createWork();

        $this->authenticatedRequest('POST', self::BASE_URL, [
            'employeeId' => $employeeId,
            'taskId' => $taskId,
            'workId' => $workId,
            'date' => '2026-01-15',
            'hours' => '8',
        ]);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsBadRequestWhenMissingRequiredFields(): void
    {
        $this->authenticatedRequest('POST', self::BASE_URL, []);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsNotFoundWhenTaskNotFound(): void
    {
        $employeeId = $this->createUser('emp@test.com', 'Сотрудник', 'employee', self::FIXTURE_USER_ID_1);
        $workId = $this->createWork();

        $this->authenticatedRequest('POST', self::BASE_URL, [
            'employeeId' => $employeeId,
            'taskId' => 'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee',
            'workId' => $workId,
            'date' => '2026-01-15',
            'hours' => '8',
        ]);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsNotFoundWhenWorkNotFound(): void
    {
        $employeeId = $this->createUser('emp@test.com', 'Сотрудник', 'employee', self::FIXTURE_USER_ID_1);
        $clientId = $this->createClientEntity();
        $projectId = $this->createProject($clientId);
        $taskId = $this->createTask('Задача', $projectId, null, self::FIXTURE_TASK_ID_1);

        $this->authenticatedRequest('POST', self::BASE_URL, [
            'employeeId' => $employeeId,
            'taskId' => $taskId,
            'workId' => 'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee',
            'date' => '2026-01-15',
            'hours' => '8',
        ]);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsForbiddenWhenEmployeeCreatesForOtherEmployee(): void
    {
        $employee1Id = $this->createUser('emp1@test.com', 'Сотрудник 1', 'employee', self::FIXTURE_USER_ID_1);
        $employee2Id = $this->createUser('emp2@test.com', 'Сотрудник 2', 'employee', self::FIXTURE_USER_ID_2);
        $clientId = $this->createClientEntity();
        $projectId = $this->createProject($clientId);
        $taskId = $this->createTask('Задача', $projectId, null, self::FIXTURE_TASK_ID_1);
        $workId = $this->createWork();

        $employee1Jwt = $this->generateJwtForUser($employee1Id);

        $this->client->request(
            method: 'POST',
            uri: self::BASE_URL,
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $employee1Jwt,
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            content: json_encode([
                'employeeId' => $employee2Id,
                'taskId' => $taskId,
                'workId' => $workId,
                'date' => '2026-01-15',
                'hours' => '8',
            ], \JSON_THROW_ON_ERROR),
        );

        self::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
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
