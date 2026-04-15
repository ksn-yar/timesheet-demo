<?php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\ValueResolver;

use App\Shared\Infrastructure\ValueResolver\AbstractJsonValueResolver;
use App\Timesheet\Infrastructure\Dto\UpdateImportPolicyRequestDto;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;

/** Десериализует и валидирует DTO запроса на обновление политики импорта из JSON body. */
#[AsTargetedValueResolver(UpdateImportPolicyRequestDto::class)]
final class UpdateImportPolicyValueResolver extends AbstractJsonValueResolver
{
    protected function getDtoRequestClass(): string
    {
        return UpdateImportPolicyRequestDto::class;
    }
}
