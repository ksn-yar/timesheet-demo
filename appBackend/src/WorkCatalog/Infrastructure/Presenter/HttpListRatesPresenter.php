<?php

declare(strict_types=1);

namespace App\WorkCatalog\Infrastructure\Presenter;

use App\WorkCatalog\Application\Dto\ListRatesOutputDto;
use App\WorkCatalog\Application\Port\ListRatesOutputPortInterface;
use App\WorkCatalog\Infrastructure\Dto\RateListResponseDto;
use App\WorkCatalog\Infrastructure\Dto\RateResponseDto;
use LogicException;

/** HTTP-презентер результата Use Case получения списка ставок. Формирует Response DTO. */
final class HttpListRatesPresenter implements ListRatesOutputPortInterface
{
    private ?ListRatesOutputDto $dto = null;

    public function present(ListRatesOutputDto $dto): void
    {
        $this->dto = $dto;
    }

    public function getResponseDto(): RateListResponseDto
    {
        if (null === $this->dto) {
            throw new LogicException('Presenter has not been called yet.');
        }

        $items = array_map(
            static fn ($item) => new RateResponseDto(
                id: $item->id,
                amount: $item->amount,
                currency: $item->currency,
                effectiveFrom: $item->effectiveFrom,
                roleId: $item->roleId,
                roleName: $item->roleName,
                workId: $item->workId,
                workName: $item->workName,
            ),
            $this->dto->items,
        );

        return new RateListResponseDto(
            items: $items,
            total: $this->dto->total,
            page: $this->dto->page,
            perPage: $this->dto->perPage,
        );
    }
}
