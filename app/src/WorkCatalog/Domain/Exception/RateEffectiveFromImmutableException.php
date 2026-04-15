<?php

declare(strict_types=1);

namespace App\WorkCatalog\Domain\Exception;

use DomainException;

/** Невозможно изменить дату начала действия ставки, применённой к тикету. */
final class RateEffectiveFromImmutableException extends DomainException
{
    public function __construct(string $rateId)
    {
        parent::__construct("Невозможно изменить effectiveFrom ставки «{$rateId}»: ставка применена к тикету.");
    }
}
