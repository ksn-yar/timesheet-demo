<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Presenter;

use App\Reporting\Application\Dto\ListReportsOutputDto;
use App\Reporting\Application\Dto\ReportItemDto;
use App\Reporting\Application\Port\ListReportsOutputPortInterface;
use App\Reporting\Infrastructure\Dto\ReportListItemResponseDto;
use App\Reporting\Infrastructure\Dto\ReportListResponseDto;
use LogicException;

/** HTTP-презентер результата Use Case получения списка отчётов. Формирует Response DTO с пагинацией. */
final class HttpListReportsPresenter implements ListReportsOutputPortInterface
{
    private ?ListReportsOutputDto $dto = null;

    public function present(ListReportsOutputDto $dto): void
    {
        $this->dto = $dto;
    }

    public function getResponseDto(): ReportListResponseDto
    {
        if (null === $this->dto) {
            throw new LogicException('Presenter has not been called yet.');
        }

        $items = array_map(
            static fn (ReportItemDto $item): ReportListItemResponseDto => new ReportListItemResponseDto(
                id: $item->id,
                name: $item->name,
                createdBy: $item->createdBy,
                createdAt: $item->createdAt,
                periodFrom: $item->periodFrom,
                periodTo: $item->periodTo,
            ),
            $this->dto->items,
        );

        return new ReportListResponseDto(
            items: $items,
            total: $this->dto->total,
            page: $this->dto->page,
            perPage: $this->dto->perPage,
        );
    }
}
