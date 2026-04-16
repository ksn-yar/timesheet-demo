<?php

declare(strict_types=1);

namespace App\Persistence\Repository;

use App\Persistence\Entity\Task;
use App\Persistence\Entity\Ticket;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Репозиторий для работы с задачами через Doctrine ORM.
 *
 * @extends ServiceEntityRepository<Task>
 *
 * @method null|Task find($id, $lockMode = null, $lockVersion = null)
 * @method null|Task findOneBy(array $criteria, ?array $orderBy = null)
 * @method Task[]    findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
 */
class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    public function save(Task $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Task $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Поиск задач с пагинацией и фильтрацией.
     *
     * @param array<string, mixed> $criteria
     *
     * @return Task[]
     */
    public function findAllPaginated(array $criteria, int $page, int $perPage): array
    {
        $qb = $this->createQueryBuilder('t')
            ->andWhere('t.deletedAt IS NULL')
        ;

        if (isset($criteria['projectId'])) {
            $qb->andWhere('t.projectId = :projectId')
                ->setParameter('projectId', $criteria['projectId'])
            ;
        }

        if (isset($criteria['crId'])) {
            $qb->andWhere('t.crId = :crId')
                ->setParameter('crId', $criteria['crId'])
            ;
        }

        return $qb
            ->orderBy('t.name', 'ASC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Подсчёт задач с учётом фильтров.
     *
     * @param array<string, mixed> $criteria
     */
    public function countAll(array $criteria): int
    {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.deletedAt IS NULL')
        ;

        if (isset($criteria['projectId'])) {
            $qb->andWhere('t.projectId = :projectId')
                ->setParameter('projectId', $criteria['projectId'])
            ;
        }

        if (isset($criteria['crId'])) {
            $qb->andWhere('t.crId = :crId')
                ->setParameter('crId', $criteria['crId'])
            ;
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /** Проверяет наличие тикетов, привязанных к задаче, через Doctrine ORM QueryBuilder. */
    public function hasTicketsForTask(string $taskId): bool
    {
        /** @var int|string $count */
        $count = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('COUNT(t.id)')
            ->from(Ticket::class, 't')
            ->where('t.taskId = :taskId')
            ->setParameter('taskId', $taskId)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) $count > 0;
    }
}
