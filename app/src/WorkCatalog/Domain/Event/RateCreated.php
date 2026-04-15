<?php

declare(strict_types=1);

namespace App\WorkCatalog\Domain\Event;

use App\WorkCatalog\Domain\ValueObject\RateId;
use DateTimeImmutable;

/** Событие создания ставки. */
final readonly class RateCreated
{
    public DateTimeImmutable $occurredAt;

    public function __construct(
        public RateId $rateId,
        public string $amount,
        public string $currency,
        public string $effectiveFrom,
        public ?string $roleId,
        public ?string $workId,
    ) {
        $this->occurredAt = new DateTimeImmutable();
    }
}
