<?php

declare(strict_types=1);

namespace App\Reporting\Domain\Entity;

use App\Reporting\Domain\Event\ReportAdded;
use App\Reporting\Domain\Trait\RecordsDomainEventsTrait;
use App\Reporting\Domain\ValueObject\ReportData;
use App\Reporting\Domain\ValueObject\ReportFilters;
use App\Reporting\Domain\ValueObject\ReportGroupBy;
use App\Reporting\Domain\ValueObject\ReportId;
use App\Reporting\Domain\ValueObject\ReportPeriod;
use DateTimeImmutable;
use InvalidArgumentException;

/** Агрегат отчёта. Неизменяем после создания: все изменения проходят через новый отчёт. */
final class Report
{
    use RecordsDomainEventsTrait;

    private ReportId $id;
    private string $name;
    private string $createdBy;
    private DateTimeImmutable $createdAt;
    private ReportPeriod $period;
    private ReportFilters $filters;
    private ReportGroupBy $groupBy;
    private ReportData $data;

    private function __construct() {}

    public static function create(
        ReportId $id,
        string $name,
        string $createdBy,
        ReportPeriod $period,
        ReportFilters $filters,
        ReportGroupBy $groupBy,
        ReportData $data,
    ): self {
        if ('' === trim($name)) {
            throw new InvalidArgumentException('Название отчёта не может быть пустым.');
        }

        $report = new self();
        $report->id = $id;
        $report->name = $name;
        $report->createdBy = $createdBy;
        $report->createdAt = new DateTimeImmutable();
        $report->period = $period;
        $report->filters = $filters;
        $report->groupBy = $groupBy;
        $report->data = $data;

        $report->recordEvent(new ReportAdded(
            $report->id,
            $report->createdBy,
            $report->period->from(),
            $report->period->to(),
        ));

        return $report;
    }

    /** Восстанавливает отчёт из хранилища без валидации и без генерации событий. */
    public static function restore(
        string $id,
        string $name,
        string $createdBy,
        DateTimeImmutable $createdAt,
        ReportPeriod $period,
        ReportFilters $filters,
        ReportGroupBy $groupBy,
        ReportData $data,
    ): self {
        $report = new self();
        $report->id = new ReportId($id);
        $report->name = $name;
        $report->createdBy = $createdBy;
        $report->createdAt = $createdAt;
        $report->period = $period;
        $report->filters = $filters;
        $report->groupBy = $groupBy;
        $report->data = $data;

        return $report;
    }

    public function getId(): ReportId
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCreatedBy(): string
    {
        return $this->createdBy;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getPeriod(): ReportPeriod
    {
        return $this->period;
    }

    public function getFilters(): ReportFilters
    {
        return $this->filters;
    }

    public function getGroupBy(): ReportGroupBy
    {
        return $this->groupBy;
    }

    public function getData(): ReportData
    {
        return $this->data;
    }
}
