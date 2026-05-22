<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('lexik_jwt_authentication', [
        // Пути к ключам задаются через переменные окружения JWT_SECRET_KEY, JWT_PUBLIC_KEY, JWT_PASSPHRASE
        'secret_key' => '%env(resolve:JWT_SECRET_KEY)%',
        'public_key' => '%env(resolve:JWT_PUBLIC_KEY)%',
        'pass_phrase' => '%env(JWT_PASSPHRASE)%',
        'token_ttl' => '%env(int:JWT_TTL)%',
    ]);
};
