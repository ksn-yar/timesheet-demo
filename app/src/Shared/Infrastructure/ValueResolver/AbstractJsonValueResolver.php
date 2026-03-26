<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\ValueResolver;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

/**
 * Резолвер для запросов, которые имеют json payload.
 */
abstract class AbstractJsonValueResolver extends AbstractValueResolver
{
    /**
     * @throws ExceptionInterface
     */
    protected function deserialize(Request $request, ArgumentMetadata $argument): object
    {
        return $this->serializer->deserialize($this->getContent($request), $argument->getType(), 'json');
    }

    protected function getContent(Request $request): string
    {
        $content = $request->getContent();
        if ($content) {
            return $content;
        }

        throw new BadRequestHttpException('Empty request body.');
    }
}
