<?php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\ValueResolver;

use App\Shared\Infrastructure\ValueResolver\AbstractJsonValueResolver;
use App\Timesheet\Infrastructure\Dto\UpdateManualTicketRequestDto;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;

/** Десериализует и валидирует DTO запроса на обновление тикета из JSON body. */
#[AsTargetedValueResolver(UpdateManualTicketRequestDto::class)]
final class UpdateManualTicketValueResolver extends AbstractJsonValueResolver
{
    protected function getDtoRequestClass(): string
    {
        return UpdateManualTicketRequestDto::class;
    }
}
