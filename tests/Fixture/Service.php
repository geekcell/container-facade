<?php

declare(strict_types=1);

namespace GeekCell\Facade\Test\Fixture;

class Service
{
    public function greeting(string $name): string
    {
        return "Hello {$name}!";
    }
}
