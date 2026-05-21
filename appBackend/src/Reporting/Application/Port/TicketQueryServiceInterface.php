<?php

declare(strict_types=1);

namespace App\Reporting\Application\Port;

use App\Reporting\Application\Dto\TicketReportRowDto;
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
     *
     * @return TicketReportRowDto[]
     */
    public function queryTickets(ReportPeriod $period, ReportFilters $filters): array;
}
