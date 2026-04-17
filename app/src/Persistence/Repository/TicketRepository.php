<?php

declare(strict_types=1);

namespace App\Persistence\Repository;

use App\Persistence\Entity\Ticket;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ArrayParameterType;
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
            ->innerJoin('t.employee', 'u')
            ->innerJoin('t.task', 'task')
            ->innerJoin('t.work', 'w')
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
     * Выбирает денормализованные проекции тикетов для формирования отчёта.
     * Параметры являются примитивами, чтобы не создавать зависимость на другие Bounded Contexts.
     *
     * @param string[]|null $employeeIds
     * @param string[]|null $groupIds
     * @param string[]|null $projectIds
     * @param string[]|null $crIds
     * @param string[]|null $taskIds
     * @param string[]|null $workIds
     *
     * @return array<int, array<string, mixed>>
     */
    public function findForReport(
        string $periodFrom,
        string $periodTo,
        ?array $employeeIds = null,
        ?array $groupIds = null,
        ?array $projectIds = null,
        ?array $crIds = null,
        ?array $taskIds = null,
        ?array $workIds = null,
    ): array {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $qb->select(
            't.id          AS ticketId',
            't.employee_id AS employeeId',
            'u.name        AS employeeName',
            'g.id          AS groupId',
            'g.name        AS groupName',
            't.task_id     AS taskId',
            'tk.name       AS taskName',
            'cr.id         AS crId',
            'cr.name       AS crName',
            'p.id          AS projectId',
            'p.name        AS projectName',
            't.work_id     AS workId',
            'w.name        AS workName',
            't.date        AS date',
            't.hours       AS hours',
            't.rate_snapshot AS rateSnapshot',
        )
            ->from('tickets', 't')
            ->join('t', 'users', 'u', 'u.id = t.employee_id')
            ->join('u', 'groups', 'g', 'g.id = u.group_id')
            ->join('t', 'tasks', 'tk', 'tk.id = t.task_id')
            ->join('tk', 'change_requests', 'cr', 'cr.id = tk.cr_id')
            ->join('cr', 'projects', 'p', 'p.id = cr.project_id')
            ->join('t', 'works', 'w', 'w.id = t.work_id')
            ->where('t.date BETWEEN :periodFrom AND :periodTo')
            ->setParameter('periodFrom', $periodFrom)
            ->setParameter('periodTo', $periodTo)
        ;

        if (null !== $employeeIds) {
            $qb->andWhere('t.employee_id IN (:employeeIds)')
                ->setParameter('employeeIds', $employeeIds, ArrayParameterType::STRING)
            ;
        }

        if (null !== $groupIds) {
            $qb->andWhere('g.id IN (:groupIds)')
                ->setParameter('groupIds', $groupIds, ArrayParameterType::STRING)
            ;
        }

        if (null !== $projectIds) {
            $qb->andWhere('p.id IN (:projectIds)')
                ->setParameter('projectIds', $projectIds, ArrayParameterType::STRING)
            ;
        }

        if (null !== $crIds) {
            $qb->andWhere('cr.id IN (:crIds)')
                ->setParameter('crIds', $crIds, ArrayParameterType::STRING)
            ;
        }

        if (null !== $taskIds) {
            $qb->andWhere('t.task_id IN (:taskIds)')
                ->setParameter('taskIds', $taskIds, ArrayParameterType::STRING)
            ;
        }

        if (null !== $workIds) {
            $qb->andWhere('t.work_id IN (:workIds)')
                ->setParameter('workIds', $workIds, ArrayParameterType::STRING)
            ;
        }

        return $qb->executeQuery()->fetchAllAssociative();
    }

    /**
     * Применяет фильтры к QueryBuilder.
     *
     * @param array<string, mixed> $criteria
     */
    private function applyCriteria(QueryBuilder $qb, array $criteria): void
    {
        if (isset($criteria['employeeId'])) {
            $qb->andWhere('t.employee = :employeeId')
                ->setParameter('employeeId', $criteria['employeeId'])
            ;
        }

        if (isset($criteria['taskId'])) {
            $qb->andWhere('t.task = :taskId')
                ->setParameter('taskId', $criteria['taskId'])
            ;
        }

        if (isset($criteria['workId'])) {
            $qb->andWhere('t.work = :workId')
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
                $qb->innerJoin('t.task', 'task');
            }
            $qb->andWhere('task.project = :projectId')
                ->setParameter('projectId', $criteria['projectId'])
            ;
        }

        if (isset($criteria['crId'])) {
            if (!\in_array('task', $qb->getAllAliases(), true)) {
                $qb->innerJoin('t.task', 'task');
            }
            $qb->andWhere('task.changeRequest = :crId')
                ->setParameter('crId', $criteria['crId'])
            ;
        }
    }
}
