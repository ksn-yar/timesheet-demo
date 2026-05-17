<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Service;

use App\Reporting\Application\Port\ReportFileGeneratorInterface;
use App\Reporting\Domain\Entity\Report;
use App\Reporting\Domain\Enum\ExportFormat;
use App\Reporting\Domain\Exception\ReportExportGenerationException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\Uid\Uuid;
use Throwable;

/** Генератор файлов экспорта отчётов в формате XLSX. */
final readonly class XlsxReportFileGenerator implements ReportFileGeneratorInterface
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

        $fileRef = 'var/exports/export_' . Uuid::v4()->toRfc4122() . '.xlsx';
        $projectRoot = \dirname(__DIR__, 5);
        $absolutePath = $projectRoot . '/' . $fileRef;

        $this->ensureDirectoryExists(\dirname($absolutePath));

        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Записываем заголовки колонок из ключей первой строки
            if (!empty($rows)) {
                $headers = array_keys($rows[0]);
                $sheet->fromArray([$headers], null, 'A1');

                // Записываем данные начиная со второй строки
                foreach ($rows as $rowIndex => $row) {
                    $sheet->fromArray([array_values($row)], null, 'A' . ($rowIndex + 2));
                }
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save($absolutePath);
        } catch (Throwable $e) {
            throw new ReportExportGenerationException(
                "Не удалось сгенерировать XLSX-файл: {$e->getMessage()}",
                $e,
            );
        }

        return $fileRef;
    }

    private function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory) && !mkdir($directory, 0o755, true) && !is_dir($directory)) {
            throw new ReportExportGenerationException("Не удалось создать директорию: {$directory}");
        }
    }
}
