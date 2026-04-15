<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Presenter;

use App\Reporting\Application\Dto\ExportItemDto;
use App\Reporting\Application\Dto\ListExportedReportsOutputDto;
use App\Reporting\Application\Port\ListExportedReportsOutputPortInterface;
use App\Reporting\Infrastructure\Dto\ExportListItemResponseDto;
use App\Reporting\Infrastructure\Dto\ExportListResponseDto;
use LogicException;

/** HTTP-презентер результата Use Case получения списка выгрузок. Формирует Response DTO с пагинацией. */
final class HttpListExportedReportsPresenter implements ListExportedReportsOutputPortInterface
{
    private ?ListExportedReportsOutputDto $dto = null;

    public function present(ListExportedReportsOutputDto $dto): void
    {
        $this->dto = $dto;
    }

    public function getResponseDto(): ExportListResponseDto
    {
        if (null === $this->dto) {
            throw new LogicException('Presenter has not been called yet.');
        }

        $items = array_map(
            static fn (ExportItemDto $item): ExportListItemResponseDto => new ExportListItemResponseDto(
                id: $item->id,
                reportIds: $item->reportIds,
                format: $item->format,
                generatedAt: $item->generatedAt,
                fileRef: $item->fileRef,
            ),
            $this->dto->items,
        );

        return new ExportListResponseDto(
            items: $items,
            total: $this->dto->total,
            page: $this->dto->page,
            perPage: $this->dto->perPage,
        );
    }
}
