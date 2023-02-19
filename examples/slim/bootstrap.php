<?php

declare(strict_types=1);

use App\Service\GreetingService;
use DI\Container;
use GeekCell\Facade\Facade;

require_once __DIR__ . '/vendor/autoload.php';

$container = new Container();

$container->set(GreetingService::class, static function (Container $c) {
    return new GreetingService();
});

Facade::setContainer($container);

return $container;
