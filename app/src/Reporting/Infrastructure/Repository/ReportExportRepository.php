<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Repository;

use App\Reporting\Domain\Enum\ExportFormat;
use App\Reporting\Domain\Repository\ReportExportRepositoryInterface;
use App\Reporting\Domain\ValueObject\ReportExport;
use App\Reporting\Domain\ValueObject\ReportExportId;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

/**
 * DBAL-реализация хранилища выгрузок отчётов.
 * Использует нативный SQL через DBAL Connection, т.к. Persistence Entity для ReportExport ещё не создана.
 */
final class ReportExportRepository implements ReportExportRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function save(ReportExport $export): void
    {
        $connection = $this->entityManager->getConnection();

        $connection->executeStatement(
            <<<'SQL'
                INSERT INTO report_exports (id, report_ids, format, generated_at, file_ref)
                VALUES (:id, :report_ids, :format, :generated_at, :file_ref)
                ON CONFLICT (id) DO UPDATE SET
                    report_ids   = EXCLUDED.report_ids,
                    format       = EXCLUDED.format,
                    generated_at = EXCLUDED.generated_at,
                    file_ref     = EXCLUDED.file_ref
                SQL,
            [
                'id' => $export->id()->value(),
                'report_ids' => json_encode($export->reportIds(), \JSON_THROW_ON_ERROR),
                'format' => $export->format()->value,
                'generated_at' => $export->generatedAt()->format('Y-m-d H:i:s'),
                'file_ref' => $export->fileRef(),
            ],
        );
    }

    public function findById(ReportExportId $id): ?ReportExport
    {
        $connection = $this->entityManager->getConnection();

        $row = $connection->fetchAssociative(
            'SELECT id, report_ids, format, generated_at, file_ref FROM report_exports WHERE id = :id',
            ['id' => $id->value()],
        );

        if (false === $row) {
            return null;
        }

        return $this->hydrateExport($row);
    }

    /**
     * @param array<string, mixed> $criteria
     *
     * @return ReportExport[]
     */
    public function findAll(array $criteria): array
    {
        $connection = $this->entityManager->getConnection();
        [$sql, $params] = $this->buildSelectQuery($criteria);

        $rows = $connection->fetchAllAssociative($sql, $params);

        return array_map(fn (array $row): ReportExport => $this->hydrateExport($row), $rows);
    }

    /** @param array<string, mixed> $criteria */
    public function count(array $criteria): int
    {
        $connection = $this->entityManager->getConnection();
        [$sql, $params] = $this->buildCountQuery($criteria);

        /** @var int|string $result */
        $result = $connection->fetchOne($sql, $params);

        return (int) $result;
    }

    /**
     * @param array<string, mixed> $criteria
     *
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function buildSelectQuery(array $criteria): array
    {
        $where = [];
        $params = [];

        $this->applyFilterCriteria($criteria, $where, $params);

        $rawPage = $criteria['page'] ?? 1;
        $rawPerPage = $criteria['perPage'] ?? 20;
        $page = is_numeric($rawPage) ? (int) $rawPage : 1;
        $perPage = is_numeric($rawPerPage) ? (int) $rawPerPage : 20;
        $offset = ($page - 1) * $perPage;

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT id, report_ids, format, generated_at, file_ref FROM report_exports {$whereClause} ORDER BY generated_at DESC LIMIT :limit OFFSET :offset";

        $params['limit'] = $perPage;
        $params['offset'] = $offset;

        return [$sql, $params];
    }

    /**
     * @param array<string, mixed> $criteria
     *
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function buildCountQuery(array $criteria): array
    {
        $where = [];
        $params = [];

        $this->applyFilterCriteria($criteria, $where, $params);

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT COUNT(*) FROM report_exports {$whereClause}";

        return [$sql, $params];
    }

    /**
     * Применяет поддерживаемые критерии фильтрации к массивам WHERE и params.
     *
     * @param array<string, mixed> $criteria
     * @param string[]             $where
     * @param array<string, mixed> $params
     */
    private function applyFilterCriteria(array $criteria, array &$where, array &$params): void
    {
        if (isset($criteria['format'])) {
            $where[] = 'format = :format';
            $params['format'] = $criteria['format'];
        }

        if (isset($criteria['generatedAtFrom'])) {
            $where[] = 'generated_at >= :generatedAtFrom';
            $params['generatedAtFrom'] = $criteria['generatedAtFrom'];
        }

        if (isset($criteria['generatedAtTo'])) {
            $where[] = 'generated_at <= :generatedAtTo';
            $params['generatedAtTo'] = $criteria['generatedAtTo'];
        }
    }

    /**
     * Восстанавливает Value Object ReportExport из строки DBAL.
     *
     * @param array<string, mixed> $row
     */
    private function hydrateExport(array $row): ReportExport
    {
        return new ReportExport(
            id: new ReportExportId($row['id']),
            reportIds: json_decode($row['report_ids'], true, 512, \JSON_THROW_ON_ERROR),
            format: ExportFormat::from($row['format']),
            generatedAt: new DateTimeImmutable($row['generated_at']),
            fileRef: $row['file_ref'],
        );
    }
}
