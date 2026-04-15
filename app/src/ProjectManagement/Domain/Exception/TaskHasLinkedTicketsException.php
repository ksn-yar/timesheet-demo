<?php

declare(strict_types=1);

namespace App\ProjectManagement\Domain\Exception;

use DomainException;

/** Невозможно удалить задачу, к которой привязаны тикеты. */
final class TaskHasLinkedTicketsException extends DomainException
{
    public function __construct(string $taskId)
    {
        parent::__construct("Невозможно удалить задачу «{$taskId}»: есть связанные тикеты.");
    }
}
