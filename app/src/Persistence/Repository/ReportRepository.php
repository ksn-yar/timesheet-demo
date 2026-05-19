<?php

declare(strict_types=1);

namespace App\Persistence\Repository;

use App\Persistence\Entity\Report;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Doctrine-репозиторий для работы с отчётами.
 * Предоставляет методы частичной выборки (без поля data) для оптимизации памяти при формировании списков.
 *
 * @extends ServiceEntityRepository<Report>
 *
 * @method null|Report find($id, $lockMode = null, $lockVersion = null)
 * @method null|Report findOneBy(array $criteria, ?array $orderBy = null)
 * @method Report[]    findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
 */
class ReportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Report::class);
    }

    public function save(Report $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Возвращает список метаданных отчётов без поля data для экономии памяти.
     * Использует скалярный SELECT и возвращает массивы вместо Entity-объектов.
     *
     * @param array<string, mixed> $criteria
     *
     * @return array<int, array<string, mixed>>
     */
    public function findAllMeta(array $criteria, int $page, int $perPage): array
    {
        $qb = $this->createQueryBuilder('r')
            ->select(
                'r.id',
                'r.name',
                'r.createdBy',
                'r.createdAt',
                'r.periodFrom',
                'r.periodTo',
                'r.filters',
                'r.groupBy',
            )
        ;

        $this->applyCriteria($qb, $criteria);

        return $qb
            ->orderBy('r.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults(min($perPage, 1000))
            ->getQuery()
            ->getArrayResult()
        ;
    }

    /**
     * Подсчёт отчётов с учётом фильтров.
     *
     * @param array<string, mixed> $criteria
     */
    public function countByCriteria(array $criteria): int
    {
        $qb = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
        ;

        $this->applyCriteria($qb, $criteria);

        /** @var int|string $result */
        $result = $qb->getQuery()->getSingleScalarResult();

        return (int) $result;
    }

    /**
     * Применяет поддерживаемые критерии фильтрации к QueryBuilder.
     *
     * @param array<string, mixed> $criteria
     */
    private function applyCriteria(QueryBuilder $qb, array $criteria): void
    {
        if (isset($criteria['createdBy'])) {
            $qb->andWhere('r.createdBy = :createdBy')
                ->setParameter('createdBy', $criteria['createdBy'])
            ;
        }

        if (isset($criteria['periodFrom'])) {
            $qb->andWhere('r.periodFrom >= :periodFrom')
                ->setParameter('periodFrom', $criteria['periodFrom'])
            ;
        }

        if (isset($criteria['periodTo'])) {
            $qb->andWhere('r.periodTo <= :periodTo')
                ->setParameter('periodTo', $criteria['periodTo'])
            ;
        }

        if (isset($criteria['name']) && \is_string($criteria['name'])) {
            $qb->andWhere('r.name LIKE :name')
                ->setParameter('name', '%' . $criteria['name'] . '%')
            ;
        }
    }
}
