<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Repository;

use App\Reporting\Domain\Entity\Report;
use App\Reporting\Domain\Repository\ReportRepositoryInterface;
use App\Reporting\Domain\ValueObject\ReportData;
use App\Reporting\Domain\ValueObject\ReportFilters;
use App\Reporting\Domain\ValueObject\ReportGroupBy;
use App\Reporting\Domain\ValueObject\ReportId;
use App\Reporting\Domain\ValueObject\ReportPeriod;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

/**
 * DBAL-реализация хранилища отчётов.
 * Использует нативный SQL через DBAL Connection, т.к. Persistence Entity для Report ещё не создана.
 */
final class ReportRepository implements ReportRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function save(Report $report): void
    {
        $connection = $this->entityManager->getConnection();

        $connection->executeStatement(
            <<<'SQL'
                INSERT INTO reports (id, name, created_by, created_at, period_from, period_to, filters, group_by, data)
                VALUES (:id, :name, :created_by, :created_at, :period_from, :period_to, :filters, :group_by, :data)
                ON CONFLICT (id) DO UPDATE SET
                    name        = EXCLUDED.name,
                    created_by  = EXCLUDED.created_by,
                    created_at  = EXCLUDED.created_at,
                    period_from = EXCLUDED.period_from,
                    period_to   = EXCLUDED.period_to,
                    filters     = EXCLUDED.filters,
                    group_by    = EXCLUDED.group_by,
                    data        = EXCLUDED.data
                SQL,
            [
                'id' => $report->getId()->value(),
                'name' => $report->getName(),
                'created_by' => $report->getCreatedBy(),
                'created_at' => $report->getCreatedAt()->format('Y-m-d H:i:s'),
                'period_from' => $report->getPeriod()->from()->format('Y-m-d'),
                'period_to' => $report->getPeriod()->to()->format('Y-m-d'),
                'filters' => json_encode($report->getFilters()->toArray(), \JSON_THROW_ON_ERROR),
                'group_by' => json_encode($report->getGroupBy()->toArray(), \JSON_THROW_ON_ERROR),
                'data' => json_encode($report->getData()->toArray(), \JSON_THROW_ON_ERROR),
            ],
        );
    }

    public function findById(ReportId $id): ?Report
    {
        $connection = $this->entityManager->getConnection();

        $row = $connection->fetchAssociative(
            'SELECT id, name, created_by, created_at, period_from, period_to, filters, group_by, data FROM reports WHERE id = :id',
            ['id' => $id->value()],
        );

        if (false === $row) {
            return null;
        }

        return $this->hydrateReport($row);
    }

    /** @return Report[] */
    public function findAll(array $criteria): array
    {
        $connection = $this->entityManager->getConnection();
        [$sql, $params] = $this->buildSelectQuery($criteria, includeData: false);

        $rows = $connection->fetchAllAssociative($sql, $params);

        return array_map(fn (array $row): Report => $this->hydrateReport($row), $rows);
    }

    public function count(array $criteria): int
    {
        $connection = $this->entityManager->getConnection();
        [$sql, $params] = $this->buildCountQuery($criteria);

        return (int) $connection->fetchOne($sql, $params);
    }

    /**
     * Строит SELECT-запрос с фильтрами и пагинацией.
     * При includeData=false колонка data исключается для экономии памяти при списках.
     *
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function buildSelectQuery(array $criteria, bool $includeData = true): array
    {
        $columns = $includeData
            ? 'id, name, created_by, created_at, period_from, period_to, filters, group_by, data'
            : 'id, name, created_by, created_at, period_from, period_to, filters, group_by';

        $where = [];
        $params = [];

        $this->applyFilterCriteria($criteria, $where, $params);

        $page = (int) ($criteria['page'] ?? 1);
        $perPage = (int) ($criteria['perPage'] ?? 20);
        $offset = ($page - 1) * $perPage;

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT {$columns} FROM reports {$whereClause} ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

        $params['limit'] = $perPage;
        $params['offset'] = $offset;

        return [$sql, $params];
    }

    /** @return array{0: string, 1: array<string, mixed>} */
    private function buildCountQuery(array $criteria): array
    {
        $where = [];
        $params = [];

        $this->applyFilterCriteria($criteria, $where, $params);

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT COUNT(*) FROM reports {$whereClause}";

        return [$sql, $params];
    }

    /** Применяет поддерживаемые критерии фильтрации к массивам WHERE и params. */
    private function applyFilterCriteria(array $criteria, array &$where, array &$params): void
    {
        if (isset($criteria['createdBy'])) {
            $where[] = 'created_by = :createdBy';
            $params['createdBy'] = $criteria['createdBy'];
        }

        if (isset($criteria['periodFrom'])) {
            $where[] = 'period_from >= :periodFrom';
            $params['periodFrom'] = $criteria['periodFrom'];
        }

        if (isset($criteria['periodTo'])) {
            $where[] = 'period_to <= :periodTo';
            $params['periodTo'] = $criteria['periodTo'];
        }

        if (isset($criteria['name'])) {
            $where[] = 'name ILIKE :name';
            $params['name'] = '%' . $criteria['name'] . '%';
        }
    }

    /** Восстанавливает доменную сущность Report из строки DBAL. */
    private function hydrateReport(array $row): Report
    {
        $filters = ReportFilters::fromArray(
            json_decode($row['filters'], true, 512, \JSON_THROW_ON_ERROR) ?? [],
        );

        $groupBy = ReportGroupBy::fromArray(
            json_decode($row['group_by'], true, 512, \JSON_THROW_ON_ERROR) ?? [],
        );

        $data = ReportData::fromArray(
            isset($row['data'])
                ? json_decode($row['data'], true, 512, \JSON_THROW_ON_ERROR) ?? []
                : [],
        );

        return Report::restore(
            id: $row['id'],
            name: $row['name'],
            createdBy: $row['created_by'],
            createdAt: new DateTimeImmutable($row['created_at']),
            period: new ReportPeriod(
                new DateTimeImmutable($row['period_from']),
                new DateTimeImmutable($row['period_to']),
            ),
            filters: $filters,
            groupBy: $groupBy,
            data: $data,
        );
    }
}
