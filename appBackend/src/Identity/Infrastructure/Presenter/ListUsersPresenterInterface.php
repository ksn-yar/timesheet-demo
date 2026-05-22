<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Presenter;

use App\Identity\Application\Port\ListUsersOutputPortInterface;
use App\Identity\Infrastructure\Dto\UserListResponseDto;

/** Контракт HTTP-презентера списка пользователей, объединяющий порт вывода и формирование Response DTO. */
interface ListUsersPresenterInterface extends ListUsersOutputPortInterface
{
    public function getResponseDto(): UserListResponseDto;
}
