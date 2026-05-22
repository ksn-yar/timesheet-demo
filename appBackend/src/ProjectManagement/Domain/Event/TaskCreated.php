<?php

declare(strict_types=1);

namespace App\ProjectManagement\Domain\Event;

use App\ProjectManagement\Domain\ValueObject\ChangeRequestId;
use App\ProjectManagement\Domain\ValueObject\ProjectId;
use App\ProjectManagement\Domain\ValueObject\TaskId;
use DateTimeImmutable;

/** Событие создания задачи. */
final readonly class TaskCreated
{
    public DateTimeImmutable $occurredAt;

    public function __construct(
        public TaskId $taskId,
        public ?ProjectId $projectId,
        public ?ChangeRequestId $crId,
        public string $name,
    ) {
        $this->occurredAt = new DateTimeImmutable();
    }
}
