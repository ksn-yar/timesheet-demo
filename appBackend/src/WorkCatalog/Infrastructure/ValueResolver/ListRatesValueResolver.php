<?php

declare(strict_types=1);

namespace App\WorkCatalog\Infrastructure\ValueResolver;

use App\Shared\Infrastructure\ValueResolver\AbstractValueResolver;
use App\WorkCatalog\Infrastructure\Dto\ListRatesRequestDto;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/** Десериализует и валидирует DTO запроса на получение списка ставок из query-параметров. */
#[AsTargetedValueResolver(ListRatesRequestDto::class)]
final class ListRatesValueResolver extends AbstractValueResolver
{
    protected function getDtoRequestClass(): string
    {
        return ListRatesRequestDto::class;
    }

    protected function deserialize(Request $request, ArgumentMetadata $argument): object
    {
        $query = $request->query->all();

        return new ListRatesRequestDto(
            roleId: isset($query['roleId']) ? (string) $query['roleId'] : null,
            workId: isset($query['workId']) ? (string) $query['workId'] : null,
            page: isset($query['page']) ? (int) $query['page'] : 1,
            perPage: isset($query['perPage']) ? (int) $query['perPage'] : 20,
        );
    }
}
