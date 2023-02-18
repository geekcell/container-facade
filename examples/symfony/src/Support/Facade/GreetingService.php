<?php

declare(strict_types=1);

namespace App\Support\Facade;

use App\Service\GreetingService as GreetingServiceRoot;
use GeekCell\Facade\Facade;

class GreetingService extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return GreetingServiceRoot::class;
    }
}
