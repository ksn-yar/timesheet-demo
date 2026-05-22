<?php

declare(strict_types=1);

namespace App\ProjectManagement\Domain\Event;

use App\ProjectManagement\Domain\ValueObject\ClientId;
use DateTimeImmutable;

/** Событие создания клиента. */
final readonly class ClientCreated
{
    public DateTimeImmutable $occurredAt;

    public function __construct(
        public ClientId $clientId,
        public string $name,
    ) {
        $this->occurredAt = new DateTimeImmutable();
    }
}
