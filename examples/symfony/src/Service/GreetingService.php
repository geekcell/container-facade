<?php

declare(strict_types=1);

namespace App\Service;

class GreetingService
{
    public function greet(string $name): string
    {
        return sprintf('Hello, %s!', ucfirst($name));
    }
}
