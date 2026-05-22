<?php

declare(strict_types=1);

namespace App\Tests\Functional\Reporting\Infrastructure\Controller;

use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

/**
 * Функциональные тесты контроллера скачивания файла выгрузки.
 *
 * @internal
 *
 * @coversNothing
 */
class DownloadExportControllerTest extends FunctionalTestCase
{
    private const string BASE_URL = '/api/reporting/exports';

    #[Test]
    public function returnsFileContentWhenExportExists(): void
    {
        $exportId = $this->createReportExportWithFile(format: 'csv');

        $this->authenticatedRequest('GET', self::BASE_URL . '/' . $exportId . '/download');

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $contentType = $this->client->getResponse()->headers->get('Content-Type');
        self::assertStringContainsString('text/csv', (string) $contentType);
    }

    #[Test]
    public function returnsNotFoundWhenExportDoesNotExist(): void
    {
        $this->authenticatedRequest('GET', self::BASE_URL . '/aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee/download');

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsBadRequestForInvalidIdFormat(): void
    {
        $this->authenticatedRequest('GET', self::BASE_URL . '/not-a-valid-uuid/download');

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsUnauthorizedWithoutToken(): void
    {
        $this->client->request('GET', self::BASE_URL . '/aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee/download');

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
