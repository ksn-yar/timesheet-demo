<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Service;

use App\Persistence\Repository\TicketRepository;
use App\Reporting\Application\Dto\TicketReportRowDto;
use App\Reporting\Application\Port\TicketQueryServiceInterface;
use App\Reporting\Domain\ValueObject\ReportFilters;
use App\Reporting\Domain\ValueObject\ReportPeriod;

/**
 * Реализация ACL-сервиса получения тикетов из Timesheet BC через TicketRepository.
 * Транслирует доменные Value Objects в примитивы и конвертирует результат в DTO.
 */
final class DoctrineTicketQueryService implements TicketQueryServiceInterface
{
    public function __construct(private readonly TicketRepository $ticketRepository) {}

    /**
     * @return TicketReportRowDto[]
     */
    public function queryTickets(ReportPeriod $period, ReportFilters $filters): array
    {
        $rows = $this->ticketRepository->findForReport(
            periodFrom: $period->from()->format('Y-m-d'),
            periodTo: $period->to()->format('Y-m-d'),
            employeeIds: $filters->employeeIds(),
            groupIds: $filters->groupIds(),
            projectIds: $filters->projectIds(),
            crIds: $filters->crIds(),
            taskIds: $filters->taskIds(),
            workIds: $filters->workIds(),
        );

        return array_map(
            static fn (array $row): TicketReportRowDto => TicketReportRowDto::fromArray($row),
            $rows,
        );
    }
}
