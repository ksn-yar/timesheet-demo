<?php

declare(strict_types=1);

namespace App\Persistence\Repository;

use App\Persistence\Entity\Rate;
use App\Persistence\Entity\Role;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Репозиторий для работы с ролями через Doctrine ORM.
 *
 * @extends ServiceEntityRepository<Role>
 *
 * @method null|Role find($id, $lockMode = null, $lockVersion = null)
 * @method null|Role findOneBy(array $criteria, ?array $orderBy = null)
 * @method Role[]    findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
 */
class RoleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Role::class);
    }

    public function save(Role $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /** Поиск активной роли по точному имени. */
    public function findByName(string $name): ?Role
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.name = :name')
            ->setParameter('name', $name)
            ->andWhere('r.deletedAt IS NULL')
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * Поиск активных ролей с пагинацией.
     *
     * @param array<string, mixed> $criteria
     *
     * @return Role[]
     */
    public function findActiveAll(array $criteria, int $page, int $perPage): array
    {
        $qb = $this->createQueryBuilder('r')
            ->andWhere('r.deletedAt IS NULL')
        ;

        return $qb
            ->orderBy('r.name', 'ASC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult()
        ;
    }

    /** Подсчёт активных ролей. */
    public function countActive(array $criteria): int
    {
        $qb = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.deletedAt IS NULL')
        ;

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /** Подсчёт активных ставок, привязанных к роли. */
    public function countActiveRatesByRoleId(string $roleId): int
    {
        return (int) $this->getEntityManager()
            ->createQueryBuilder()
            ->select('COUNT(rate.id)')
            ->from(Rate::class, 'rate')
            ->andWhere('rate.roleId = :roleId')
            ->setParameter('roleId', $roleId)
            ->andWhere('rate.deletedAt IS NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
