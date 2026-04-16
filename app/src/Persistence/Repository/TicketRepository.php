<?php

declare(strict_types=1);

namespace App\Persistence\Repository;

use App\Persistence\Entity\Task;
use App\Persistence\Entity\Ticket;
use App\Persistence\Entity\User;
use App\Persistence\Entity\Work;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Репозиторий для работы с тикетами учёта времени через Doctrine ORM.
 *
 * @extends ServiceEntityRepository<Ticket>
 *
 * @method null|Ticket find($id, $lockMode = null, $lockVersion = null)
 * @method null|Ticket findOneBy(array $criteria, ?array $orderBy = null)
 * @method Ticket[]    findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
 */
class TicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ticket::class);
    }

    public function save(Ticket $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Ticket $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Поиск тикетов с пагинацией и фильтрацией.
     *
     * @param array<string, mixed> $criteria
     *
     * @return Ticket[]
     */
    public function findAllPaginated(array $criteria, int $page, int $perPage): array
    {
        $qb = $this->createQueryBuilder('t');

        $this->applyCriteria($qb, $criteria);

        return $qb
            ->orderBy('t.date', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults(min($perPage, 1000))
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Поиск тикетов с JOIN на имена пользователя, задачи и вида работ.
     *
     * @param array<string, mixed> $criteria
     *
     * @return array<array{0: Ticket, employeeName: string, taskName: string, workName: string}>
     */
    public function findAllWithNamesPaginated(array $criteria, int $page, int $perPage): array
    {
        $qb = $this->createQueryBuilder('t')
            ->select('t, u.name as employeeName, task.name as taskName, w.name as workName')
            ->innerJoin(User::class, 'u', 'WITH', 'u.id = t.employeeId')
            ->innerJoin(Task::class, 'task', 'WITH', 'task.id = t.taskId')
            ->innerJoin(Work::class, 'w', 'WITH', 'w.id = t.workId')
        ;

        $this->applyCriteria($qb, $criteria);

        return $qb
            ->orderBy('t.date', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults(min($perPage, 1000))
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Подсчёт тикетов с учётом фильтров.
     *
     * @param array<string, mixed> $criteria
     */
    public function countAll(array $criteria): int
    {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
        ;

        $this->applyCriteria($qb, $criteria);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /** Проверяет существование тикета по источнику импорта и внешнему идентификатору. */
    public function existsByImportSourceAndExternalId(string $importSource, string $externalId): bool
    {
        $count = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.importSource = :importSource')
            ->andWhere('t.externalId = :externalId')
            ->setParameter('importSource', $importSource)
            ->setParameter('externalId', $externalId)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) $count > 0;
    }

    /**
     * Применяет фильтры к QueryBuilder.
     *
     * @param array<string, mixed> $criteria
     */
    private function applyCriteria(QueryBuilder $qb, array $criteria): void
    {
        if (isset($criteria['employeeId'])) {
            $qb->andWhere('t.employeeId = :employeeId')
                ->setParameter('employeeId', $criteria['employeeId'])
            ;
        }

        if (isset($criteria['taskId'])) {
            $qb->andWhere('t.taskId = :taskId')
                ->setParameter('taskId', $criteria['taskId'])
            ;
        }

        if (isset($criteria['workId'])) {
            $qb->andWhere('t.workId = :workId')
                ->setParameter('workId', $criteria['workId'])
            ;
        }

        if (isset($criteria['dateFrom'])) {
            $qb->andWhere('t.date >= :dateFrom')
                ->setParameter('dateFrom', $criteria['dateFrom'])
            ;
        }

        if (isset($criteria['dateTo'])) {
            $qb->andWhere('t.date <= :dateTo')
                ->setParameter('dateTo', $criteria['dateTo'])
            ;
        }

        if (isset($criteria['projectId'])) {
            if (!\in_array('task', $qb->getAllAliases(), true)) {
                $qb->innerJoin(Task::class, 'task', 'WITH', 'task.id = t.taskId');
            }
            $qb->andWhere('task.projectId = :projectId')
                ->setParameter('projectId', $criteria['projectId'])
            ;
        }

        if (isset($criteria['crId'])) {
            if (!\in_array('task', $qb->getAllAliases(), true)) {
                $qb->innerJoin(Task::class, 'task', 'WITH', 'task.id = t.taskId');
            }
            $qb->andWhere('task.crId = :crId')
                ->setParameter('crId', $criteria['crId'])
            ;
        }
    }
}
