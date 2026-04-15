<?php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Transformer;

use App\Timesheet\Application\Dto\GetTicketInputDto;

/** Трансформирует идентификатор тикета из маршрута в Application DTO. */
final class GetTicketInputTransformer
{
    public function transform(string $ticketId): GetTicketInputDto
    {
        return new GetTicketInputDto(
            ticketId: $ticketId,
        );
    }
}
