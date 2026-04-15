<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\ValueResolver;

use App\Identity\Infrastructure\Dto\ChangeUserGroupRequestDto;
use App\Shared\Infrastructure\ValueResolver\AbstractJsonValueResolver;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;

/** Десериализует и валидирует DTO запроса на изменение группы пользователя из JSON body. */
#[AsTargetedValueResolver(ChangeUserGroupRequestDto::class)]
final class ChangeUserGroupValueResolver extends AbstractJsonValueResolver
{
    protected function getDtoRequestClass(): string
    {
        return ChangeUserGroupRequestDto::class;
    }
}
