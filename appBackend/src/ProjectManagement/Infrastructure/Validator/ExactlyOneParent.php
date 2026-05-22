<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;

/** Правило валидации: задача должна принадлежать ровно одному родителю (проект или запрос на изменение). */
#[Attribute(Attribute::TARGET_CLASS)]
final class ExactlyOneParent extends Constraint
{
    public string $message = 'Необходимо указать ровно одно из полей: projectId или crId.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
