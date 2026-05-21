<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Validator;

use App\ProjectManagement\Infrastructure\Dto\CreateTaskRequestDto;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/** Проверяет, что задача привязана ровно к одному родителю: проекту или запросу на изменение. */
final class ExactlyOneParentValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ExactlyOneParent) {
            throw new UnexpectedTypeException($constraint, ExactlyOneParent::class);
        }

        if (!$value instanceof CreateTaskRequestDto) {
            throw new UnexpectedValueException($value, CreateTaskRequestDto::class);
        }

        $hasProject = null !== $value->projectId && '' !== $value->projectId;
        $hasCr = null !== $value->crId && '' !== $value->crId;

        if ($hasProject === $hasCr) {
            $this->context->buildViolation($constraint->message)
                ->atPath('projectId')
                ->addViolation()
            ;
        }
    }
}
