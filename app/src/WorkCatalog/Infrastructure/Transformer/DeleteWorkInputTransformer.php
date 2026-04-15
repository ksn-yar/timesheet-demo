<?php

declare(strict_types=1);

namespace App\WorkCatalog\Infrastructure\Transformer;

use App\WorkCatalog\Application\Dto\DeleteWorkInputDto;

/** Трансформирует path-параметр id в DeleteWorkInputDto для Use Case. */
final class DeleteWorkInputTransformer
{
    public function transform(string $id): DeleteWorkInputDto
    {
        return new DeleteWorkInputDto(id: $id);
    }
}
