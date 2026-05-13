<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * Точка входа для неаутентифицированных запросов. Возвращает 401 в формате JSON
 * вместо редиректа на форму логина.
 */
final class JsonAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new JsonResponse(
            ['error' => 'Требуется аутентификация.'],
            Response::HTTP_UNAUTHORIZED,
        );
    }
}
