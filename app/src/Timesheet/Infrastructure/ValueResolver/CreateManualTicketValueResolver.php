<?php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\ValueResolver;

use App\Shared\Infrastructure\ValueResolver\AbstractJsonValueResolver;
use App\Timesheet\Infrastructure\Dto\CreateManualTicketRequestDto;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;

/** Десериализует и валидирует DTO запроса на создание тикета из JSON body. */
#[AsTargetedValueResolver(CreateManualTicketRequestDto::class)]
final class CreateManualTicketValueResolver extends AbstractJsonValueResolver
{
    protected function getDtoRequestClass(): string
    {
        return CreateManualTicketRequestDto::class;
    }
}
