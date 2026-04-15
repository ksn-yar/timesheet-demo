<?php

declare(strict_types=1);

namespace App\Persistence\Repository;

use App\Persistence\Entity\Group;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Репозиторий для работы с группами через Doctrine ORM.
 *
 * @extends ServiceEntityRepository<Group>
 *
 * @method null|Group find($id, $lockMode = null, $lockVersion = null)
 * @method null|Group findOneBy(array $criteria, ?array $orderBy = null)
 * @method Group[]    findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
 */
class GroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Group::class);
    }

    public function save(Group $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Group $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByName(string $name): ?Group
    {
        return $this->findOneBy(['name' => $name, 'deletedAt' => null]);
    }

    public function existsByName(string $name): bool
    {
        return (int) $this->createQueryBuilder('g')
            ->select('COUNT(g.id)')
            ->andWhere('g.name = :name')
            ->andWhere('g.deletedAt IS NULL')
            ->setParameter('name', $name)
            ->getQuery()
            ->getSingleScalarResult() > 0
        ;
    }

    /**
     * Поиск групп с пагинацией и фильтрацией.
     *
     * @param array<string, mixed> $criteria
     *
     * @return Group[]
     */
    public function findAllPaginated(array $criteria, int $page, int $perPage): array
    {
        $qb = $this->createQueryBuilder('g')
            ->andWhere('g.deletedAt IS NULL')
        ;

        return $qb
            ->orderBy('g.name', 'ASC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult()
        ;
    }

    /** Подсчёт групп с учётом фильтров. */
    public function countAll(array $criteria): int
    {
        $qb = $this->createQueryBuilder('g')
            ->select('COUNT(g.id)')
            ->andWhere('g.deletedAt IS NULL')
        ;

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
