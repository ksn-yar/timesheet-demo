<?php

declare(strict_types=1);

namespace App\Timesheet\Domain\Exception;

use DomainException;

/** Политика импорта неактивна для запуска импорта. */
final class ImportPolicyNotActiveException extends DomainException
{
    public function __construct(string $id)
    {
        parent::__construct("Политика импорта «{$id}» неактивна. Активируйте политику перед запуском импорта.");
    }
}
