<?php

declare(strict_types=1);

namespace App\Timesheet\Domain\Exception;

use DomainException;

/** Конфликт: для данного источника уже существует активная политика импорта. */
final class ImportPolicyConflictException extends DomainException
{
    public function __construct(string $sourceSystem)
    {
        parent::__construct("Для источника «{$sourceSystem}» уже существует активная политика импорта.");
    }
}
