<?php

declare(strict_types=1);

namespace App\Reporting\Application\UseCase;

use App\Reporting\Application\Dto\CreateReportInputDto;
use App\Reporting\Application\Port\TicketQueryServiceInterface;
use App\Reporting\Application\Service\ReportAggregationService;
use App\Reporting\Domain\Entity\Report;
use App\Reporting\Domain\Repository\ReportRepositoryInterface;
use App\Reporting\Domain\ValueObject\ReportFilters;
use App\Reporting\Domain\ValueObject\ReportGroupBy;
use App\Reporting\Domain\ValueObject\ReportId;
use App\Reporting\Domain\ValueObject\ReportPeriod;
use DateTimeImmutable;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/** Use Case создания нового отчёта: запрашивает тикеты, агрегирует данные и сохраняет отчёт. */
final readonly class CreateReportUseCase
{
    public function __construct(
        private ReportRepositoryInterface $reportRepository,
        private TicketQueryServiceInterface $ticketQueryService,
        private ReportAggregationService $aggregationService,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(CreateReportInputDto $input): void
    {
        $period = new ReportPeriod(
            new DateTimeImmutable($input->periodFrom),
            new DateTimeImmutable($input->periodTo),
        );

        $filters = null !== $input->filters
            ? ReportFilters::fromArray($input->filters)
            : new ReportFilters();

        $groupBy = ReportGroupBy::fromArray($input->groupBy);

        $tickets = $this->ticketQueryService->queryTickets($period, $filters);
        $data = $this->aggregationService->aggregate($tickets, $groupBy);

        $report = Report::create(
            new ReportId($input->reportId),
            $input->name,
            $input->createdBy,
            $period,
            $filters,
            $groupBy,
            $data,
        );

        $this->reportRepository->save($report);

        foreach ($report->pullDomainEvents() as $event) {
            $this->eventDispatcher->dispatch($event);
        }
    }
}
