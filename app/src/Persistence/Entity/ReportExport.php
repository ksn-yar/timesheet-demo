<?php

declare(strict_types=1);

namespace App\Persistence\Entity;

use App\Persistence\Repository\ReportExportRepository;
use App\Reporting\Domain\Enum\ExportFormat;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/** Doctrine-сущность выгрузки отчётов. Хранит метаданные экспорта и ссылку на сгенерированный файл. */
#[ORM\Entity(repositoryClass: ReportExportRepository::class)]
#[ORM\Table(name: 'report_exports')]
#[ORM\Index(columns: ['format'], name: 'idx_report_exports_format')]
#[ORM\Index(columns: ['generated_at'], name: 'idx_report_exports_generated_at')]
class ReportExport
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    /** @var string[] */
    #[ORM\Column(name: 'report_ids', type: 'json')]
    private array $reportIds;

    #[ORM\Column(type: 'string', length: 10, enumType: ExportFormat::class)]
    private ExportFormat $format;

    #[ORM\Column(name: 'generated_at', type: 'datetime_immutable')]
    private DateTimeImmutable $generatedAt;

    #[ORM\Column(name: 'file_ref', type: 'string', length: 1024)]
    private string $fileRef;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /** @return string[] */
    public function getReportIds(): array
    {
        return $this->reportIds;
    }

    /** @param string[] $reportIds */
    public function setReportIds(array $reportIds): void
    {
        $this->reportIds = $reportIds;
    }

    public function getFormat(): ExportFormat
    {
        return $this->format;
    }

    public function setFormat(ExportFormat $format): void
    {
        $this->format = $format;
    }

    public function getGeneratedAt(): DateTimeImmutable
    {
        return $this->generatedAt;
    }

    public function setGeneratedAt(DateTimeImmutable $generatedAt): void
    {
        $this->generatedAt = $generatedAt;
    }

    public function getFileRef(): string
    {
        return $this->fileRef;
    }

    public function setFileRef(string $fileRef): void
    {
        $this->fileRef = $fileRef;
    }
}
