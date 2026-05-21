<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\ValueResolver;

use App\ProjectManagement\Infrastructure\Dto\ListChangeRequestsRequestDto;
use App\Shared\Infrastructure\ValueResolver\AbstractValueResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/** Десериализует и валидирует DTO запроса на получение списка запросов на изменение из query-параметров. */
#[AsTargetedValueResolver(ListChangeRequestsRequestDto::class)]
final class ListChangeRequestsValueResolver extends AbstractValueResolver
{
    protected function getDtoRequestClass(): string
    {
        return ListChangeRequestsRequestDto::class;
    }

    protected function deserialize(Request $request, ArgumentMetadata $argument): object
    {
        $query = $request->query->all();

        return new ListChangeRequestsRequestDto(
            projectId: isset($query['projectId']) ? (string) $query['projectId'] : null,
            name: isset($query['name']) ? (string) $query['name'] : null,
            page: isset($query['page']) ? (int) $query['page'] : 1,
            perPage: isset($query['perPage']) ? (int) $query['perPage'] : 20,
        );
    }
}
