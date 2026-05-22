<?php

declare(strict_types=1);

namespace App\Tests\Functional\ProjectManagement\Infrastructure\Controller;

use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

/**
 * Функциональные тесты контроллера создания клиента.
 * Проверяет валидацию DTO и JWT-аутентификацию.
 *
 * @internal
 *
 * @coversNothing
 */
class CreateClientControllerTest extends FunctionalTestCase
{
    private const string URL = '/api/project-management/clients';

    #[Test]
    public function returnsCreatedOnSuccess(): void
    {
        $this->authenticatedRequest('POST', self::URL, [
            'name' => 'ООО Рога и Копыта',
        ]);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsCreatedWithOptionalDescription(): void
    {
        $this->authenticatedRequest('POST', self::URL, [
            'name' => 'ООО Рога и Копыта',
            'description' => 'Тестовое описание клиента.',
        ]);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsBadRequestWhenNameMissing(): void
    {
        $this->authenticatedRequest('POST', self::URL, [
            'description' => 'Описание без названия.',
        ]);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsBadRequestWhenNameIsBlank(): void
    {
        $this->authenticatedRequest('POST', self::URL, [
            'name' => '',
        ]);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsUnauthorizedWithoutToken(): void
    {
        $this->client->request('POST', self::URL, [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['name' => 'ООО Тест'], \JSON_THROW_ON_ERROR));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
