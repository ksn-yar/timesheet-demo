<?php

declare(strict_types=1);

namespace App\Reporting\Domain\ValueObject;

use App\Reporting\Domain\Enum\ExportFormat;
use DateTimeImmutable;

/** Результат экспорта набора отчётов. Хранит ссылку на сгенерированный файл и метаданные выгрузки. */
final readonly class ReportExport
{
    /** @param string[] $reportIds */
    public function __construct(
        private ReportExportId $id,
        private array $reportIds,
        private ExportFormat $format,
        private DateTimeImmutable $generatedAt,
        private string $fileRef,
    ) {}

    public function id(): ReportExportId
    {
        return $this->id;
    }

    /** @return string[] */
    public function reportIds(): array
    {
        return $this->reportIds;
    }

    public function format(): ExportFormat
    {
        return $this->format;
    }

    public function generatedAt(): DateTimeImmutable
    {
        return $this->generatedAt;
    }

    public function fileRef(): string
    {
        return $this->fileRef;
    }
}
