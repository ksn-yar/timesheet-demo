<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Presenter;

use App\ProjectManagement\Application\Dto\ListTasksOutputDto;
use App\ProjectManagement\Application\Port\ListTasksOutputPortInterface;
use App\ProjectManagement\Infrastructure\Dto\TaskListResponseDto;
use App\ProjectManagement\Infrastructure\Dto\TaskResponseDto;
use LogicException;

/** HTTP-презентер результата Use Case получения списка задач. Формирует Response DTO. */
final class HttpListTasksPresenter implements ListTasksOutputPortInterface
{
    private ?ListTasksOutputDto $dto = null;

    public function present(ListTasksOutputDto $dto): void
    {
        $this->dto = $dto;
    }

    public function getResponseDto(): TaskListResponseDto
    {
        if (null === $this->dto) {
            throw new LogicException('Presenter has not been called yet.');
        }

        $items = array_map(
            static fn ($item) => new TaskResponseDto(
                id: $item->id,
                projectId: $item->projectId,
                projectName: $item->projectName,
                crId: $item->crId,
                crName: $item->crName,
                name: $item->name,
                description: $item->description,
                estimate: $item->estimate,
            ),
            $this->dto->items,
        );

        return new TaskListResponseDto(
            items: $items,
            total: $this->dto->total,
            page: $this->dto->page,
            perPage: $this->dto->perPage,
        );
    }
}
