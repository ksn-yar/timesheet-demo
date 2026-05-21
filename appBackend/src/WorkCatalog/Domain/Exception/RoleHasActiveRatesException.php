<?php

declare(strict_types=1);

namespace App\WorkCatalog\Domain\Exception;

use DomainException;

/** Невозможно удалить роль, к которой привязаны активные ставки. */
final class RoleHasActiveRatesException extends DomainException
{
    public function __construct(string $roleId)
    {
        parent::__construct("Невозможно удалить роль «{$roleId}»: есть активные ставки.");
    }
}
