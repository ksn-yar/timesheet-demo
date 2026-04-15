<?php

declare(strict_types=1);

namespace App\WorkCatalog\Infrastructure\ValueResolver;

use App\Shared\Infrastructure\ValueResolver\AbstractJsonValueResolver;
use App\WorkCatalog\Infrastructure\Dto\UpdateRateRequestDto;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;

/** Десериализует и валидирует DTO запроса на обновление ставки из JSON body. */
#[AsTargetedValueResolver(UpdateRateRequestDto::class)]
final class UpdateRateValueResolver extends AbstractJsonValueResolver
{
    protected function getDtoRequestClass(): string
    {
        return UpdateRateRequestDto::class;
    }
}
