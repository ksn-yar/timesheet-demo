<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Controller;

use Symfony\Component\Routing\Attribute\Route;

/** Маршрут выхода. Тело метода никогда не выполняется — обрабатывается фаерволом Symfony Security. */
final class LogoutController
{
    #[Route('/logout', name: 'app_logout', methods: ['POST'])]
    public function __invoke(): never
    {
        throw new \LogicException('Этот метод не должен быть вызван — он перехватывается фаерволом Security.');
    }
}
