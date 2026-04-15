<?php

declare(strict_types=1);

namespace App\Reporting\Domain\Exception;

use DomainException;

/** Выгрузка отчёта не найдена по указанному идентификатору. */
final class ReportExportNotFoundException extends DomainException
{
    public function __construct(string $id)
    {
        parent::__construct("Выгрузка с ID {$id} не найдена.");
    }
}
