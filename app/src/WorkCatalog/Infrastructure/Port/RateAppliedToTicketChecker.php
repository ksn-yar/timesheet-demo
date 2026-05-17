<?php

declare(strict_types=1);

namespace App\WorkCatalog\Infrastructure\Port;

use App\WorkCatalog\Application\Port\RateAppliedToTicketCheckerInterface;
use App\WorkCatalog\Domain\ValueObject\RateId;
use App\WorkCatalog\Infrastructure\Repository\TicketRepository;

/** Проверяет, применена ли ставка хотя бы к одному тикету. */
final readonly class RateAppliedToTicketChecker implements RateAppliedToTicketCheckerInterface
{
    public function __construct(
        private TicketRepository $ticketRepository,
    ) {}

    public function isRateAppliedToTicket(RateId $rateId): bool
    {
        return $this->ticketRepository->existsByRateId($rateId);
    }
}
