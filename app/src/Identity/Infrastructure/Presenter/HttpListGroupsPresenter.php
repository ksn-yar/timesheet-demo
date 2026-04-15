<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Presenter;

use App\Identity\Application\Dto\ListGroupsOutputDto;
use App\Identity\Application\Port\ListGroupsOutputPortInterface;
use App\Identity\Infrastructure\Dto\GroupListResponseDto;
use App\Identity\Infrastructure\Dto\GroupResponseDto;
use LogicException;

/** HTTP-презентер результата Use Case получения списка групп. Формирует Response DTO. */
final class HttpListGroupsPresenter implements ListGroupsOutputPortInterface
{
    private ?ListGroupsOutputDto $dto = null;

    public function present(ListGroupsOutputDto $dto): void
    {
        $this->dto = $dto;
    }

    public function getResponseDto(): GroupListResponseDto
    {
        if (null === $this->dto) {
            throw new LogicException('Presenter has not been called yet.');
        }

        $items = array_map(
            static fn ($item) => new GroupResponseDto(
                id: $item->id,
                name: $item->name,
                description: $item->description,
            ),
            $this->dto->items,
        );

        return new GroupListResponseDto(
            items: $items,
            total: $this->dto->total,
            page: $this->dto->page,
            perPage: $this->dto->perPage,
        );
    }
}
