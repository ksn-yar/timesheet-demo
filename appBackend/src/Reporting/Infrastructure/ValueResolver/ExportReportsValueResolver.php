<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\ValueResolver;

use App\Reporting\Infrastructure\Dto\ExportReportsRequestDto;
use App\Shared\Infrastructure\ValueResolver\AbstractJsonValueResolver;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;

/** Десериализует и валидирует DTO запроса на экспорт отчётов из JSON-тела. */
#[AsTargetedValueResolver(ExportReportsRequestDto::class)]
final class ExportReportsValueResolver extends AbstractJsonValueResolver
{
    protected function getDtoRequestClass(): string
    {
        return ExportReportsRequestDto::class;
    }
}
