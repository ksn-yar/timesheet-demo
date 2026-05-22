<?php

declare(strict_types=1);

namespace App\Tests\Functional\Reporting\Infrastructure\Controller;

use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

/**
 * Функциональные тесты контроллера получения списка отчётов.
 *
 * @internal
 *
 * @coversNothing
 */
class ListReportsControllerTest extends FunctionalTestCase
{
    private const string BASE_URL = '/api/reporting/reports';

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
        $this->authenticatedRequest('GET', self::BASE_URL . '?page=2&perPage=5');

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $body = $this->getResponseJson();
        self::assertArrayHasKey('items', $body);
        self::assertArrayHasKey('total', $body);
        self::assertArrayHasKey('page', $body);
        self::assertArrayHasKey('perPage', $body);
        self::assertSame(2, $body['page']);
        self::assertSame(5, $body['perPage']);
    }

    #[Test]
    public function returnsReportsWhenTheyExist(): void
    {
        $this->createReport('Отчёт 1', id: self::FIXTURE_REPORT_ID_1);
        $this->createReport('Отчёт 2', id: self::FIXTURE_REPORT_ID_2);

        $this->authenticatedRequest('GET', self::BASE_URL);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $body = $this->getResponseJson();
        self::assertCount(2, $body['items']);
        self::assertSame(2, $body['total']);
    }

    #[Test]
    public function returnsFilteredReportsByCreatedBy(): void
    {
        $this->createReport('Мой отчёт', id: self::FIXTURE_REPORT_ID_1);

        $this->authenticatedRequest('GET', self::BASE_URL . '?createdBy=' . self::AUTH_USER_ID);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $body = $this->getResponseJson();
        self::assertSame(1, $body['total']);
        self::assertSame('Мой отчёт', $body['items'][0]['name']);
    }

    #[Test]
    public function returnsUnauthorizedWithoutToken(): void
    {
        $this->client->request('GET', self::BASE_URL);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
