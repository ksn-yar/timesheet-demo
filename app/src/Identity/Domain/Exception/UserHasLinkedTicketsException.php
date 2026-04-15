<?php

declare(strict_types=1);

namespace App\Identity\Domain\Exception;

use DomainException;

/** Невозможно удалить пользователя с привязанными тикетами. */
final class UserHasLinkedTicketsException extends DomainException
{
    public function __construct(string $userId)
    {
        parent::__construct("Невозможно удалить пользователя «{$userId}»: есть привязанные тикеты. Деактивируйте пользователя вместо удаления.");
    }
}
