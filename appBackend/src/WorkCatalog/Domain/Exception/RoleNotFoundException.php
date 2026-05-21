<?php

declare(strict_types=1);

namespace App\WorkCatalog\Domain\Exception;

use DomainException;

/** Роль не найдена по указанному идентификатору. */
final class RoleNotFoundException extends DomainException
{
    public function __construct(string $id)
    {
        parent::__construct("Роль с идентификатором «{$id}» не найдена.");
    }
}
