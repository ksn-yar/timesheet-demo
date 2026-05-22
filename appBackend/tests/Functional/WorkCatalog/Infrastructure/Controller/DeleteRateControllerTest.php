<?php

declare(strict_types=1);

namespace App\Tests\Functional\WorkCatalog\Infrastructure\Controller;

use App\Persistence\Entity\Rate as RateOrmEntity;
use App\Tests\Functional\FunctionalTestCase;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

/**
 * Функциональные тесты контроллера удаления ставки.
 * Проверяет мягкое удаление, 404 и 422 при попытке удалить уже удалённую ставку.
 *
 * @internal
 *
 * @coversNothing
 */
class DeleteRateControllerTest extends FunctionalTestCase
{
    private const string BASE_URL = '/api/work-catalog/rates/';

    #[Test]
    public function returnsNoContentOnSuccess(): void
    {
        $rateId = $this->createRate();

        $this->authenticatedRequest('DELETE', self::BASE_URL . $rateId);

        self::assertSame(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function returnsNotFoundWhenRateDoesNotExist(): void
    {
        $this->authenticatedRequest('DELETE', self::BASE_URL . 'f0000000-0000-4000-a000-000000000001');

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsUnprocessableWhenRateAlreadyDeleted(): void
    {
        $em = $this->getEntityManager();
        $rateId = $this->createRate();

        $rate = $em->find(RateOrmEntity::class, $rateId);
        \assert(null !== $rate);
        $rate->setDeletedAt(new DateTimeImmutable());
        $em->flush();

        $this->authenticatedRequest('DELETE', self::BASE_URL . $rateId);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
        $body = $this->getResponseJson();
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsUnauthorizedWithoutToken(): void
    {
        $rateId = $this->createRate();

        $this->client->request('DELETE', self::BASE_URL . $rateId);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }
}
