<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Presenter;

use App\ProjectManagement\Application\Dto\ListChangeRequestsOutputDto;
use App\ProjectManagement\Application\Port\ListChangeRequestsOutputPortInterface;
use App\ProjectManagement\Infrastructure\Dto\ChangeRequestListResponseDto;
use App\ProjectManagement\Infrastructure\Dto\ChangeRequestResponseDto;
use LogicException;

/** HTTP-презентер результата Use Case получения списка запросов на изменение. Формирует Response DTO. */
final class HttpListChangeRequestsPresenter implements ListChangeRequestsOutputPortInterface
{
    private ?ListChangeRequestsOutputDto $dto = null;

    public function present(ListChangeRequestsOutputDto $dto): void
    {
        $this->dto = $dto;
    }

    public function getResponseDto(): ChangeRequestListResponseDto
    {
        if (null === $this->dto) {
            throw new LogicException('Presenter has not been called yet.');
        }

        $items = array_map(
            static fn ($item) => new ChangeRequestResponseDto(
                id: $item->id,
                projectId: $item->projectId,
                projectName: $item->projectName,
                name: $item->name,
                description: $item->description,
            ),
            $this->dto->items,
        );

        return new ChangeRequestListResponseDto(
            items: $items,
            total: $this->dto->total,
            page: $this->dto->page,
            perPage: $this->dto->perPage,
        );
    }
}
