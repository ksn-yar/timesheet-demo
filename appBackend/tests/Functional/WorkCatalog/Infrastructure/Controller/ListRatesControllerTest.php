<?php

declare(strict_types=1);

namespace App\Tests\Functional\WorkCatalog\Infrastructure\Controller;

use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

/**
 * Функциональные тесты контроллера получения списка ставок.
 * Проверяет структуру ответа, данные и фильтрацию по roleId/workId.
 *
 * @internal
 *
 * @coversNothing
 */
class ListRatesControllerTest extends FunctionalTestCase
{
    private const string BASE_URL = '/api/work-catalog/rates';

    #[Test]
    public function returnsCorrectSchema(): void
    {
        $this->authenticatedRequest('GET', self::BASE_URL);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertArrayHasKey('items', $body);
        self::assertArrayHasKey('total', $body);
        self::assertArrayHasKey('page', $body);
        self::assertArrayHasKey('perPage', $body);
    }

    #[Test]
    public function returnsCorrectRateFields(): void
    {
        $roleId = $this->createRole('Разработчик');
        $this->createRate('1500.00', 'RUB', '2026-01-01', $roleId);

        $this->authenticatedRequest('GET', self::BASE_URL);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertCount(1, $body['items']);

        $item = $body['items'][0];
        self::assertArrayHasKey('id', $item);
        self::assertArrayHasKey('amount', $item);
        self::assertArrayHasKey('currency', $item);
        self::assertArrayHasKey('effectiveFrom', $item);
        self::assertSame('RUB', $item['currency']);
    }

    #[Test]
    public function filtersByRoleId(): void
    {
        $roleId1 = $this->createRole('Разработчик', self::FIXTURE_ROLE_ID_1);
        $roleId2 = $this->createRole('Тестировщик', self::FIXTURE_ROLE_ID_2);
        $this->createRate('1500.00', 'RUB', '2026-01-01', $roleId1, null, self::FIXTURE_RATE_ID_1);
        $this->createRate('1200.00', 'RUB', '2026-01-01', $roleId2, null, self::FIXTURE_RATE_ID_2);

        $this->authenticatedRequest('GET', self::BASE_URL . '?roleId=' . $roleId1);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertCount(1, $body['items']);
        self::assertSame('1500.00', $body['items'][0]['amount']);
    }

    #[Test]
    public function filtersByWorkId(): void
    {
        $workId1 = $this->createWork('Разработка', self::FIXTURE_WORK_ID_1);
        $workId2 = $this->createWork('Тестирование', self::FIXTURE_WORK_ID_2);
        $this->createRate('2000.00', 'RUB', '2026-01-01', null, $workId1, self::FIXTURE_RATE_ID_1);
        $this->createRate('1800.00', 'RUB', '2026-01-01', null, $workId2, self::FIXTURE_RATE_ID_2);

        $this->authenticatedRequest('GET', self::BASE_URL . '?workId=' . $workId2);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertCount(1, $body['items']);
        self::assertSame('1800.00', $body['items'][0]['amount']);
    }

    #[Test]
    public function returnsEmptyListWhenNoRates(): void
    {
        $this->authenticatedRequest('GET', self::BASE_URL);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertSame([], $body['items']);
        self::assertSame(0, $body['total']);
    }

    #[Test]
    public function returnsUnauthorizedWithoutToken(): void
    {
        $this->client->request('GET', self::BASE_URL);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
