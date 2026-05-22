<?php

declare(strict_types=1);

namespace App\Tests\Functional\WorkCatalog\Infrastructure\Controller;

use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

/**
 * Функциональные тесты контроллера создания роли.
 * Проверяет успешное создание, валидацию и дублирование имени.
 *
 * @internal
 *
 * @coversNothing
 */
class CreateRoleControllerTest extends FunctionalTestCase
{
    private const string BASE_URL = '/api/work-catalog/roles';

    #[Test]
    public function returnsCreatedOnSuccess(): void
    {
        $this->authenticatedRequest('POST', self::BASE_URL, [
            'name' => 'Разработчик',
        ]);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsCreatedWithOptionalDescription(): void
    {
        $this->authenticatedRequest('POST', self::BASE_URL, [
            'name' => 'Аналитик',
            'description' => 'Бизнес-аналитик',
        ]);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsBadRequestWhenNameIsBlank(): void
    {
        $this->authenticatedRequest('POST', self::BASE_URL, [
            'name' => '',
        ]);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsBadRequestWhenNameIsMissing(): void
    {
        $this->authenticatedRequest('POST', self::BASE_URL, []);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsUnprocessableWhenDuplicateName(): void
    {
        $this->createRole('Разработчик');

        $this->authenticatedRequest('POST', self::BASE_URL, [
            'name' => 'Разработчик',
        ]);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsUnauthorizedWithoutToken(): void
    {
        $this->client->request('POST', self::BASE_URL);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
