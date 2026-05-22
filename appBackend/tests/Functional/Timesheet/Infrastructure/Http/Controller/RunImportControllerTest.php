<?php

declare(strict_types=1);

namespace App\Tests\Functional\Timesheet\Infrastructure\Http\Controller;

use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

/**
 * Функциональные тесты контроллера запуска импорта тикетов по политике.
 *
 * @internal
 *
 * @coversNothing
 */
class RunImportControllerTest extends FunctionalTestCase
{
    private const string IMPORT_POLICIES_URL = '/api/timesheet/import-policies';

    #[Test]
    public function returnsOkWithSummaryForActivePolicy(): void
    {
        $policyId = $this->createImportPolicy(isActive: true);

        $this->authenticatedRequest(
            'POST',
            self::IMPORT_POLICIES_URL . '/' . $policyId . '/run',
            ['dateFrom' => '2026-01-01', 'dateTo' => '2026-01-31'],
        );

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $body = $this->getResponseJson();
        self::assertArrayHasKey('imported', $body);
        self::assertArrayHasKey('duplicates', $body);
        self::assertArrayHasKey('errors', $body);
        self::assertArrayHasKey('logEntries', $body);
        self::assertSame(0, $body['imported']);
    }

    #[Test]
    public function returnsNotFoundForMissingPolicy(): void
    {
        $this->authenticatedRequest(
            'POST',
            self::IMPORT_POLICIES_URL . '/aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee/run',
            ['dateFrom' => '2026-01-01', 'dateTo' => '2026-01-31'],
        );

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsUnprocessableWhenPolicyIsNotActive(): void
    {
        $policyId = $this->createImportPolicy(isActive: false);

        $this->authenticatedRequest(
            'POST',
            self::IMPORT_POLICIES_URL . '/' . $policyId . '/run',
            ['dateFrom' => '2026-01-01', 'dateTo' => '2026-01-31'],
        );

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsBadRequestWhenMissingDateFrom(): void
    {
        $policyId = $this->createImportPolicy(isActive: true);

        $this->authenticatedRequest(
            'POST',
            self::IMPORT_POLICIES_URL . '/' . $policyId . '/run',
            ['dateTo' => '2026-01-31'],
        );

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsBadRequestWhenMissingDateTo(): void
    {
        $policyId = $this->createImportPolicy(isActive: true);

        $this->authenticatedRequest(
            'POST',
            self::IMPORT_POLICIES_URL . '/' . $policyId . '/run',
            ['dateFrom' => '2026-01-01'],
        );

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsUnauthorizedWithoutToken(): void
    {
        $this->client->request('POST', self::IMPORT_POLICIES_URL . '/aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee/run');

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
