<?php

declare(strict_types=1);

namespace App\Reporting\Application\Port;

use App\Reporting\Domain\ValueObject\ReportFilters;
use App\Reporting\Domain\ValueObject\ReportPeriod;

/**
 * Anti-Corruption Layer: получение проекций Ticket из контекста Timesheet.
 * Изолирует домен Reporting от деталей реализации смежного контекста.
 */
interface TicketQueryServiceInterface
{
    /**
     * Возвращает проекции тикетов для формирования отчёта.
     * Каждый элемент массива содержит:
     * ticketId, employeeId, employeeName, groupId, groupName,
     * taskId, taskName, crId, crName, projectId, projectName,
     * workId, workName, date, hours, rateSnapshot.
     *
     * @return array<int, array<string, mixed>>
     */
    public function queryTickets(ReportPeriod $period, ReportFilters $filters): array;
}
