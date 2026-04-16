<?php

declare(strict_types=1);

namespace App\Persistence\Repository;

use App\Persistence\Entity\Rate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Репозиторий для работы со ставками через Doctrine ORM.
 *
 * @extends ServiceEntityRepository<Rate>
 *
 * @method null|Rate find($id, $lockMode = null, $lockVersion = null)
 * @method null|Rate findOneBy(array $criteria, ?array $orderBy = null)
 * @method Rate[]    findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
 */
class RateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rate::class);
    }

    public function save(Rate $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Поиск активных ставок с пагинацией и фильтрацией.
     *
     * @param array<string, mixed> $criteria
     *
     * @return Rate[]
     */
    public function findActiveAll(array $criteria, int $page, int $perPage): array
    {
        $qb = $this->createQueryBuilder('r')
            ->andWhere('r.deletedAt IS NULL')
        ;

        if (isset($criteria['roleId'])) {
            $qb->andWhere('r.roleId = :roleId')
                ->setParameter('roleId', $criteria['roleId'])
            ;
        }

        if (isset($criteria['workId'])) {
            $qb->andWhere('r.workId = :workId')
                ->setParameter('workId', $criteria['workId'])
            ;
        }

        return $qb
            ->orderBy('r.effectiveFrom', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Подсчёт активных ставок с учётом фильтров.
     *
     * @param array<string, mixed> $criteria
     */
    public function countActive(array $criteria): int
    {
        $qb = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.deletedAt IS NULL')
        ;

        if (isset($criteria['roleId'])) {
            $qb->andWhere('r.roleId = :roleId')
                ->setParameter('roleId', $criteria['roleId'])
            ;
        }

        if (isset($criteria['workId'])) {
            $qb->andWhere('r.workId = :workId')
                ->setParameter('workId', $criteria['workId'])
            ;
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Поиск активных ставок по роли.
     *
     * @return Rate[]
     */
    public function findByRoleId(string $roleId): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.roleId = :roleId')
            ->setParameter('roleId', $roleId)
            ->andWhere('r.deletedAt IS NULL')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Поиск активных ставок по виду работ.
     *
     * @return Rate[]
     */
    public function findByWorkId(string $workId): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.workId = :workId')
            ->setParameter('workId', $workId)
            ->andWhere('r.deletedAt IS NULL')
            ->getQuery()
            ->getResult()
        ;
    }
}
