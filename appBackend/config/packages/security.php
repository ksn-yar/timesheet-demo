<?php

declare(strict_types=1);

use App\Identity\Infrastructure\Security\JsonAccessDeniedHandler;
use App\Identity\Infrastructure\Security\JsonAuthenticationEntryPoint;
use App\Identity\Infrastructure\Security\JsonLoginSuccessHandler;
use App\Identity\Infrastructure\Security\UserProvider;
use App\Persistence\Entity\User;
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
        'firewalls' => [
            'dev' => [
                'pattern' => '^/(_profiler|_wdt|assets|build)/',
                'security' => false,
            ],
            'api' => [
                'pattern' => '^/api/',
                'stateless' => false,
                'provider' => 'app_user_provider',
                'json_login' => [
                    'check_path' => '/api/login',
                    'username_path' => 'email',
                    'password_path' => 'password',
                    'success_handler' => JsonLoginSuccessHandler::class,
                    // Стандартный JsonLoginFailureHandler возвращает 401 JSON — подходит без кастомизации
                ],
                'logout' => [
                    'path' => '/api/logout',
                    // Очищаем куки сессии и инвалидируем сессию при выходе
                    'invalidate_session' => true,
                ],
                'access_denied_handler' => JsonAccessDeniedHandler::class,
                'entry_point' => JsonAuthenticationEntryPoint::class,
            ],
        ],
        'access_control' => [
            ['path' => '^/api/login$', 'roles' => 'PUBLIC_ACCESS'],
            ['path' => '^/api/logout$', 'roles' => 'ROLE_USER'],
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
