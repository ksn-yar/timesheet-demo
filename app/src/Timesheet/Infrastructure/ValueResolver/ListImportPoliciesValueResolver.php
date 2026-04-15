<?php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\ValueResolver;

use App\Shared\Infrastructure\ValueResolver\AbstractValueResolver;
use App\Timesheet\Infrastructure\Dto\ListImportPoliciesRequestDto;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/** Десериализует и валидирует DTO запроса на получение списка политик импорта из query-параметров. */
#[AsTargetedValueResolver(ListImportPoliciesRequestDto::class)]
final class ListImportPoliciesValueResolver extends AbstractValueResolver
{
    protected function getDtoRequestClass(): string
    {
        return ListImportPoliciesRequestDto::class;
    }

    protected function deserialize(Request $request, ArgumentMetadata $argument): object
    {
        $query = $request->query->all();

        return new ListImportPoliciesRequestDto(
            page: isset($query['page']) ? (int) $query['page'] : 1,
            perPage: isset($query['perPage']) ? (int) $query['perPage'] : 20,
        );
    }
}
