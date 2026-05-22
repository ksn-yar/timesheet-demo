<?php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\ValueResolver;

use App\Shared\Infrastructure\ValueResolver\AbstractJsonValueResolver;
use App\Timesheet\Infrastructure\Dto\RunImportRequestDto;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;

/** Десериализует и валидирует DTO запроса на запуск импорта из JSON body. */
#[AsTargetedValueResolver(RunImportRequestDto::class)]
final class RunImportValueResolver extends AbstractJsonValueResolver
{
    protected function getDtoRequestClass(): string
    {
        return RunImportRequestDto::class;
    }
}
