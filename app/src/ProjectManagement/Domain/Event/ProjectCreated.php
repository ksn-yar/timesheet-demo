<?php

declare(strict_types=1);

namespace App\ProjectManagement\Domain\Event;

use App\ProjectManagement\Domain\Enum\ProjectStatus;
use App\ProjectManagement\Domain\ValueObject\ClientId;
use App\ProjectManagement\Domain\ValueObject\ProjectId;
use DateTimeImmutable;

/** Событие создания проекта. */
final readonly class ProjectCreated
{
    public DateTimeImmutable $occurredAt;

    public function __construct(
        public ProjectId $projectId,
        public ClientId $clientId,
        public string $name,
        public ProjectStatus $status,
    ) {
        $this->occurredAt = new DateTimeImmutable();
    }
}
