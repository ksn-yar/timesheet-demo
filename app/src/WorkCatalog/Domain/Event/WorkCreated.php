<?php

declare(strict_types=1);

namespace App\WorkCatalog\Domain\Event;

use App\WorkCatalog\Domain\ValueObject\WorkId;
use DateTimeImmutable;

/** Событие создания вида работ. */
final readonly class WorkCreated
{
    public DateTimeImmutable $occurredAt;

    public function __construct(
        public WorkId $workId,
        public string $name,
    ) {
        $this->occurredAt = new DateTimeImmutable();
    }
}
