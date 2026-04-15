<?php

declare(strict_types=1);

namespace App\WorkCatalog\Domain\Exception;

use DomainException;

/** Ставка не найдена по указанному идентификатору. */
final class RateNotFoundException extends DomainException
{
    public function __construct(string $id)
    {
        parent::__construct("Ставка с идентификатором «{$id}» не найдена.");
    }
}
