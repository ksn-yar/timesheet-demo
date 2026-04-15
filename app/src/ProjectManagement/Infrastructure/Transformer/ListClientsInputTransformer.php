<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Transformer;

use App\ProjectManagement\Application\Dto\ListClientsInputDto;
use App\ProjectManagement\Infrastructure\Dto\ListClientsRequestDto;

/** Трансформирует ListClientsRequestDto в ListClientsInputDto для Use Case. */
final class ListClientsInputTransformer
{
    public function transform(ListClientsRequestDto $dto): ListClientsInputDto
    {
        return new ListClientsInputDto(
            name: $dto->name,
            page: $dto->page,
            perPage: $dto->perPage,
        );
    }
}
