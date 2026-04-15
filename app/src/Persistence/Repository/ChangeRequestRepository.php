<?php

declare(strict_types=1);

namespace App\Persistence\Repository;

use App\Persistence\Entity\ChangeRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Репозиторий для работы с запросами на изменение через Doctrine ORM.
 *
 * @extends ServiceEntityRepository<ChangeRequest>
 *
 * @method null|ChangeRequest find($id, $lockMode = null, $lockVersion = null)
 * @method null|ChangeRequest findOneBy(array $criteria, ?array $orderBy = null)
 * @method ChangeRequest[]    findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
 */
class ChangeRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChangeRequest::class);
    }

    public function save(ChangeRequest $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ChangeRequest $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Поиск запросов на изменение с пагинацией и фильтрацией.
     *
     * @param array<string, mixed> $criteria
     *
     * @return ChangeRequest[]
     */
    public function findAllPaginated(array $criteria, int $page, int $perPage): array
    {
        $qb = $this->createQueryBuilder('cr')
            ->andWhere('cr.deletedAt IS NULL')
        ;

        if (isset($criteria['projectId'])) {
            $qb->andWhere('cr.projectId = :projectId')
                ->setParameter('projectId', $criteria['projectId'])
            ;
        }

        if (isset($criteria['name'])) {
            $qb->andWhere('cr.name LIKE :name')
                ->setParameter('name', '%' . $criteria['name'] . '%')
            ;
        }

        return $qb
            ->orderBy('cr.name', 'ASC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult()
        ;
    }

    /** Подсчёт запросов на изменение с учётом фильтров. */
    public function countAll(array $criteria): int
    {
        $qb = $this->createQueryBuilder('cr')
            ->select('COUNT(cr.id)')
            ->andWhere('cr.deletedAt IS NULL')
        ;

        if (isset($criteria['projectId'])) {
            $qb->andWhere('cr.projectId = :projectId')
                ->setParameter('projectId', $criteria['projectId'])
            ;
        }

        if (isset($criteria['name'])) {
            $qb->andWhere('cr.name LIKE :name')
                ->setParameter('name', '%' . $criteria['name'] . '%')
            ;
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /** Подсчёт активных задач, привязанных к запросу на изменение. */
    public function countActiveTasksByChangeRequestId(string $crId): int
    {
        return (int) $this->getEntityManager()
            ->createQueryBuilder()
            ->select('COUNT(t.id)')
            ->from('App\Persistence\Entity\Task', 't')
            ->andWhere('t.crId = :crId')
            ->andWhere('t.deletedAt IS NULL')
            ->setParameter('crId', $crId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
