<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Presenter;

use App\Identity\Application\Dto\ListUsersOutputDto;
use App\Identity\Domain\Enum\SystemRole;
use App\Identity\Infrastructure\Dto\UserListResponseDto;
use App\Identity\Infrastructure\Dto\UserResponseDto;
use LogicException;

/** HTTP-презентер результата Use Case получения списка пользователей. Формирует Response DTO. */
final class HttpListUsersPresenter implements ListUsersPresenterInterface
{
    private ?ListUsersOutputDto $dto = null;

    public function present(ListUsersOutputDto $dto): void
    {
        $this->dto = $dto;
    }

    public function getResponseDto(): UserListResponseDto
    {
        if (null === $this->dto) {
            throw new LogicException('Presenter has not been called yet.');
        }

        $items = array_map(
            static fn ($item) => new UserResponseDto(
                id: $item->id,
                name: $item->name,
                email: $item->email,
                systemRole: $item->systemRole,
                systemRoleLabel: SystemRole::from($item->systemRole)->getLabel(),
                groupId: $item->groupId,
                groupName: $item->groupName,
                roleId: $item->roleId,
                roleName: $item->roleName,
                isActive: $item->isActive,
            ),
            $this->dto->items,
        );

        return new UserListResponseDto(
            items: $items,
            total: $this->dto->total,
            page: $this->dto->page,
            perPage: $this->dto->perPage,
        );
    }
}
