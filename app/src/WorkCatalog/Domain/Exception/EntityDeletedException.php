<?php

declare(strict_types=1);

namespace App\WorkCatalog\Domain\Exception;

use DomainException;

/** Попытка изменить удалённую сущность. */
final class EntityDeletedException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Сущность удалена и не может быть изменена');
    }
}
