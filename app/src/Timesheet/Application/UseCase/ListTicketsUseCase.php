<?php

declare(strict_types=1);

namespace App\Timesheet\Application\UseCase;

use App\Timesheet\Application\Dto\ListTicketsInputDto;
use App\Timesheet\Application\Dto\ListTicketsOutputDto;
use App\Timesheet\Application\Dto\TicketItemDto;
use App\Timesheet\Application\Port\CurrentUserProviderInterface;
use App\Timesheet\Application\Port\ListTicketsOutputPortInterface;
use App\Timesheet\Domain\Repository\TicketRepositoryInterface;

/** Use Case получения списка тикетов с фильтрацией, пагинацией и контролем доступа. */
final class ListTicketsUseCase
{
    public function __construct(
        private readonly TicketRepositoryInterface $ticketRepository,
        private readonly ListTicketsOutputPortInterface $presenter,
        private readonly CurrentUserProviderInterface $currentUserProvider,
    ) {}

    public function execute(ListTicketsInputDto $input): void
    {
        $employeeId = $input->employeeId;

        if ($this->currentUserProvider->isEmployee()) {
            $employeeId = $this->currentUserProvider->getCurrentUserId();
        }

        $criteria = [];

        if (null !== $employeeId) {
            $criteria['employeeId'] = $employeeId;
        }

        if (null !== $input->projectId) {
            $criteria['projectId'] = $input->projectId;
        }

        if (null !== $input->crId) {
            $criteria['crId'] = $input->crId;
        }

        if (null !== $input->taskId) {
            $criteria['taskId'] = $input->taskId;
        }

        if (null !== $input->workId) {
            $criteria['workId'] = $input->workId;
        }

        if (null !== $input->dateFrom) {
            $criteria['dateFrom'] = $input->dateFrom;
        }

        if (null !== $input->dateTo) {
            $criteria['dateTo'] = $input->dateTo;
        }

        $rows = $this->ticketRepository->findAllWithNames($criteria, $input->page, $input->perPage);
        $total = $this->ticketRepository->countAll($criteria);

        $items = array_map(
            static fn (array $row): TicketItemDto => new TicketItemDto(
                id: $row['ticket']->getId()->value(),
                employeeId: $row['ticket']->getEmployeeId(),
                employeeName: $row['employeeName'],
                taskId: $row['ticket']->getTaskId(),
                taskName: $row['taskName'],
                workId: $row['ticket']->getWorkId(),
                workName: $row['workName'],
                date: $row['ticket']->getDate()->format('Y-m-d'),
                hours: $row['ticket']->getHours(),
                comment: $row['ticket']->getComment(),
                rateSnapshot: $row['ticket']->getRateSnapshot(),
                type: $row['ticket']->getType()->value,
                importSource: $row['ticket']->getImportSource(),
                externalId: $row['ticket']->getExternalId(),
                isEditable: $row['ticket']->isEditable(),
            ),
            $rows,
        );

        $this->presenter->present(new ListTicketsOutputDto(
            items: $items,
            total: $total,
            page: $input->page,
            perPage: $input->perPage,
        ));
    }
}
