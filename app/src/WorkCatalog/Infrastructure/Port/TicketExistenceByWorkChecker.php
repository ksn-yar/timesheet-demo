<?php

declare(strict_types=1);

namespace App\WorkCatalog\Infrastructure\Port;

use App\WorkCatalog\Application\Port\TicketExistenceByWorkCheckerInterface;
use App\WorkCatalog\Domain\ValueObject\WorkId;
use App\WorkCatalog\Infrastructure\Repository\TicketRepository;

/** Проверяет наличие тикетов, привязанных к виду работ. */
final readonly class TicketExistenceByWorkChecker implements TicketExistenceByWorkCheckerInterface
{
    public function __construct(
        private TicketRepository $ticketRepository,
    ) {}

    public function hasTicketsForWork(WorkId $workId): bool
    {
        return $this->ticketRepository->existsByWorkId($workId);
    }
}
