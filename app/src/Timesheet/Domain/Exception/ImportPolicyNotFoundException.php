<?php

declare(strict_types=1);

namespace App\Timesheet\Domain\Exception;

use DomainException;

/** Политика импорта не найдена по указанному идентификатору. */
final class ImportPolicyNotFoundException extends DomainException
{
    public function __construct(string $id)
    {
        parent::__construct("Политика импорта с идентификатором «{$id}» не найдена.");
    }
}
