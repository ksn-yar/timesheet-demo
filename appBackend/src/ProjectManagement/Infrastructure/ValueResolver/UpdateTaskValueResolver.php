<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\ValueResolver;

use App\ProjectManagement\Infrastructure\Dto\UpdateTaskRequestDto;
use App\Shared\Infrastructure\ValueResolver\AbstractJsonValueResolver;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;

/** Десериализует и валидирует DTO запроса на обновление задачи из JSON body. */
#[AsTargetedValueResolver(UpdateTaskRequestDto::class)]
final class UpdateTaskValueResolver extends AbstractJsonValueResolver
{
    protected function getDtoRequestClass(): string
    {
        return UpdateTaskRequestDto::class;
    }
}
