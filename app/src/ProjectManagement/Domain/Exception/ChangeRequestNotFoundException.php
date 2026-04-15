<?php

declare(strict_types=1);

namespace App\ProjectManagement\Domain\Exception;

use DomainException;

/** Запрос на изменение не найден по указанному идентификатору. */
final class ChangeRequestNotFoundException extends DomainException
{
    public function __construct(string $id)
    {
        parent::__construct("Запрос на изменение с идентификатором «{$id}» не найден.");
    }
}
