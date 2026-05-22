<?php

declare(strict_types=1);

namespace App\Tests\Functional\Identity\Infrastructure\Controller;

use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

/**
 * Функциональные тесты контроллера создания группы.
 * Проверяет валидацию DTO, дублирование имени и требование аутентификации.
 *
 * @internal
 *
 * @coversNothing
 */
class CreateGroupControllerTest extends FunctionalTestCase
{
    private const string URL = '/api/identity/groups';

    #[Test]
    public function returnsCreatedOnSuccess(): void
    {
        $this->authenticatedRequest('POST', self::URL, [
            'name' => 'Разработка',
        ]);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsCreatedWithDescription(): void
    {
        $this->authenticatedRequest('POST', self::URL, [
            'name' => 'Дизайн',
            'description' => 'Команда дизайнеров',
        ]);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsBadRequestWhenNameMissing(): void
    {
        $this->authenticatedRequest('POST', self::URL, [
            'description' => 'Описание без имени',
        ]);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsBadRequestWhenNameIsBlankString(): void
    {
        $this->authenticatedRequest('POST', self::URL, [
            'name' => '',
        ]);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsUnprocessableWhenDuplicateName(): void
    {
        $this->createGroup('Разработка', self::FIXTURE_GROUP_ID_2);

        $this->authenticatedRequest('POST', self::URL, [
            'name' => 'Разработка',
        ]);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsUnauthorizedWithoutToken(): void
    {
        $this->client->request('POST', self::URL, [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['name' => 'Разработка'], \JSON_THROW_ON_ERROR));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
