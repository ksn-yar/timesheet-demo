<?php

declare(strict_types=1);

namespace App\Tests\Functional\Reporting\Infrastructure\Controller;

use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

/**
 * Функциональные тесты контроллера получения отчёта по идентификатору.
 *
 * @internal
 *
 * @coversNothing
 */
class GetReportControllerTest extends FunctionalTestCase
{
    private const string BASE_URL = '/api/reporting/reports';

    #[Test]
    public function returnsOkWithReportData(): void
    {
        $reportId = $this->createReport('Январский отчёт', '2026-01-01', '2026-01-31');

        $this->authenticatedRequest('GET', self::BASE_URL . '/' . $reportId);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $body = $this->getResponseJson();
        self::assertSame($reportId, $body['id']);
        self::assertSame('Январский отчёт', $body['name']);
        self::assertSame('2026-01-01', $body['periodFrom']);
        self::assertSame('2026-01-31', $body['periodTo']);
        self::assertArrayHasKey('groupBy', $body);
        self::assertArrayHasKey('data', $body);
    }

    #[Test]
    public function returnsNotFoundForMissingReport(): void
    {
        $this->authenticatedRequest('GET', self::BASE_URL . '/aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee');

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
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
