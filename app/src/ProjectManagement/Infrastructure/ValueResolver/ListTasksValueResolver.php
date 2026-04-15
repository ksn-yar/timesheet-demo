<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\ValueResolver;

use App\ProjectManagement\Infrastructure\Dto\ListTasksRequestDto;
use App\Shared\Infrastructure\ValueResolver\AbstractValueResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/** Десериализует и валидирует DTO запроса на получение списка задач из query-параметров. */
#[AsTargetedValueResolver(ListTasksRequestDto::class)]
final class ListTasksValueResolver extends AbstractValueResolver
{
    protected function getDtoRequestClass(): string
    {
        return ListTasksRequestDto::class;
    }

    protected function deserialize(Request $request, ArgumentMetadata $argument): object
    {
        $query = $request->query->all();

        return new ListTasksRequestDto(
            projectId: isset($query['projectId']) ? (string) $query['projectId'] : null,
            crId: isset($query['crId']) ? (string) $query['crId'] : null,
            page: isset($query['page']) ? (int) $query['page'] : 1,
            perPage: isset($query['perPage']) ? (int) $query['perPage'] : 20,
        );
    }
}
