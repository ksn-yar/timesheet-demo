<?php

declare(strict_types=1);

namespace App\WorkCatalog\Infrastructure\Transformer;

use App\WorkCatalog\Application\Dto\DeleteRateInputDto;

/** Трансформирует path-параметр id в DeleteRateInputDto для Use Case. */
final readonly class DeleteRateInputTransformer
{
    public function transform(string $id): DeleteRateInputDto
    {
        return new DeleteRateInputDto(id: $id);
    }
}
