<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Service;

use App\Reporting\Application\Port\ReportFileGeneratorInterface;
use App\Reporting\Domain\Entity\Report;
use App\Reporting\Domain\Enum\ExportFormat;
use App\Reporting\Domain\Exception\ReportExportGenerationException;
use Symfony\Component\Uid\Uuid;

/** Генератор файлов экспорта отчётов в формате CSV. */
final readonly class CsvReportFileGenerator implements ReportFileGeneratorInterface
{
    /** @param Report[] $reports */
    public function generate(array $reports, ExportFormat $format): string
    {
        // Собираем все строки данных из всех отчётов
        $rows = [];
        foreach ($reports as $report) {
            foreach ($report->getData()->rows() as $row) {
                $rows[] = $row;
            }
        }

        $fileRef = 'var/exports/export_' . Uuid::v4()->toRfc4122() . '.csv';
        $projectRoot = $this->resolveProjectRoot();
        $absolutePath = $projectRoot . '/' . $fileRef;

        $this->ensureDirectoryExists(\dirname($absolutePath));

        $handle = fopen($absolutePath, 'w');

        if (false === $handle) {
            throw new ReportExportGenerationException("Не удалось создать файл экспорта: {$absolutePath}");
        }

        try {
            // Записываем заголовок из ключей первой строки
            if (!empty($rows)) {
                fputcsv($handle, array_keys($rows[0]));
                foreach ($rows as $row) {
                    fputcsv($handle, array_values($row));
                }
            }
        } finally {
            fclose($handle);
        }

        return $fileRef;
    }

    private function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory) && !mkdir($directory, 0o755, true) && !is_dir($directory)) {
            throw new ReportExportGenerationException("Не удалось создать директорию: {$directory}");
        }
    }

    private function resolveProjectRoot(): string
    {
        // Поднимаемся из vendor/../.. или app/src/... до корня проекта
        return \dirname(__DIR__, 5);
    }
}
