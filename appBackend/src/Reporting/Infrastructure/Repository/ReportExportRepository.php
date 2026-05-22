<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Repository;

use App\Persistence\Entity\ReportExport as ReportExportEntity;
use App\Persistence\Repository\ReportExportRepository as DoctrineReportExportRepository;
use App\Reporting\Domain\Enum\ExportFormat;
use App\Reporting\Domain\Repository\ReportExportRepositoryInterface;
use App\Reporting\Domain\ValueObject\ReportExport;
use App\Reporting\Domain\ValueObject\ReportExportId;

/**
 * Реализация доменного репозитория выгрузок отчётов через Doctrine ORM.
 * Выполняет маппинг между доменным Value Object ReportExport и Persistence Entity.
 */
final class ReportExportRepository implements ReportExportRepositoryInterface
{
    public function __construct(
        private readonly DoctrineReportExportRepository $doctrineRepository,
    ) {}

    public function save(ReportExport $export): void
    {
        $entity = $this->doctrineRepository->find($export->id()->value());

        if (null === $entity) {
            $entity = new ReportExportEntity();
            $entity->setId($export->id()->value());
        }

        $entity->setReportIds($export->reportIds());
        $entity->setFormat($export->format()->value);
        $entity->setGeneratedAt($export->generatedAt());
        $entity->setFileRef($export->fileRef());

        $this->doctrineRepository->save($entity);
    }

    public function findById(ReportExportId $id): ?ReportExport
    {
        $entity = $this->doctrineRepository->find($id->value());

        if (null === $entity) {
            return null;
        }

        return $this->toDomain($entity);
    }

    /**
     * @param array<string, mixed> $criteria
     *
     * @return ReportExport[]
     */
    public function findAll(array $criteria): array
    {
        $rawPage = $criteria['page'] ?? 1;
        $rawPerPage = $criteria['perPage'] ?? 20;
        $page = is_numeric($rawPage) ? (int) $rawPage : 1;
        $perPage = is_numeric($rawPerPage) ? (int) $rawPerPage : 20;

        $entities = $this->doctrineRepository->findAllByCriteria($criteria, $page, $perPage);

        return array_map(
            fn (ReportExportEntity $entity): ReportExport => $this->toDomain($entity),
            $entities,
        );
    }

    /** @param array<string, mixed> $criteria */
    public function count(array $criteria): int
    {
        return $this->doctrineRepository->countByCriteria($criteria);
    }

    /** Восстанавливает доменный Value Object ReportExport из Persistence Entity. */
    private function toDomain(ReportExportEntity $entity): ReportExport
    {
        return new ReportExport(
            id: new ReportExportId($entity->getId()),
            reportIds: $entity->getReportIds(),
            format: ExportFormat::from($entity->getFormat()),
            generatedAt: $entity->getGeneratedAt(),
            fileRef: $entity->getFileRef(),
        );
    }
}
