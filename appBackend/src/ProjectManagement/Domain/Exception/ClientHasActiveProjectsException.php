<?php

declare(strict_types=1);

namespace App\ProjectManagement\Domain\Exception;

use DomainException;

/** Невозможно удалить клиента, у которого есть активные проекты. */
final class ClientHasActiveProjectsException extends DomainException
{
    public function __construct(string $clientId)
    {
        parent::__construct("Невозможно удалить клиента «{$clientId}»: есть активные проекты.");
    }
}
