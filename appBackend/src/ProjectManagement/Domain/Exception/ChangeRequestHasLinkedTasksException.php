<?php

declare(strict_types=1);

namespace App\ProjectManagement\Domain\Exception;

use DomainException;

/** Невозможно удалить запрос на изменение, к которому привязаны задачи. */
final class ChangeRequestHasLinkedTasksException extends DomainException
{
    public function __construct(string $changeRequestId)
    {
        parent::__construct("Невозможно удалить запрос на изменение «{$changeRequestId}»: есть связанные задачи.");
    }
}
