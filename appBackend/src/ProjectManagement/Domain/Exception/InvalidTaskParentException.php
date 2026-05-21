<?php

declare(strict_types=1);

namespace App\ProjectManagement\Domain\Exception;

use DomainException;

/** Задача должна принадлежать либо проекту, либо запросу на изменение. */
final class InvalidTaskParentException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Задача должна быть привязана к проекту или запросу на изменение.');
    }
}
