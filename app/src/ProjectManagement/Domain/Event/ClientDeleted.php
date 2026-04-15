<?php

declare(strict_types=1);

namespace App\ProjectManagement\Domain\Event;

use App\ProjectManagement\Domain\ValueObject\ClientId;
use DateTimeImmutable;

/** Событие удаления клиента. */
final readonly class ClientDeleted
{
    public DateTimeImmutable $occurredAt;

    public function __construct(
        public ClientId $clientId,
    ) {
        $this->occurredAt = new DateTimeImmutable();
    }
}
