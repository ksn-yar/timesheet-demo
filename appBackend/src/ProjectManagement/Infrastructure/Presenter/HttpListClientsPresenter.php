<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Presenter;

use App\ProjectManagement\Application\Dto\ListClientsOutputDto;
use App\ProjectManagement\Application\Port\ListClientsOutputPortInterface;
use App\ProjectManagement\Infrastructure\Dto\ClientListResponseDto;
use App\ProjectManagement\Infrastructure\Dto\ClientResponseDto;
use LogicException;

/** HTTP-презентер результата Use Case получения списка клиентов. Формирует Response DTO. */
final class HttpListClientsPresenter implements ListClientsOutputPortInterface
{
    private ?ListClientsOutputDto $dto = null;

    public function present(ListClientsOutputDto $dto): void
    {
        $this->dto = $dto;
    }

    public function getResponseDto(): ClientListResponseDto
    {
        if (null === $this->dto) {
            throw new LogicException('Presenter has not been called yet.');
        }

        $items = array_map(
            static fn ($item) => new ClientResponseDto(
                id: $item->id,
                name: $item->name,
                description: $item->description,
            ),
            $this->dto->items,
        );

        return new ClientListResponseDto(
            items: $items,
            total: $this->dto->total,
            page: $this->dto->page,
            perPage: $this->dto->perPage,
        );
    }
}
