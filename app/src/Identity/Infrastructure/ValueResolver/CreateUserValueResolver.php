<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\ValueResolver;

use App\Identity\Infrastructure\Dto\CreateUserRequestDto;
use App\Shared\Infrastructure\ValueResolver\AbstractJsonValueResolver;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;

/** Десериализует и валидирует DTO запроса на создание пользователя из JSON body. */
#[AsTargetedValueResolver(CreateUserRequestDto::class)]
final class CreateUserValueResolver extends AbstractJsonValueResolver
{
    protected function getDtoRequestClass(): string
    {
        return CreateUserRequestDto::class;
    }
}
