<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\ValueResolver;

use App\ProjectManagement\Infrastructure\Dto\ListProjectsRequestDto;
use App\Shared\Infrastructure\ValueResolver\AbstractValueResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/** Десериализует и валидирует DTO запроса на получение списка проектов из query-параметров. */
#[AsTargetedValueResolver(ListProjectsRequestDto::class)]
final class ListProjectsValueResolver extends AbstractValueResolver
{
    protected function getDtoRequestClass(): string
    {
        return ListProjectsRequestDto::class;
    }

    protected function deserialize(Request $request, ArgumentMetadata $argument): object
    {
        $query = $request->query->all();

        return new ListProjectsRequestDto(
            clientId: isset($query['clientId']) ? (string) $query['clientId'] : null,
            status: isset($query['status']) ? (string) $query['status'] : null,
            page: isset($query['page']) ? (int) $query['page'] : 1,
            perPage: isset($query['perPage']) ? (int) $query['perPage'] : 20,
        );
    }
}
