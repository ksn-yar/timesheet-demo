<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Service;

use App\Reporting\Application\Port\ReportFileGeneratorInterface;
use App\Reporting\Domain\Enum\ExportFormat;

/**
 * Фабрика генераторов файлов экспорта отчётов.
 * Делегирует генерацию конкретному генератору в зависимости от запрошенного формата.
 */
final readonly class ReportFileGeneratorFactory implements ReportFileGeneratorInterface
{
    public function __construct(
        private CsvReportFileGenerator $csvGenerator,
        private XlsxReportFileGenerator $xlsxGenerator,
        private PdfReportFileGenerator $pdfGenerator,
    ) {}

    public function generate(array $reports, ExportFormat $format): string
    {
        $generator = match ($format) {
            ExportFormat::Csv => $this->csvGenerator,
            ExportFormat::Xlsx => $this->xlsxGenerator,
            ExportFormat::Pdf => $this->pdfGenerator,
        };

        return $generator->generate($reports, $format);
    }
}
