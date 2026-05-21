<?php

declare(strict_types=1);

namespace App\WorkCatalog\Domain\Event;

use App\WorkCatalog\Domain\ValueObject\RoleId;
use DateTimeImmutable;

/** Событие создания роли. */
final readonly class RoleCreated
{
    public DateTimeImmutable $occurredAt;

    public function __construct(
        public RoleId $roleId,
        public string $name,
    ) {
        $this->occurredAt = new DateTimeImmutable();
    }
}
