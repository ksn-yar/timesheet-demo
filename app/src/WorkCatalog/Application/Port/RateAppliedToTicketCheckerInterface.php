<?php

declare(strict_types=1);

namespace App\WorkCatalog\Application\Port;

use App\WorkCatalog\Domain\ValueObject\RateId;

/** Контракт проверки, применена ли ставка хотя бы к одному тикету. */
interface RateAppliedToTicketCheckerInterface
{
    public function isRateAppliedToTicket(RateId $rateId): bool;
}
