<?php

declare(strict_types=1);

namespace App\WorkCatalog\Domain\Event;

use App\WorkCatalog\Domain\ValueObject\RateId;
use DateTimeImmutable;

/** Событие обновления ставки. */
final readonly class RateUpdated
{
    public DateTimeImmutable $occurredAt;

    public function __construct(
        public RateId $rateId,
    ) {
        $this->occurredAt = new DateTimeImmutable();
    }
}
