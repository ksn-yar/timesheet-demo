<?php

declare(strict_types=1);

namespace App\Tests\Functional\Timesheet\Infrastructure\Http\Controller;

use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

/**
 * Функциональные тесты контроллера списка политик импорта тикетов.
 *
 * @internal
 *
 * @coversNothing
 */
class ListImportPoliciesControllerTest extends FunctionalTestCase
{
    private const string BASE_URL = '/api/timesheet/import-policies';

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
    public function returnsPoliciesWhenTheyExist(): void
    {
        $this->createImportPolicy('Jira импорт', 'jira', false, true, self::FIXTURE_IMPORT_POLICY_ID_1);
        $this->createImportPolicy('GitLab импорт', 'gitlab', true, false, self::FIXTURE_IMPORT_POLICY_ID_2);

        $this->authenticatedRequest('GET', self::BASE_URL);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $body = $this->getResponseJson();
        self::assertCount(2, $body['items']);
        self::assertSame(2, $body['total']);
    }

    #[Test]
    public function returnsUnauthorizedWithoutToken(): void
    {
        $this->client->request('GET', self::BASE_URL);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
