<?php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Presenter;

use App\Timesheet\Application\Dto\ListTicketsOutputDto;
use App\Timesheet\Application\Port\ListTicketsOutputPortInterface;
use App\Timesheet\Infrastructure\Dto\TicketListResponseDto;
use App\Timesheet\Infrastructure\Dto\TicketResponseDto;
use LogicException;

/** HTTP-презентер результата Use Case получения списка тикетов. Формирует Response DTO. */
final class HttpListTicketsPresenter implements ListTicketsOutputPortInterface
{
    private ?ListTicketsOutputDto $dto = null;

    public function present(ListTicketsOutputDto $dto): void
    {
        $this->dto = $dto;
    }

    public function getResponseDto(): TicketListResponseDto
    {
        if (null === $this->dto) {
            throw new LogicException('Presenter has not been called yet.');
        }

        $items = array_map(
            static fn ($item) => new TicketResponseDto(
                id: $item->id,
                employeeId: $item->employeeId,
                employeeName: $item->employeeName,
                taskId: $item->taskId,
                taskName: $item->taskName,
                workId: $item->workId,
                workName: $item->workName,
                date: $item->date,
                hours: $item->hours,
                comment: $item->comment,
                rateSnapshot: $item->rateSnapshot,
                type: $item->type,
                importSource: $item->importSource,
                externalId: $item->externalId,
                isEditable: $item->isEditable,
            ),
            $this->dto->items,
        );

        return new TicketListResponseDto(
            items: $items,
            total: $this->dto->total,
            page: $this->dto->page,
            perPage: $this->dto->perPage,
        );
    }
}
