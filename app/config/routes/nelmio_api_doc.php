<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routingConfigurator): void {
    $routingConfigurator->add('app.swagger', '/swagger/doc.json')
        ->controller('nelmio_api_doc.controller.swagger')
        ->methods([
            'GET',
        ])
    ;

    $routingConfigurator->add('app.swagger_ui', '/swagger/doc')
        ->controller('nelmio_api_doc.controller.swagger_ui')
        ->methods([
            'GET',
        ])
    ;
};
