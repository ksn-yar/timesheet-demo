<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\ValueResolver;

use App\Identity\Infrastructure\Dto\UpdateGroupRequestDto;
use App\Shared\Infrastructure\ValueResolver\AbstractJsonValueResolver;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;

/** Десериализует и валидирует DTO запроса на обновление группы из JSON body. */
#[AsTargetedValueResolver(UpdateGroupRequestDto::class)]
final class UpdateGroupValueResolver extends AbstractJsonValueResolver
{
    protected function getDtoRequestClass(): string
    {
        return UpdateGroupRequestDto::class;
    }
}
