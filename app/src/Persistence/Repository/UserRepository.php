<?php

declare(strict_types=1);

namespace App\Persistence\Repository;

use App\Persistence\Entity\Ticket;
use App\Persistence\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Репозиторий для работы с пользователями через Doctrine ORM.
 *
 * @extends ServiceEntityRepository<User>
 *
 * @method null|User find($id, $lockMode = null, $lockVersion = null)
 * @method null|User findOneBy(array $criteria, ?array $orderBy = null)
 * @method User[]    findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function save(User $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(User $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email, 'deletedAt' => null]);
    }

    public function existsByEmail(string $email): bool
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.email = :email')
            ->andWhere('u.deletedAt IS NULL')
            ->setParameter('email', $email)
            ->getQuery()
            ->getSingleScalarResult() > 0
        ;
    }

    /** @return User[] */
    public function findByGroupId(string $groupId): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.group = :groupId')
            ->andWhere('u.deletedAt IS NULL')
            ->setParameter('groupId', $groupId)
            ->orderBy('u.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /** @return User[] */
    public function findByRoleId(string $roleId): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.role = :roleId')
            ->andWhere('u.deletedAt IS NULL')
            ->setParameter('roleId', $roleId)
            ->orderBy('u.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /** Количество активных пользователей в группе. */
    public function countActiveByGroupId(string $groupId): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.group = :groupId')
            ->andWhere('u.isActive = true')
            ->andWhere('u.deletedAt IS NULL')
            ->setParameter('groupId', $groupId)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /** Количество активных пользователей с указанной ролью. */
    public function countActiveByRoleId(string $roleId): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.role = :roleId')
            ->andWhere('u.isActive = true')
            ->andWhere('u.deletedAt IS NULL')
            ->setParameter('roleId', $roleId)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /** Проверяет наличие тикетов, привязанных к пользователю. */
    public function hasTicketsForUser(string $userId): bool
    {
        return (int) $this->getEntityManager()
            ->createQueryBuilder()
            ->select('COUNT(t.id)')
            ->from(Ticket::class, 't')
            ->andWhere('t.employee = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult() > 0
        ;
    }

    /**
     * Поиск пользователей с пагинацией и фильтрацией.
     *
     * @param array<string, mixed> $criteria
     *
     * @return User[]
     */
    public function findAllPaginated(array $criteria, int $page, int $perPage): array
    {
        $qb = $this->createQueryBuilder('u')
            ->andWhere('u.deletedAt IS NULL')
        ;

        $this->applyCriteria($qb, $criteria);

        return $qb
            ->orderBy('u.name', 'ASC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Подсчёт пользователей с учётом фильтров.
     *
     * @param array<string, mixed> $criteria
     */
    public function countAll(array $criteria): int
    {
        $qb = $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.deletedAt IS NULL')
        ;

        $this->applyCriteria($qb, $criteria);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /** @param array<string, mixed> $criteria */
    private function applyCriteria(QueryBuilder $qb, array $criteria): void
    {
        if (isset($criteria['groupId'])) {
            $qb->andWhere('u.group = :groupId')
                ->setParameter('groupId', $criteria['groupId'])
            ;
        }

        if (isset($criteria['roleId'])) {
            $qb->andWhere('u.role = :roleId')
                ->setParameter('roleId', $criteria['roleId'])
            ;
        }

        if (isset($criteria['isActive'])) {
            $qb->andWhere('u.isActive = :isActive')
                ->setParameter('isActive', $criteria['isActive'])
            ;
        }
    }
}
