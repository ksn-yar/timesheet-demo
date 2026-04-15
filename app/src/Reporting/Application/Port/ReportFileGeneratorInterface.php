<?php

declare(strict_types=1);

namespace App\Reporting\Application\Port;

use App\Reporting\Domain\Entity\Report;
use App\Reporting\Domain\Enum\ExportFormat;

/** Контракт генерации файла выгрузки отчётов. Возвращает путь к сформированному файлу (fileRef). */
interface ReportFileGeneratorInterface
{
    /**
     * Генерирует файл выгрузки и возвращает путь к нему (fileRef).
     *
     * @param Report[] $reports
     */
    public function generate(array $reports, ExportFormat $format): string;
}
