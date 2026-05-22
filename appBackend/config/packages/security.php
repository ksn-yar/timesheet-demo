<?php

declare(strict_types=1);

use App\Persistence\Entity\User;
use App\Shared\Infrastructure\Security\JsonAccessDeniedHandler;
use App\Shared\Infrastructure\Security\JsonAuthenticationEntryPoint;
use App\Shared\Infrastructure\Security\UserProvider;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('security', [
        'password_hashers' => [
            // Хэшер привязан к ORM-сущности, реализующей PasswordAuthenticatedUserInterface
            User::class => 'auto',
        ],
        'providers' => [
            'app_user_provider' => [
                'id' => UserProvider::class,
            ],
        ],
        'role_hierarchy' => [
            'ROLE_ADMIN' => ['ROLE_EMPLOYEE'],
        ],
        'firewalls' => [
            'dev' => [
                'pattern' => '^/(_profiler|_wdt|assets|build)/',
                'security' => false,
            ],
            'api' => [
                'pattern' => '^/api/',
                // JWT-аутентификация — каждый запрос самодостаточен, сессия не нужна
                'stateless' => true,
                'provider' => 'app_user_provider',
                'jwt' => null,
                'access_denied_handler' => JsonAccessDeniedHandler::class,
                'entry_point' => JsonAuthenticationEntryPoint::class,
            ],
        ],
        'access_control' => [
            // Эндпоинт логина открыт для всех
            ['path' => '^/api/auth/login$', 'roles' => 'PUBLIC_ACCESS'],
            // Обновление токена не требует действующего access token
            ['path' => '^/api/auth/refresh$', 'roles' => 'PUBLIC_ACCESS'],
            // Логаут требует аутентификации — нужен валидный access token
            ['path' => '^/api/auth/logout$', 'roles' => 'ROLE_USER'],
            // Все остальные API-маршруты требуют аутентификации
            ['path' => '^/api/', 'roles' => 'ROLE_USER'],
        ],
    ]);

    if ('test' === $containerConfigurator->env()) {
        $containerConfigurator->extension('security', [
            'password_hashers' => [
                User::class => [
                    'algorithm' => 'auto',
                    'cost' => 4,
                    'time_cost' => 3,
                    'memory_cost' => 10,
                ],
            ],
        ]);
    }
};
