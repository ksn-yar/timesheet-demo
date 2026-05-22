<?php

declare(strict_types=1);

namespace App\Persistence\Repository;

use App\Persistence\Entity\Work;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Репозиторий для работы с видами работ через Doctrine ORM.
 *
 * @extends ServiceEntityRepository<Work>
 *
 * @method null|Work find($id, $lockMode = null, $lockVersion = null)
 * @method null|Work findOneBy(array $criteria, ?array $orderBy = null)
 * @method Work[]    findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
 */
class WorkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Work::class);
    }

    public function save(Work $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /** Поиск активного вида работ по точному имени. */
    public function findByName(string $name): ?Work
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.name = :name')
            ->setParameter('name', $name)
            ->andWhere('w.deletedAt IS NULL')
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * Поиск активных видов работ с пагинацией.
     *
     * @param array<string, mixed> $criteria
     *
     * @return Work[]
     */
    public function findActiveAll(array $criteria, int $limit, int $offset): array
    {
        $qb = $this->createQueryBuilder('w')
            ->andWhere('w.deletedAt IS NULL')
        ;

        return $qb
            ->orderBy('w.name', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Подсчёт активных видов работ.
     *
     * @param array<string, mixed> $criteria
     */
    public function countActive(array $criteria): int
    {
        $qb = $this->createQueryBuilder('w')
            ->select('COUNT(w.id)')
            ->andWhere('w.deletedAt IS NULL')
        ;

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
