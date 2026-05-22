<?php

declare(strict_types=1);

namespace App\Tests\Functional\Reporting\Infrastructure\Controller;

use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

/**
 * Функциональные тесты контроллера получения списка выгрузок отчётов.
 *
 * @internal
 *
 * @coversNothing
 */
class ListExportedReportsControllerTest extends FunctionalTestCase
{
    private const string BASE_URL = '/api/reporting/exports';

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
    public function returnsExportsWhenTheyExist(): void
    {
        $this->createReportExportWithFile(format: 'csv', id: self::FIXTURE_REPORT_EXPORT_ID_1);

        $this->authenticatedRequest('GET', self::BASE_URL);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $body = $this->getResponseJson();
        self::assertCount(1, $body['items']);
        self::assertSame(1, $body['total']);
    }

    #[Test]
    public function returnsFilteredExportsByFormat(): void
    {
        $this->createReportExportWithFile(format: 'csv', id: self::FIXTURE_REPORT_EXPORT_ID_1);

        $this->authenticatedRequest('GET', self::BASE_URL . '?format=csv');

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $body = $this->getResponseJson();
        self::assertSame(1, $body['total']);
    }

    #[Test]
    public function returnsUnauthorizedWithoutToken(): void
    {
        $this->client->request('GET', self::BASE_URL);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
