<?php

declare(strict_types=1);

namespace App\WorkCatalog\Infrastructure\Transformer;

use App\WorkCatalog\Application\Dto\UpdateRateInputDto;
use App\WorkCatalog\Infrastructure\Dto\UpdateRateRequestDto;

/** Трансформирует UpdateRateRequestDto в UpdateRateInputDto для Use Case. */
final readonly class UpdateRateInputTransformer
{
    public function transform(string $id, UpdateRateRequestDto $dto): UpdateRateInputDto
    {
        return new UpdateRateInputDto(
            id: $id,
            amount: $dto->amount,
            currency: $dto->currency,
            effectiveFrom: $dto->effectiveFrom,
            roleId: $dto->roleId,
            workId: $dto->workId,
        );
    }
}
