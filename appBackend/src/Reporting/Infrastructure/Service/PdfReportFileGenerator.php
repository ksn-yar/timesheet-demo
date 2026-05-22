<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Service;

use App\Reporting\Application\Port\ReportFileGeneratorInterface;
use App\Reporting\Domain\Entity\Report;
use App\Reporting\Domain\Enum\ExportFormat;
use App\Reporting\Domain\Exception\ReportExportGenerationException;
use Dompdf\Dompdf;
use Symfony\Component\Uid\Uuid;
use Throwable;

/** Генератор файлов экспорта отчётов в формате PDF через Dompdf. */
final readonly class PdfReportFileGenerator implements ReportFileGeneratorInterface
{
    /** @param Report[] $reports */
    public function generate(array $reports, ExportFormat $format): string
    {
        // todo resolve me
        $fileRef = 'var/exports/export_' . Uuid::v4()->toRfc4122() . '.pdf';
        $projectRoot = \dirname(__DIR__, 4);
        $absolutePath = $projectRoot . '/' . $fileRef;

        $this->ensureDirectoryExists(\dirname($absolutePath));

        try {
            $html = $this->buildHtml($reports);

            $dompdf = new Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();

            file_put_contents($absolutePath, $dompdf->output());
        } catch (Throwable $e) {
            throw new ReportExportGenerationException(
                "Не удалось сгенерировать PDF-файл: {$e->getMessage()}",
                $e,
            );
        }

        return $fileRef;
    }

    /**
     * Формирует HTML-таблицу для рендеринга в PDF.
     *
     * @param Report[] $reports
     */
    private function buildHtml(array $reports): string
    {
        $html = '<html lang="ru"><head><meta charset="UTF-8"><style>';
        $html .= 'body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }';
        $html .= 'table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }';
        $html .= 'th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; }';
        $html .= 'th { background-color: #f0f0f0; font-weight: bold; }';
        $html .= 'h2 { font-size: 12px; margin-bottom: 5px; }';
        $html .= '</style></head><body>';

        foreach ($reports as $report) {
            $rows = $report->getData()->rows();
            $html .= '<h2>' . htmlspecialchars($report->getName(), \ENT_QUOTES) . '</h2>';

            if (empty($rows)) {
                $html .= '<p>Нет данных.</p>';

                continue;
            }

            $headers = array_keys($rows[0]);
            $html .= '<table><thead><tr>';

            foreach ($headers as $header) {
                $html .= '<th>' . htmlspecialchars((string) $header, \ENT_QUOTES) . '</th>';
            }

            $html .= '</tr></thead><tbody>';

            foreach ($rows as $row) {
                $html .= '<tr>';
                foreach ($row as $cell) {
                    $html .= '<td>' . htmlspecialchars((string) $cell, \ENT_QUOTES) . '</td>';
                }
                $html .= '</tr>';
            }

            $html .= '</tbody></table>';
        }

        $html .= '</body></html>';

        return $html;
    }

    private function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory) && !mkdir($directory, 0o755, true) && !is_dir($directory)) {
            throw new ReportExportGenerationException("Не удалось создать директорию: {$directory}");
        }
    }
}
