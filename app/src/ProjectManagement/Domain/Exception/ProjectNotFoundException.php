<?php

declare(strict_types=1);

namespace App\ProjectManagement\Domain\Exception;

use DomainException;

/** Проект не найден по указанному идентификатору. */
final class ProjectNotFoundException extends DomainException
{
    public function __construct(string $id)
    {
        parent::__construct("Проект с идентификатором «{$id}» не найден.");
    }
}
