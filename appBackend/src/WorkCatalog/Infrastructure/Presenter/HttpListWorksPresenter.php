<?php

declare(strict_types=1);

namespace App\WorkCatalog\Infrastructure\Presenter;

use App\WorkCatalog\Application\Dto\ListWorksOutputDto;
use App\WorkCatalog\Application\Port\ListWorksOutputPortInterface;
use App\WorkCatalog\Infrastructure\Dto\WorkListResponseDto;
use App\WorkCatalog\Infrastructure\Dto\WorkResponseDto;
use LogicException;

/** HTTP-презентер результата Use Case получения списка видов работ. Формирует Response DTO. */
final class HttpListWorksPresenter implements ListWorksOutputPortInterface
{
    private ?ListWorksOutputDto $dto = null;

    public function present(ListWorksOutputDto $dto): void
    {
        $this->dto = $dto;
    }

    public function getResponseDto(): WorkListResponseDto
    {
        if (null === $this->dto) {
            throw new LogicException('Presenter has not been called yet.');
        }

        $items = array_map(
            static fn ($item) => new WorkResponseDto(
                id: $item->id,
                name: $item->name,
                description: $item->description,
            ),
            $this->dto->items,
        );

        return new WorkListResponseDto(
            items: $items,
            total: $this->dto->total,
            page: $this->dto->page,
            perPage: $this->dto->perPage,
        );
    }
}
