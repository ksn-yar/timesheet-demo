<?php

declare(strict_types=1);

namespace App\Persistence\Entity;

use App\Persistence\Repository\ReportRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/** Doctrine-сущность отчёта. Чистый ORM-класс без бизнес-логики, представляет snapshot агрегированных данных. */
#[ORM\Entity(repositoryClass: ReportRepository::class)]
#[ORM\Table(name: 'reports')]
#[ORM\Index(columns: ['created_by'], name: 'idx_reports_created_by')]
#[ORM\Index(columns: ['period_from'], name: 'idx_reports_period_from')]
#[ORM\Index(columns: ['period_to'], name: 'idx_reports_period_to')]
#[ORM\Index(columns: ['created_at'], name: 'idx_reports_created_at')]
#[ORM\Index(columns: ['name'], name: 'idx_reports_name')]
class Report
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(name: 'created_by', type: 'guid')]
    private string $createdBy;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'period_from', type: 'date_immutable')]
    private DateTimeImmutable $periodFrom;

    #[ORM\Column(name: 'period_to', type: 'date_immutable')]
    private DateTimeImmutable $periodTo;

    /** @var array<string, null|string[]> */
    #[ORM\Column(type: 'json')]
    private array $filters = [];

    /** @var string[] */
    #[ORM\Column(name: 'group_by', type: 'json')]
    private array $groupBy = [];

    /** @var array<int, array<string, mixed>> */
    #[ORM\Column(type: 'json')]
    private array $data = [];

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getCreatedBy(): string
    {
        return $this->createdBy;
    }

    public function setCreatedBy(string $createdBy): void
    {
        $this->createdBy = $createdBy;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getPeriodFrom(): DateTimeImmutable
    {
        return $this->periodFrom;
    }

    public function setPeriodFrom(DateTimeImmutable $periodFrom): void
    {
        $this->periodFrom = $periodFrom;
    }

    public function getPeriodTo(): DateTimeImmutable
    {
        return $this->periodTo;
    }

    public function setPeriodTo(DateTimeImmutable $periodTo): void
    {
        $this->periodTo = $periodTo;
    }

    /** @return array<string, null|string[]> */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /** @param array<string, null|string[]> $filters */
    public function setFilters(array $filters): void
    {
        $this->filters = $filters;
    }

    /** @return string[] */
    public function getGroupBy(): array
    {
        return $this->groupBy;
    }

    /** @param string[] $groupBy */
    public function setGroupBy(array $groupBy): void
    {
        $this->groupBy = $groupBy;
    }

    /** @return array<int, array<string, mixed>> */
    public function getData(): array
    {
        return $this->data;
    }

    /** @param array<int, array<string, mixed>> $data */
    public function setData(array $data): void
    {
        $this->data = $data;
    }
}
