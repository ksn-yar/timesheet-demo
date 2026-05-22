<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Presenter;

use App\Identity\Application\Port\ListGroupsOutputPortInterface;
use App\Identity\Infrastructure\Dto\GroupListResponseDto;

/** Контракт HTTP-презентера списка групп, объединяющий порт вывода и формирование Response DTO. */
interface ListGroupsPresenterInterface extends ListGroupsOutputPortInterface
{
    public function getResponseDto(): GroupListResponseDto;
}
