<?php

declare(strict_types=1);

namespace App\Reporting\Domain\Exception;

use DomainException;
use Throwable;

/** Ошибка генерации файла экспорта отчёта. Оборачивает технические исключения инфраструктурного слоя. */
final class ReportExportGenerationException extends DomainException
{
    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
