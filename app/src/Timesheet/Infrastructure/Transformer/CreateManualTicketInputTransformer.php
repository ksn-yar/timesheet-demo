<?php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Transformer;

use App\Timesheet\Application\Dto\CreateManualTicketInputDto;
use App\Timesheet\Infrastructure\Dto\CreateManualTicketRequestDto;
use DateTimeImmutable;

/** Трансформирует HTTP DTO создания тикета в Application DTO. */
final class CreateManualTicketInputTransformer
{
    public function transform(CreateManualTicketRequestDto $dto): CreateManualTicketInputDto
    {
        return new CreateManualTicketInputDto(
            employeeId: $dto->employeeId,
            taskId: $dto->taskId,
            workId: $dto->workId,
            date: new DateTimeImmutable($dto->date),
            hours: $dto->hours,
            comment: $dto->comment,
        );
    }
}
