<?php

declare(strict_types=1);

namespace App\Controller;

use App\Support\Facade\GreetingService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HelloController
{
    #[Route('/hello/{name}', name: 'hello')]
    public function hello(string $name): Response
    {
        $greeting = GreetingService::greet($name);
        return new Response(
            '<html><body><h1>' . $greeting . '</h1></body></html>',
        );
    }
}
