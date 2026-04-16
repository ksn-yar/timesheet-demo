<?php

declare(strict_types=1);

namespace App\Persistence\Repository;

use App\Persistence\Entity\ReportExport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Doctrine-репозиторий для работы с выгрузками отчётов.
 * Поддерживает фильтрацию по формату и дате генерации с пагинацией.
 *
 * @extends ServiceEntityRepository<ReportExport>
 *
 * @method null|ReportExport find($id, $lockMode = null, $lockVersion = null)
 * @method null|ReportExport findOneBy(array $criteria, ?array $orderBy = null)
 * @method ReportExport[]    findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
 */
class ReportExportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReportExport::class);
    }

    public function save(ReportExport $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Возвращает список выгрузок с фильтрацией и пагинацией.
     *
     * @param array<string, mixed> $criteria
     *
     * @return ReportExport[]
     */
    public function findAllByCriteria(array $criteria, int $page, int $perPage): array
    {
        $qb = $this->createQueryBuilder('re');

        $this->applyCriteria($qb, $criteria);

        return $qb
            ->orderBy('re.generatedAt', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults(min($perPage, 1000))
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Подсчёт выгрузок с учётом фильтров.
     *
     * @param array<string, mixed> $criteria
     */
    public function countByCriteria(array $criteria): int
    {
        $qb = $this->createQueryBuilder('re')
            ->select('COUNT(re.id)')
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
        if (isset($criteria['format'])) {
            $qb->andWhere('re.format = :format')
                ->setParameter('format', $criteria['format'])
            ;
        }

        if (isset($criteria['generatedAtFrom'])) {
            $qb->andWhere('re.generatedAt >= :generatedAtFrom')
                ->setParameter('generatedAtFrom', $criteria['generatedAtFrom'])
            ;
        }

        if (isset($criteria['generatedAtTo'])) {
            $qb->andWhere('re.generatedAt <= :generatedAtTo')
                ->setParameter('generatedAtTo', $criteria['generatedAtTo'])
            ;
        }
    }
}
