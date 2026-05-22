<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\ValueResolver;

use App\Identity\Infrastructure\Dto\ListUsersRequestDto;
use App\Shared\Infrastructure\ValueResolver\AbstractValueResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/** Десериализует и валидирует DTO запроса на получение списка пользователей из query-параметров. */
#[AsTargetedValueResolver(ListUsersRequestDto::class)]
final class ListUsersValueResolver extends AbstractValueResolver
{
    protected function getDtoRequestClass(): string
    {
        return ListUsersRequestDto::class;
    }

    protected function deserialize(Request $request, ArgumentMetadata $argument): object
    {
        $query = $request->query->all();

        $isActive = null;
        if (isset($query['isActive'])) {
            $isActive = filter_var($query['isActive'], \FILTER_VALIDATE_BOOLEAN, \FILTER_NULL_ON_FAILURE);
        }

        return new ListUsersRequestDto(
            groupId: isset($query['groupId']) ? (string) $query['groupId'] : null,
            roleId: isset($query['roleId']) ? (string) $query['roleId'] : null,
            isActive: $isActive,
            page: isset($query['page']) ? (int) $query['page'] : 1,
            perPage: isset($query['perPage']) ? (int) $query['perPage'] : 20,
        );
    }
}
