<?php

declare(strict_types=1);

namespace App\WorkCatalog\Infrastructure\ValueResolver;

use App\Shared\Infrastructure\ValueResolver\AbstractJsonValueResolver;
use App\WorkCatalog\Infrastructure\Dto\CreateRateRequestDto;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;

/** Десериализует и валидирует DTO запроса на создание ставки из JSON body. */
#[AsTargetedValueResolver(CreateRateRequestDto::class)]
final class CreateRateValueResolver extends AbstractJsonValueResolver
{
    protected function getDtoRequestClass(): string
    {
        return CreateRateRequestDto::class;
    }
}
