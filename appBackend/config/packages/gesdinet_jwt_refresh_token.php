<?php

declare(strict_types=1);

use App\Persistence\Entity\UserRefreshToken;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('gesdinet_jwt_refresh_token', [
        // Используем собственную сущность вместо дефолтной из бандла
        'refresh_token_class' => UserRefreshToken::class,
        'ttl' => '%env(int:JWT_REFRESH_TOKEN_TTL)%',
        // Режим одноразового использования: при каждом refresh старый токен удаляется, выдаётся новый
        'single_use' => true,
    ]);
};
