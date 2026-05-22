<?php

declare(strict_types=1);

namespace App\Tests\Functional\Timesheet\Infrastructure\Http\Controller;

use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

/**
 * Функциональные тесты контроллера создания политики импорта тикетов.
 *
 * @internal
 *
 * @coversNothing
 */
class CreateImportPolicyControllerTest extends FunctionalTestCase
{
    private const string BASE_URL = '/api/timesheet/import-policies';

    #[Test]
    public function returnsCreatedOnSuccess(): void
    {
        $this->authenticatedRequest('POST', self::BASE_URL, [
            'name' => 'Импорт из Jira',
            'sourceSystem' => 'jira',
            'mappingRules' => ['taskId' => 'issue_key'],
            'allowEdit' => false,
        ]);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsCreatedWithAllowEditEnabled(): void
    {
        $this->authenticatedRequest('POST', self::BASE_URL, [
            'name' => 'Импорт с редактированием',
            'sourceSystem' => 'gitlab',
            'mappingRules' => ['hours' => 'spent_time'],
            'allowEdit' => true,
        ]);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsBadRequestWhenNameIsBlank(): void
    {
        $this->authenticatedRequest('POST', self::BASE_URL, [
            'name' => '',
            'sourceSystem' => 'jira',
            'mappingRules' => ['taskId' => 'issue_key'],
            'allowEdit' => false,
        ]);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsBadRequestWhenSourceSystemIsBlank(): void
    {
        $this->authenticatedRequest('POST', self::BASE_URL, [
            'name' => 'Политика',
            'sourceSystem' => '',
            'mappingRules' => ['taskId' => 'issue_key'],
            'allowEdit' => false,
        ]);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsBadRequestWhenMissingRequiredFields(): void
    {
        $this->authenticatedRequest('POST', self::BASE_URL, []);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsUnauthorizedWithoutToken(): void
    {
        $this->client->request('POST', self::BASE_URL);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
