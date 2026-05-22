<?php

declare(strict_types=1);

namespace App\Reporting\Domain\Exception;

use DomainException;

/** Отчёт не найден по указанному идентификатору. */
final class ReportNotFoundException extends DomainException
{
    public function __construct(string $id)
    {
        parent::__construct("Отчёт с ID {$id} не найден.");
    }
}
