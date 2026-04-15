<?php

declare(strict_types=1);

namespace App\ProjectManagement\Domain\Exception;

use DomainException;

/** Клиент не найден по указанному идентификатору. */
final class ClientNotFoundException extends DomainException
{
    public function __construct(string $id)
    {
        parent::__construct("Клиент с идентификатором «{$id}» не найден.");
    }
}
