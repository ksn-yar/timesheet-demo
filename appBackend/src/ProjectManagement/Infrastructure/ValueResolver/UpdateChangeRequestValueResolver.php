<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\ValueResolver;

use App\ProjectManagement\Infrastructure\Dto\UpdateChangeRequestRequestDto;
use App\Shared\Infrastructure\ValueResolver\AbstractJsonValueResolver;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;

/** Десериализует и валидирует DTO запроса на обновление запроса на изменение из JSON body. */
#[AsTargetedValueResolver(UpdateChangeRequestRequestDto::class)]
final class UpdateChangeRequestValueResolver extends AbstractJsonValueResolver
{
    protected function getDtoRequestClass(): string
    {
        return UpdateChangeRequestRequestDto::class;
    }
}
