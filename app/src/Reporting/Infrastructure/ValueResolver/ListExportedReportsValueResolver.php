<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\ValueResolver;

use App\Reporting\Infrastructure\Dto\ListExportedReportsRequestDto;
use App\Shared\Infrastructure\ValueResolver\AbstractValueResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/** Десериализует и валидирует DTO запроса на получение списка выгрузок из query-параметров. */
#[AsTargetedValueResolver(ListExportedReportsRequestDto::class)]
final class ListExportedReportsValueResolver extends AbstractValueResolver
{
    protected function getDtoRequestClass(): string
    {
        return ListExportedReportsRequestDto::class;
    }

    protected function deserialize(Request $request, ArgumentMetadata $argument): object
    {
        $query = $request->query->all();

        return new ListExportedReportsRequestDto(
            format: isset($query['format']) ? (string) $query['format'] : null,
            generatedAtFrom: isset($query['generatedAtFrom']) ? (string) $query['generatedAtFrom'] : null,
            generatedAtTo: isset($query['generatedAtTo']) ? (string) $query['generatedAtTo'] : null,
            page: isset($query['page']) ? (int) $query['page'] : 1,
            perPage: isset($query['perPage']) ? (int) $query['perPage'] : 20,
        );
    }
}
