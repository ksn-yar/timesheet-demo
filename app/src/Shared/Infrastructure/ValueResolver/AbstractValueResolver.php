<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\ValueResolver;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AbstractValueResolver implements ValueResolverInterface
{
    public function __construct(
        protected SerializerInterface $serializer,
        protected ValidatorInterface $validator,
    ) {}

    /**
     * @return iterable<object>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (false === $this->supports($argument)) {
            return [];
        }

        $dtoRequest = $this->deserialize($request, $argument);
        $this->validate($request, $dtoRequest);

        yield $dtoRequest;
    }

    protected function validate(Request $request, object $dtoRequest): void
    {
        $errors = $this->validator->validate($dtoRequest);
        if ($errors->count()) {
            throw new ValidationFailedException($request->getContent(), $errors);
        }
    }

    protected function supports(ArgumentMetadata $argument): bool
    {
        return $argument->getType() && is_a($argument->getType(), $this->getDtoRequestClass(), true);
    }

    /**
     * @return string Наименование класса dto запроса, над которым будет работать резолвер
     */
    abstract protected function getDtoRequestClass(): string;

    /**
     * Процесс дессериализации
     */
    abstract protected function deserialize(Request $request, ArgumentMetadata $argument): object;
}
