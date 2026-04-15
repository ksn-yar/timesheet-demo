<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\ValueResolver;

use App\ProjectManagement\Infrastructure\Dto\UpdateClientRequestDto;
use App\Shared\Infrastructure\ValueResolver\AbstractJsonValueResolver;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;

/** Десериализует и валидирует DTO запроса на обновление клиента из JSON body. */
#[AsTargetedValueResolver(UpdateClientRequestDto::class)]
final class UpdateClientValueResolver extends AbstractJsonValueResolver
{
    protected function getDtoRequestClass(): string
    {
        return UpdateClientRequestDto::class;
    }
}
