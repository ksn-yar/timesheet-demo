<?php

declare(strict_types=1);

namespace App\WorkCatalog\Infrastructure\Transformer;

use App\WorkCatalog\Application\Dto\CreateRateInputDto;
use App\WorkCatalog\Infrastructure\Dto\CreateRateRequestDto;

/** Трансформирует CreateRateRequestDto в CreateRateInputDto для Use Case. */
final class CreateRateInputTransformer
{
    public function transform(CreateRateRequestDto $dto): CreateRateInputDto
    {
        return new CreateRateInputDto(
            amount: $dto->amount,
            currency: $dto->currency,
            effectiveFrom: $dto->effectiveFrom,
            roleId: $dto->roleId,
            workId: $dto->workId,
        );
    }
}
