<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

// Файл намеренно пуст: logout-маршрут удалён при переходе на JWT stateless аутентификацию.
// JWT не требует серверного logout — токен инвалидируется на стороне клиента.
return static function (RoutingConfigurator $routingConfigurator): void {};
