<?php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Transformer;

use App\Timesheet\Application\Dto\UpdateManualTicketInputDto;
use App\Timesheet\Infrastructure\Dto\UpdateManualTicketRequestDto;
use DateTimeImmutable;

/** Трансформирует HTTP DTO обновления тикета в Application DTO. */
final readonly class UpdateManualTicketInputTransformer
{
    public function transform(string $ticketId, UpdateManualTicketRequestDto $dto): UpdateManualTicketInputDto
    {
        return new UpdateManualTicketInputDto(
            ticketId: $ticketId,
            date: null !== $dto->date ? new DateTimeImmutable($dto->date) : null,
            hours: $dto->hours,
            workId: $dto->workId,
            comment: $dto->comment,
        );
    }
}
