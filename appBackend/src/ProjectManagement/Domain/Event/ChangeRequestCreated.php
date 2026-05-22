<?php

declare(strict_types=1);

namespace App\ProjectManagement\Domain\Event;

use App\ProjectManagement\Domain\ValueObject\ChangeRequestId;
use App\ProjectManagement\Domain\ValueObject\ProjectId;
use DateTimeImmutable;

/** Событие создания запроса на изменение. */
final readonly class ChangeRequestCreated
{
    public DateTimeImmutable $occurredAt;

    public function __construct(
        public ChangeRequestId $changeRequestId,
        public ProjectId $projectId,
        public string $name,
    ) {
        $this->occurredAt = new DateTimeImmutable();
    }
}
