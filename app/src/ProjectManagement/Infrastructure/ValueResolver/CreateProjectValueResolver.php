<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\ValueResolver;

use App\ProjectManagement\Infrastructure\Dto\CreateProjectRequestDto;
use App\Shared\Infrastructure\ValueResolver\AbstractJsonValueResolver;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;

/** Десериализует и валидирует DTO запроса на создание проекта из JSON body. */
#[AsTargetedValueResolver(CreateProjectRequestDto::class)]
final class CreateProjectValueResolver extends AbstractJsonValueResolver
{
    protected function getDtoRequestClass(): string
    {
        return CreateProjectRequestDto::class;
    }
}
