<?php

declare(strict_types=1);

namespace App\WorkCatalog\Domain\Event;

use App\WorkCatalog\Domain\ValueObject\RateId;
use DateTimeImmutable;

/** Событие удаления ставки. */
final readonly class RateDeleted
{
    public DateTimeImmutable $occurredAt;

    public function __construct(
        public RateId $rateId,
    ) {
        $this->occurredAt = new DateTimeImmutable();
    }
}
