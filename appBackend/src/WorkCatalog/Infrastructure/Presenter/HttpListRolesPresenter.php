<?php

declare(strict_types=1);

namespace App\WorkCatalog\Infrastructure\Presenter;

use App\WorkCatalog\Application\Dto\ListRolesOutputDto;
use App\WorkCatalog\Application\Port\ListRolesOutputPortInterface;
use App\WorkCatalog\Infrastructure\Dto\RoleListResponseDto;
use App\WorkCatalog\Infrastructure\Dto\RoleResponseDto;
use LogicException;

/** HTTP-презентер результата Use Case получения списка ролей. Формирует Response DTO. */
final class HttpListRolesPresenter implements ListRolesOutputPortInterface
{
    private ?ListRolesOutputDto $dto = null;

    public function present(ListRolesOutputDto $dto): void
    {
        $this->dto = $dto;
    }

    public function getResponseDto(): RoleListResponseDto
    {
        if (null === $this->dto) {
            throw new LogicException('Presenter has not been called yet.');
        }

        $items = array_map(
            static fn ($item) => new RoleResponseDto(
                id: $item->id,
                name: $item->name,
                description: $item->description,
            ),
            $this->dto->items,
        );

        return new RoleListResponseDto(
            items: $items,
            total: $this->dto->total,
            page: $this->dto->page,
            perPage: $this->dto->perPage,
        );
    }
}
