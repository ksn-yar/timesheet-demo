<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Serializer\Exception\MissingConstructorArgumentsException;
use Symfony\Component\Validator\Exception\ValidationFailedException;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('framework', [
        'secret' => '%env(APP_SECRET)%',
        'session' => [
            'handler_id' => '%env(REDIS_DSN)%',
        ],
        // Маппинг исключений на HTTP-статусы
        'exceptions' => [
            // ValidationFailedException бросается из AbstractValueResolver при провале Symfony Validator
            ValidationFailedException::class => [
                'status_code' => 400,
            ],
            // MissingConstructorArgumentsException бросается сериализатором когда обязательное поле отсутствует в запросе
            MissingConstructorArgumentsException::class => [
                'status_code' => 400,
            ],
        ],
    ]);
    if ('test' === $containerConfigurator->env()) {
        $containerConfigurator->extension('framework', [
            'test' => true,
            'session' => [
                'storage_factory_id' => 'session.storage.factory.mock_file',
            ],
        ]);
    }
};
