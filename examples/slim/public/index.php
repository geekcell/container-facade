<?php

declare(strict_types=1);

use App\Support\Facade\GreetingService;
use Slim\Factory\AppFactory;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

require_once __DIR__ . '/../vendor/autoload.php';

$container = require_once __DIR__ . '/../bootstrap.php';

AppFactory::setContainer($container);
$app = AppFactory::create();

$app->get('/hello/{name}', function (Request $request, Response $response, array $args) {
    $greeting = GreetingService::greet($args['name']);
    $response->getBody()->write(
        sprintf(
            '<html><body><h1>%s</h1></body></html>',
            $greeting
        )
    );

    return $response;
});

$app->run();
