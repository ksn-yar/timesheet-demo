<?php

declare(strict_types=1);

namespace App\WorkCatalog\Infrastructure\ValueResolver;

use App\Shared\Infrastructure\ValueResolver\AbstractJsonValueResolver;
use App\WorkCatalog\Infrastructure\Dto\CreateRoleRequestDto;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;

/** Десериализует и валидирует DTO запроса на создание роли из JSON body. */
#[AsTargetedValueResolver(CreateRoleRequestDto::class)]
final class CreateRoleValueResolver extends AbstractJsonValueResolver
{
    protected function getDtoRequestClass(): string
    {
        return CreateRoleRequestDto::class;
    }
}
