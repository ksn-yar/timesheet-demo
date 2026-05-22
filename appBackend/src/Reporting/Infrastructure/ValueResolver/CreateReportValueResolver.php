<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\ValueResolver;

use App\Reporting\Infrastructure\Dto\CreateReportRequestDto;
use App\Shared\Infrastructure\ValueResolver\AbstractJsonValueResolver;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;

/** Десериализует и валидирует DTO запроса на создание отчёта из JSON-тела. */
#[AsTargetedValueResolver(CreateReportRequestDto::class)]
final class CreateReportValueResolver extends AbstractJsonValueResolver
{
    protected function getDtoRequestClass(): string
    {
        return CreateReportRequestDto::class;
    }
}
