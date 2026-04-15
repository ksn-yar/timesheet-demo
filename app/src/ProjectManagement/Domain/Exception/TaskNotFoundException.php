<?php

declare(strict_types=1);

namespace App\ProjectManagement\Domain\Exception;

use DomainException;

/** Задача не найдена по указанному идентификатору. */
final class TaskNotFoundException extends DomainException
{
    public function __construct(string $id)
    {
        parent::__construct("Задача с идентификатором «{$id}» не найдена.");
    }
}
