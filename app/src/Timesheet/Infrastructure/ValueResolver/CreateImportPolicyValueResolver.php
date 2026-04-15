<?php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\ValueResolver;

use App\Shared\Infrastructure\ValueResolver\AbstractJsonValueResolver;
use App\Timesheet\Infrastructure\Dto\CreateImportPolicyRequestDto;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;

/** Десериализует и валидирует DTO запроса на создание политики импорта из JSON body. */
#[AsTargetedValueResolver(CreateImportPolicyRequestDto::class)]
final class CreateImportPolicyValueResolver extends AbstractJsonValueResolver
{
    protected function getDtoRequestClass(): string
    {
        return CreateImportPolicyRequestDto::class;
    }
}
