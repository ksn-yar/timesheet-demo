<?php

declare(strict_types=1);

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
            'main' => [
                'pattern' => '^/',
                'stateless' => false,
                'provider' => 'app_user_provider',
                'form_login' => [
                    'login_path' => 'app_login',
                    'check_path' => 'app_login',
                    'username_parameter' => 'email',
                    'password_parameter' => 'password',
                    'default_target_path' => '/',
                    'enable_csrf' => true,
                ],
                'logout' => [
                    'path' => 'app_logout',
                    'target' => 'app_login',
                    'invalidate_session' => true,
                ],
            ],
        ],
        'access_control' => [
            ['path' => '^/login$', 'roles' => 'PUBLIC_ACCESS'],
            ['path' => '^/', 'roles' => 'ROLE_USER'],
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
