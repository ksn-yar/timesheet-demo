<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Presenter;

use App\ProjectManagement\Application\Dto\ListProjectsOutputDto;
use App\ProjectManagement\Application\Port\ListProjectsOutputPortInterface;
use App\ProjectManagement\Infrastructure\Dto\ProjectListResponseDto;
use App\ProjectManagement\Infrastructure\Dto\ProjectResponseDto;
use LogicException;

/** HTTP-презентер результата Use Case получения списка проектов. Формирует Response DTO. */
final class HttpListProjectsPresenter implements ListProjectsOutputPortInterface
{
    private ?ListProjectsOutputDto $dto = null;

    public function present(ListProjectsOutputDto $dto): void
    {
        $this->dto = $dto;
    }

    public function getResponseDto(): ProjectListResponseDto
    {
        if (null === $this->dto) {
            throw new LogicException('Presenter has not been called yet.');
        }

        $items = array_map(
            static fn ($item) => new ProjectResponseDto(
                id: $item->id,
                clientId: $item->clientId,
                clientName: $item->clientName,
                name: $item->name,
                status: $item->status,
                statusLabel: $item->statusLabel,
                description: $item->description,
            ),
            $this->dto->items,
        );

        return new ProjectListResponseDto(
            items: $items,
            total: $this->dto->total,
            page: $this->dto->page,
            perPage: $this->dto->perPage,
        );
    }
}
