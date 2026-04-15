<?php

declare(strict_types=1);

namespace App\WorkCatalog\Domain\Event;

use App\WorkCatalog\Domain\ValueObject\WorkId;
use DateTimeImmutable;

/** Событие удаления вида работ. */
final readonly class WorkDeleted
{
    public DateTimeImmutable $occurredAt;

    public function __construct(
        public WorkId $workId,
    ) {
        $this->occurredAt = new DateTimeImmutable();
    }
}
