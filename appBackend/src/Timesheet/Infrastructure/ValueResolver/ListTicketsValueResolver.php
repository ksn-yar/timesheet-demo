<?php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\ValueResolver;

use App\Shared\Infrastructure\ValueResolver\AbstractValueResolver;
use App\Timesheet\Infrastructure\Dto\ListTicketsRequestDto;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/** Десериализует и валидирует DTO запроса на получение списка тикетов из query-параметров. */
#[AsTargetedValueResolver(ListTicketsRequestDto::class)]
final class ListTicketsValueResolver extends AbstractValueResolver
{
    protected function getDtoRequestClass(): string
    {
        return ListTicketsRequestDto::class;
    }

    protected function deserialize(Request $request, ArgumentMetadata $argument): object
    {
        $query = $request->query->all();

        return new ListTicketsRequestDto(
            employeeId: isset($query['employeeId']) ? (string) $query['employeeId'] : null,
            projectId: isset($query['projectId']) ? (string) $query['projectId'] : null,
            crId: isset($query['crId']) ? (string) $query['crId'] : null,
            taskId: isset($query['taskId']) ? (string) $query['taskId'] : null,
            workId: isset($query['workId']) ? (string) $query['workId'] : null,
            dateFrom: isset($query['dateFrom']) ? (string) $query['dateFrom'] : null,
            dateTo: isset($query['dateTo']) ? (string) $query['dateTo'] : null,
            page: isset($query['page']) ? (int) $query['page'] : 1,
            perPage: isset($query['perPage']) ? (int) $query['perPage'] : 20,
        );
    }
}
