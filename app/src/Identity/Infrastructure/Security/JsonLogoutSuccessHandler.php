<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Event\LogoutEvent;

/**
 * Подписчик на событие выхода из системы. Заменяет стандартный редирект Symfony
 * JSON-ответом, пригодным для SPA.
 */
final class JsonLogoutSuccessHandler implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function onLogout(LogoutEvent $event): void
    {
        $event->setResponse(new JsonResponse(
            ['message' => 'Выход выполнен.'],
            Response::HTTP_OK,
        ));
    }
}
