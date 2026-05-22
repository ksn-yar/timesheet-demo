<?php

declare(strict_types=1);

namespace App\Tests\Functional\Reporting\Infrastructure\Controller;

use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

/**
 * Функциональные тесты контроллера создания отчёта.
 *
 * @internal
 *
 * @coversNothing
 */
class CreateReportControllerTest extends FunctionalTestCase
{
    private const string BASE_URL = '/api/reporting/reports';

    #[Test]
    public function returnsCreatedOnSuccess(): void
    {
        $this->authenticatedRequest('POST', self::BASE_URL, [
            'name' => 'Отчёт за январь',
            'periodFrom' => '2026-01-01',
            'periodTo' => '2026-01-31',
            'groupBy' => ['employee'],
        ]);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsCreatedWithReportIdInBody(): void
    {
        $this->authenticatedRequest('POST', self::BASE_URL, [
            'name' => 'Отчёт за февраль',
            'periodFrom' => '2026-02-01',
            'periodTo' => '2026-02-28',
            'groupBy' => ['employee', 'project'],
        ]);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());

        $body = $this->getResponseJson();
        self::assertArrayHasKey('id', $body);
        self::assertNotEmpty($body['id']);
    }

    #[Test]
    public function returnsCreatedWithMultipleDimensions(): void
    {
        $this->authenticatedRequest('POST', self::BASE_URL, [
            'name' => 'Детальный отчёт',
            'periodFrom' => '2026-01-01',
            'periodTo' => '2026-03-31',
            'groupBy' => ['employee', 'work', 'task'],
        ]);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsBadRequestWhenNameIsBlank(): void
    {
        $this->authenticatedRequest('POST', self::BASE_URL, [
            'name' => '',
            'periodFrom' => '2026-01-01',
            'periodTo' => '2026-01-31',
            'groupBy' => ['employee'],
        ]);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsBadRequestWhenPeriodFromIsMissing(): void
    {
        $this->authenticatedRequest('POST', self::BASE_URL, [
            'name' => 'Отчёт',
            'periodTo' => '2026-01-31',
            'groupBy' => ['employee'],
        ]);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsBadRequestWhenGroupByIsEmpty(): void
    {
        $this->authenticatedRequest('POST', self::BASE_URL, [
            'name' => 'Отчёт',
            'periodFrom' => '2026-01-01',
            'periodTo' => '2026-01-31',
            'groupBy' => [],
        ]);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsBadRequestWhenGroupByHasInvalidDimension(): void
    {
        $this->authenticatedRequest('POST', self::BASE_URL, [
            'name' => 'Отчёт',
            'periodFrom' => '2026-01-01',
            'periodTo' => '2026-01-31',
            'groupBy' => ['invalid_dimension'],
        ]);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsBadRequestWhenPeriodFromIsInvalidDate(): void
    {
        $this->authenticatedRequest('POST', self::BASE_URL, [
            'name' => 'Отчёт',
            'periodFrom' => 'not-a-date',
            'periodTo' => '2026-01-31',
            'groupBy' => ['employee'],
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
