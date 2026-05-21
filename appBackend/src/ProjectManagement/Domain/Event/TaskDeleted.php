<?php

declare(strict_types=1);

namespace App\ProjectManagement\Domain\Event;

use App\ProjectManagement\Domain\ValueObject\TaskId;
use DateTimeImmutable;

/** Событие удаления задачи. */
final readonly class TaskDeleted
{
    public DateTimeImmutable $occurredAt;

    public function __construct(
        public TaskId $taskId,
    ) {
        $this->occurredAt = new DateTimeImmutable();
    }
}
