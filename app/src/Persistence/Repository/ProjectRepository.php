<?php

declare(strict_types=1);

namespace App\Persistence\Repository;

use App\Persistence\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Репозиторий для работы с проектами через Doctrine ORM.
 *
 * @extends ServiceEntityRepository<Project>
 *
 * @method null|Project find($id, $lockMode = null, $lockVersion = null)
 * @method null|Project findOneBy(array $criteria, ?array $orderBy = null)
 * @method Project[]    findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    public function save(Project $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Project $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Поиск проектов с пагинацией и фильтрацией.
     *
     * @param array<string, mixed> $criteria
     *
     * @return Project[]
     */
    public function findAllPaginated(array $criteria, int $page, int $perPage): array
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.deletedAt IS NULL')
        ;

        if (isset($criteria['clientId'])) {
            $qb->andWhere('p.clientId = :clientId')
                ->setParameter('clientId', $criteria['clientId'])
            ;
        }

        if (isset($criteria['status'])) {
            $qb->andWhere('p.status = :status')
                ->setParameter('status', $criteria['status'])
            ;
        }

        return $qb
            ->orderBy('p.name', 'ASC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult()
        ;
    }

    /** Подсчёт проектов с учётом фильтров. */
    public function countAll(array $criteria): int
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.deletedAt IS NULL')
        ;

        if (isset($criteria['clientId'])) {
            $qb->andWhere('p.clientId = :clientId')
                ->setParameter('clientId', $criteria['clientId'])
            ;
        }

        if (isset($criteria['status'])) {
            $qb->andWhere('p.status = :status')
                ->setParameter('status', $criteria['status'])
            ;
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /** Подсчёт активных (не удалённых) проектов для указанного клиента. */
    public function countActiveByClientId(string $clientId): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.clientId = :clientId')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('clientId', $clientId)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /** Подсчёт активных задач, привязанных к проекту. */
    public function countActiveTasksByProjectId(string $projectId): int
    {
        return (int) $this->getEntityManager()
            ->createQueryBuilder()
            ->select('COUNT(t.id)')
            ->from('App\Persistence\Entity\Task', 't')
            ->andWhere('t.projectId = :projectId')
            ->andWhere('t.deletedAt IS NULL')
            ->setParameter('projectId', $projectId)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /** Подсчёт активных запросов на изменение, привязанных к проекту. */
    public function countActiveChangeRequestsByProjectId(string $projectId): int
    {
        return (int) $this->getEntityManager()
            ->createQueryBuilder()
            ->select('COUNT(cr.id)')
            ->from('App\Persistence\Entity\ChangeRequest', 'cr')
            ->andWhere('cr.projectId = :projectId')
            ->andWhere('cr.deletedAt IS NULL')
            ->setParameter('projectId', $projectId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
