<?php

declare(strict_types=1);

namespace App\Tests\Functional\Timesheet\Infrastructure\Http\Controller;

use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

/**
 * Функциональные тесты контроллера обновления политики импорта тикетов.
 *
 * @internal
 *
 * @coversNothing
 */
class UpdateImportPolicyControllerTest extends FunctionalTestCase
{
    private const string BASE_URL = '/api/timesheet/import-policies';

    #[Test]
    public function returnsNoContentOnSuccess(): void
    {
        $policyId = $this->createImportPolicy();

        $this->authenticatedRequest('PUT', self::BASE_URL . '/' . $policyId, [
            'isActive' => true,
        ]);

        self::assertSame(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsNoContentWhenUpdatingMappingRules(): void
    {
        $policyId = $this->createImportPolicy();

        $this->authenticatedRequest('PUT', self::BASE_URL . '/' . $policyId, [
            'mappingRules' => ['taskId' => 'new_key', 'hours' => 'time_spent'],
            'allowEdit' => true,
        ]);

        self::assertSame(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsNotFoundForMissingPolicy(): void
    {
        $this->authenticatedRequest('PUT', self::BASE_URL . '/aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee', [
            'isActive' => true,
        ]);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsUnauthorizedWithoutToken(): void
    {
        $this->client->request('PUT', self::BASE_URL . '/aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee');

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
