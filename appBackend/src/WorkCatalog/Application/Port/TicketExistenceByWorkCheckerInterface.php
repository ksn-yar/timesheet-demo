<?php

declare(strict_types=1);

namespace App\WorkCatalog\Application\Port;

use App\WorkCatalog\Domain\ValueObject\WorkId;

/** Контракт проверки наличия тикетов, привязанных к виду работ. */
interface TicketExistenceByWorkCheckerInterface
{
    public function hasTicketsForWork(WorkId $workId): bool;
}
