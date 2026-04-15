<?php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Transformer;

use App\Timesheet\Application\Dto\ListTicketsInputDto;
use App\Timesheet\Infrastructure\Dto\ListTicketsRequestDto;
use DateTimeImmutable;

/** Трансформирует HTTP DTO списка тикетов в Application DTO. */
final class ListTicketsInputTransformer
{
    public function transform(ListTicketsRequestDto $dto): ListTicketsInputDto
    {
        return new ListTicketsInputDto(
            employeeId: $dto->employeeId,
            projectId: $dto->projectId,
            crId: $dto->crId,
            taskId: $dto->taskId,
            workId: $dto->workId,
            dateFrom: null !== $dto->dateFrom ? new DateTimeImmutable($dto->dateFrom) : null,
            dateTo: null !== $dto->dateTo ? new DateTimeImmutable($dto->dateTo) : null,
            page: $dto->page,
            perPage: $dto->perPage,
        );
    }
}
