<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\ValueResolver;

use App\ProjectManagement\Infrastructure\Dto\CreateClientRequestDto;
use App\Shared\Infrastructure\ValueResolver\AbstractJsonValueResolver;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;

/** Десериализует и валидирует DTO запроса на создание клиента из JSON body. */
#[AsTargetedValueResolver(CreateClientRequestDto::class)]
final class CreateClientValueResolver extends AbstractJsonValueResolver
{
    protected function getDtoRequestClass(): string
    {
        return CreateClientRequestDto::class;
    }
}
