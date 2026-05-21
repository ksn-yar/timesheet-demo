<?php

declare(strict_types=1);

namespace App\WorkCatalog\Infrastructure\ValueResolver;

use App\Shared\Infrastructure\ValueResolver\AbstractValueResolver;
use App\WorkCatalog\Infrastructure\Dto\ListWorksRequestDto;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/** Десериализует и валидирует DTO запроса на получение списка видов работ из query-параметров. */
#[AsTargetedValueResolver(ListWorksRequestDto::class)]
final class ListWorksValueResolver extends AbstractValueResolver
{
    protected function getDtoRequestClass(): string
    {
        return ListWorksRequestDto::class;
    }

    protected function deserialize(Request $request, ArgumentMetadata $argument): object
    {
        $query = $request->query->all();

        return new ListWorksRequestDto(
            page: isset($query['page']) ? (int) $query['page'] : 1,
            perPage: isset($query['perPage']) ? (int) $query['perPage'] : 20,
        );
    }
}
