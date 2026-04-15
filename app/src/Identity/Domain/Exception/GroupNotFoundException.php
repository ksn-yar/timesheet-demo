<?php

declare(strict_types=1);

namespace App\Identity\Domain\Exception;

use DomainException;

/** Группа не найдена по указанному идентификатору. */
final class GroupNotFoundException extends DomainException
{
    public function __construct(string $id)
    {
        parent::__construct("Группа с идентификатором «{$id}» не найдена.");
    }
}
