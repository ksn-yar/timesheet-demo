<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Repository;

use App\Persistence\Entity\Report as ReportEntity;
use App\Persistence\Repository\ReportRepository as DoctrineReportRepository;
use App\Reporting\Domain\Entity\Report;
use App\Reporting\Domain\Repository\ReportRepositoryInterface;
use App\Reporting\Domain\ValueObject\ReportData;
use App\Reporting\Domain\ValueObject\ReportFilters;
use App\Reporting\Domain\ValueObject\ReportGroupBy;
use App\Reporting\Domain\ValueObject\ReportId;
use App\Reporting\Domain\ValueObject\ReportPeriod;
use DateTimeImmutable;

/**
 * Реализация доменного репозитория отчётов через Doctrine ORM.
 * Выполняет маппинг между доменной сущностью Report и Persistence Entity.
 */
final class ReportRepository implements ReportRepositoryInterface
{
    public function __construct(
        private readonly DoctrineReportRepository $doctrineRepository,
    ) {}

    public function save(Report $report): void
    {
        $entity = $this->doctrineRepository->find($report->getId()->value());

        if (null === $entity) {
            $entity = new ReportEntity();
            $entity->setId($report->getId()->value());
        }

        $entity->setName($report->getName());
        $entity->setCreatedBy($report->getCreatedBy());
        $entity->setCreatedAt($report->getCreatedAt());
        $entity->setPeriodFrom($report->getPeriod()->from());
        $entity->setPeriodTo($report->getPeriod()->to());
        $entity->setFilters($report->getFilters()->toArray());
        $entity->setGroupBy($report->getGroupBy()->toArray());
        $entity->setData($report->getData()->toArray());

        $this->doctrineRepository->save($entity);
    }

    public function findById(ReportId $id): ?Report
    {
        $entity = $this->doctrineRepository->find($id->value());

        if (null === $entity) {
            return null;
        }

        return $this->toDomain($entity);
    }

    /**
     * Возвращает список отчётов без поля data (оптимизация памяти для списков).
     *
     * @param array<string, mixed> $criteria
     *
     * @return Report[]
     */
    public function findAll(array $criteria): array
    {
        $rawPage = $criteria['page'] ?? 1;
        $rawPerPage = $criteria['perPage'] ?? 20;
        $page = is_numeric($rawPage) ? (int) $rawPage : 1;
        $perPage = is_numeric($rawPerPage) ? (int) $rawPerPage : 20;

        $rows = $this->doctrineRepository->findAllMeta($criteria, $page, $perPage);

        return array_map(fn (array $row): Report => $this->toDomainFromArray($row), $rows);
    }

    /** @param array<string, mixed> $criteria */
    public function count(array $criteria): int
    {
        return $this->doctrineRepository->countByCriteria($criteria);
    }

    /** Восстанавливает доменную сущность Report из Persistence Entity (для findById). */
    private function toDomain(ReportEntity $entity): Report
    {
        return Report::restore(
            id: $entity->getId(),
            name: $entity->getName(),
            createdBy: $entity->getCreatedBy(),
            createdAt: $entity->getCreatedAt(),
            period: new ReportPeriod(
                $entity->getPeriodFrom(),
                $entity->getPeriodTo(),
            ),
            filters: ReportFilters::fromArray($entity->getFilters()),
            groupBy: ReportGroupBy::fromArray($entity->getGroupBy()),
            data: ReportData::fromArray($entity->getData()),
        );
    }

    /**
     * Восстанавливает доменную сущность Report из массива скалярных данных (для findAllMeta).
     * Поле data отсутствует — возвращает Report с пустым ReportData.
     *
     * @param array<string, mixed> $row
     */
    private function toDomainFromArray(array $row): Report
    {
        $id = \is_string($row['id']) ? $row['id'] : '';
        $name = \is_string($row['name']) ? $row['name'] : '';
        $createdBy = \is_string($row['createdBy']) ? $row['createdBy'] : '';

        $createdAt = $row['createdAt'] instanceof DateTimeImmutable
            ? $row['createdAt']
            : new DateTimeImmutable();

        $periodFrom = $row['periodFrom'] instanceof DateTimeImmutable
            ? $row['periodFrom']
            : new DateTimeImmutable();

        $periodTo = $row['periodTo'] instanceof DateTimeImmutable
            ? $row['periodTo']
            : new DateTimeImmutable();

        /** @var array<string, null|string[]> $filtersRaw */
        $filtersRaw = \is_array($row['filters']) ? $row['filters'] : [];

        /** @var string[] $groupByRaw */
        $groupByRaw = \is_array($row['groupBy']) ? $row['groupBy'] : [];

        return Report::restore(
            id: $id,
            name: $name,
            createdBy: $createdBy,
            createdAt: $createdAt,
            period: new ReportPeriod($periodFrom, $periodTo),
            filters: ReportFilters::fromArray($filtersRaw),
            groupBy: ReportGroupBy::fromArray($groupByRaw),
            data: ReportData::fromArray([]),
        );
    }
}
