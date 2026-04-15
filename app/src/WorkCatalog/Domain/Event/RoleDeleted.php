<?php

declare(strict_types=1);

namespace App\WorkCatalog\Domain\Event;

use App\WorkCatalog\Domain\ValueObject\RoleId;
use DateTimeImmutable;

/** Событие удаления роли. */
final readonly class RoleDeleted
{
    public DateTimeImmutable $occurredAt;

    public function __construct(
        public RoleId $roleId,
    ) {
        $this->occurredAt = new DateTimeImmutable();
    }
}
