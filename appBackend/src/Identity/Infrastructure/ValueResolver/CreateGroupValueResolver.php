<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\ValueResolver;

use App\Identity\Infrastructure\Dto\CreateGroupRequestDto;
use App\Shared\Infrastructure\ValueResolver\AbstractJsonValueResolver;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;

/** Десериализует и валидирует DTO запроса на создание группы из JSON body. */
#[AsTargetedValueResolver(CreateGroupRequestDto::class)]
final class CreateGroupValueResolver extends AbstractJsonValueResolver
{
    protected function getDtoRequestClass(): string
    {
        return CreateGroupRequestDto::class;
    }
}
