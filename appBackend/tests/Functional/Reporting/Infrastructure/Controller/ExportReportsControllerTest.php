<?php

declare(strict_types=1);

namespace App\Tests\Functional\Reporting\Infrastructure\Controller;

use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

/**
 * Функциональные тесты контроллера экспорта отчётов в файл.
 *
 * @internal
 *
 * @coversNothing
 */
class ExportReportsControllerTest extends FunctionalTestCase
{
    private const string BASE_URL = '/api/reporting/exports';

    #[Test]
    public function returnsCreatedWithExportId(): void
    {
        $reportId = $this->createReport();

        $this->authenticatedRequest('POST', self::BASE_URL, [
            'reportIds' => [$reportId],
            'format' => 'csv',
        ]);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());

        $body = $this->getResponseJson();
        self::assertArrayHasKey('exportId', $body);
        self::assertNotEmpty($body['exportId']);
    }

    #[Test]
    public function returnsCreatedForXlsxFormat(): void
    {
        $reportId = $this->createReport();

        $this->authenticatedRequest('POST', self::BASE_URL, [
            'reportIds' => [$reportId],
            'format' => 'xlsx',
        ]);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsCreatedForMultipleReports(): void
    {
        $reportId1 = $this->createReport('Отчёт 1', id: self::FIXTURE_REPORT_ID_1);
        $reportId2 = $this->createReport('Отчёт 2', id: self::FIXTURE_REPORT_ID_2);

        $this->authenticatedRequest('POST', self::BASE_URL, [
            'reportIds' => [$reportId1, $reportId2],
            'format' => 'csv',
        ]);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsNotFoundWhenReportNotFound(): void
    {
        $this->authenticatedRequest('POST', self::BASE_URL, [
            'reportIds' => ['aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee'],
            'format' => 'csv',
        ]);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsBadRequestWhenReportIdsIsEmpty(): void
    {
        $this->authenticatedRequest('POST', self::BASE_URL, [
            'reportIds' => [],
            'format' => 'csv',
        ]);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsBadRequestWhenFormatIsInvalid(): void
    {
        $reportId = $this->createReport();

        $this->authenticatedRequest('POST', self::BASE_URL, [
            'reportIds' => [$reportId],
            'format' => 'txt',
        ]);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsUnauthorizedWithoutToken(): void
    {
        $this->client->request('POST', self::BASE_URL);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
