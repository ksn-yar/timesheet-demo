<?php

declare(strict_types=1);

namespace App\ProjectManagement\Domain\Exception;

use DomainException;

/** Невозможно удалить проект, к которому привязаны задачи или запросы на изменение. */
final class ProjectHasLinkedEntitiesException extends DomainException
{
    public function __construct(string $projectId)
    {
        parent::__construct("Невозможно удалить проект «{$projectId}»: есть связанные сущности.");
    }
}
