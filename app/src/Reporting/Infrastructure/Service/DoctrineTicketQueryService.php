<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Service;

use App\Reporting\Application\Port\TicketQueryServiceInterface;
use App\Reporting\Domain\ValueObject\ReportFilters;
use App\Reporting\Domain\ValueObject\ReportPeriod;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Реализация ACL-сервиса получения тикетов из Timesheet BC через DBAL.
 * Изолирует домен Reporting от деталей Doctrine ORM смежного контекста.
 */
final class DoctrineTicketQueryService implements TicketQueryServiceInterface
{
    private readonly Connection $connection;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->connection = $entityManager->getConnection();
    }

    public function queryTickets(ReportPeriod $period, ReportFilters $filters): array
    {
        $qb = $this->connection->createQueryBuilder();

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
            ->join('tk', 'change_requests', 'cr', 'cr.id = tk.change_request_id')
            ->join('cr', 'projects', 'p', 'p.id = cr.project_id')
            ->join('t', 'works', 'w', 'w.id = t.work_id')
            ->where('t.date BETWEEN :periodFrom AND :periodTo')
            ->setParameter('periodFrom', $period->from()->format('Y-m-d'))
            ->setParameter('periodTo', $period->to()->format('Y-m-d'))
        ;

        // Применяем опциональные фильтры из ReportFilters
        if (null !== $filters->employeeIds()) {
            $qb->andWhere('t.employee_id IN (:employeeIds)')
                ->setParameter('employeeIds', $filters->employeeIds(), Connection::PARAM_STR_ARRAY)
            ;
        }

        if (null !== $filters->groupIds()) {
            $qb->andWhere('g.id IN (:groupIds)')
                ->setParameter('groupIds', $filters->groupIds(), Connection::PARAM_STR_ARRAY)
            ;
        }

        if (null !== $filters->projectIds()) {
            $qb->andWhere('p.id IN (:projectIds)')
                ->setParameter('projectIds', $filters->projectIds(), Connection::PARAM_STR_ARRAY)
            ;
        }

        if (null !== $filters->crIds()) {
            $qb->andWhere('cr.id IN (:crIds)')
                ->setParameter('crIds', $filters->crIds(), Connection::PARAM_STR_ARRAY)
            ;
        }

        if (null !== $filters->taskIds()) {
            $qb->andWhere('t.task_id IN (:taskIds)')
                ->setParameter('taskIds', $filters->taskIds(), Connection::PARAM_STR_ARRAY)
            ;
        }

        if (null !== $filters->workIds()) {
            $qb->andWhere('t.work_id IN (:workIds)')
                ->setParameter('workIds', $filters->workIds(), Connection::PARAM_STR_ARRAY)
            ;
        }

        return $qb->executeQuery()->fetchAllAssociative();
    }
}
