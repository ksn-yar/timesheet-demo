<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Transformer;

use App\ProjectManagement\Application\Dto\DeleteChangeRequestInputDto;

/** Трансформирует path-параметр id в DeleteChangeRequestInputDto для Use Case. */
final class DeleteChangeRequestInputTransformer
{
    public function transform(string $id): DeleteChangeRequestInputDto
    {
        return new DeleteChangeRequestInputDto(id: $id);
    }
}
