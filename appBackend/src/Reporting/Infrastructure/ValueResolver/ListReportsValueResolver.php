<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\ValueResolver;

use App\Reporting\Infrastructure\Dto\ListReportsRequestDto;
use App\Shared\Infrastructure\ValueResolver\AbstractValueResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/** Десериализует и валидирует DTO запроса на получение списка отчётов из query-параметров. */
#[AsTargetedValueResolver(ListReportsRequestDto::class)]
final class ListReportsValueResolver extends AbstractValueResolver
{
    protected function getDtoRequestClass(): string
    {
        return ListReportsRequestDto::class;
    }

    protected function deserialize(Request $request, ArgumentMetadata $argument): object
    {
        $query = $request->query->all();

        return new ListReportsRequestDto(
            createdBy: isset($query['createdBy']) ? (string) $query['createdBy'] : null,
            periodFrom: isset($query['periodFrom']) ? (string) $query['periodFrom'] : null,
            periodTo: isset($query['periodTo']) ? (string) $query['periodTo'] : null,
            name: isset($query['name']) ? (string) $query['name'] : null,
            page: isset($query['page']) ? (int) $query['page'] : 1,
            perPage: isset($query['perPage']) ? (int) $query['perPage'] : 20,
        );
    }
}
