<?php

declare(strict_types=1);

namespace App\ProjectManagement\Domain\Event;

use App\ProjectManagement\Domain\ValueObject\ProjectId;
use DateTimeImmutable;

/** Событие обновления проекта. */
final readonly class ProjectUpdated
{
    public DateTimeImmutable $occurredAt;

    public function __construct(
        public ProjectId $projectId,
    ) {
        $this->occurredAt = new DateTimeImmutable();
    }
}
