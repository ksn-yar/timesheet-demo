<?php

declare(strict_types=1);

namespace App\WorkCatalog\Infrastructure\Repository;

use App\Persistence\Repository\TicketRepository as TicketOrmRepository;
use App\WorkCatalog\Domain\ValueObject\RateId;
use App\WorkCatalog\Domain\ValueObject\WorkId;

/** Реализация проверок тикетов для WorkCatalog через Persistence-репозиторий. */
final class TicketRepository
{
    public function __construct(
        private readonly TicketOrmRepository $ormRepository,
    ) {}

    public function existsByRateId(RateId $rateId): bool
    {
        return $this->ormRepository->existsByRateId($rateId->value());
    }

    public function existsByWorkId(WorkId $workId): bool
    {
        return $this->ormRepository->existsByWorkId($workId->value());
    }
}
