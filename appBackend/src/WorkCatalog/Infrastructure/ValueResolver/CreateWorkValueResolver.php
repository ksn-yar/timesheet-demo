<?php

declare(strict_types=1);

namespace App\WorkCatalog\Infrastructure\ValueResolver;

use App\Shared\Infrastructure\ValueResolver\AbstractJsonValueResolver;
use App\WorkCatalog\Infrastructure\Dto\CreateWorkRequestDto;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;

/** Десериализует и валидирует DTO запроса на создание вида работ из JSON body. */
#[AsTargetedValueResolver(CreateWorkRequestDto::class)]
final class CreateWorkValueResolver extends AbstractJsonValueResolver
{
    protected function getDtoRequestClass(): string
    {
        return CreateWorkRequestDto::class;
    }
}
