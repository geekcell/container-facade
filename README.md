# container-facade

[![Unit tests workflow status](https://github.com/geekcell/container-facade/actions/workflows/tests.yaml/badge.svg)](https://github.com/geekcell/container-facade/actions/workflows/tests.yml) [![Coverage](https://sonarcloud.io/api/project_badges/measure?project=geekcell_container-facade&metric=coverage)](https://sonarcloud.io/summary/new_code?id=geekcell_container-facade) [![Bugs](https://sonarcloud.io/api/project_badges/measure?project=geekcell_container-facade&metric=bugs)](https://sonarcloud.io/summary/new_code?id=geekcell_container-facade) [![Maintainability Rating](https://sonarcloud.io/api/project_badges/measure?project=geekcell_container-facade&metric=sqale_rating)](https://sonarcloud.io/summary/new_code?id=geekcell_container-facade) [![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=geekcell_container-facade&metric=alert_status)](https://sonarcloud.io/summary/new_code?id=geekcell_container-facade)

A standalone PHP library heavily inspired by [Laravel's Facade](https://laravel.com/docs/master/facades) implementation, which can be used with any [PSR-11 compatible](https://www.php-fig.org/psr/psr-11/) dependency injection container (DIC) such as (the ones used by) [Symfony](https://symfony.com/), [Pimple](https://github.com/silexphp/Pimple), or [Slim](https://www.slimframework.com/). 

## Installation

To use this package, require it with Composer.

```bash
composer install geekcell/container-facade
```

## Motivation

Although rare, there are situations when you want to obtain a container service without dependency injection. An example would be the [`AggregateRoot` pattern](https://martinfowler.com/bliki/DDD_Aggregate.html), which allows [dispatching domain events](https://learn.microsoft.com/en-us/dotnet/architecture/microservices/microservice-ddd-cqrs-patterns/domain-events-design-implementation) directly from the aggregate, which is usually created directly and not via a DIC. In such a case, a corresponding (static) service facade can provide a comparable convenience as a singleton, but without the inherent [disadvantages of the singleton pattern](https://stackoverflow.com/a/138012).

## Usage

Let's imagine you have a `Logger` service inside your DIC of choice that logs a message into a file.

```php
<?php

namespace App\Service;

// ...

class Logger
{
    public function __construct(
        private readonly FileWriter $writer,
    ) {
    }

    public function log(string $message, LogLevel $level = LogLevel::INFO): void
    {
        $line = sprintf(
            '%s (%s): %s', 
            (new \DateTime)->format('c'),
            $level->value,
            $message,
        );

        $this->writer->writeLine($line);
    }
}
```

If you want to "facade" this service, just create a class which extends `GeekCell\Facade\Facade`.

```php
<?php

namespace App\Support\Facade;

use App\Service\Logger as LoggerRoot;
use GeekCell\Facade\Facade;

class Logger extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'app.logger';
    }
}
```

You'll have to implement the `getFacadeAccessor()` method, which returns the identifier for service inside your DIC.

Additionally, you have to "introduce" your DIC to the Facade. How to do this really depends in the framework you're using. In Symfony, a good opportunity to do so is to override the `boot()` method within `src/Kernel.php`.

```php
<?php

namespace App;

use GeekCell\Facade\Facade;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function boot()
    {
        parent::boot();

        // This is where the magic happens!
        Facade::setContainer($this->container);
    }
}
```

To use the facade within any part of your application, just call the service as you would a static method. Behind the scenes, the call is delegated to the actual container service via `__callStatic`.

```php
<?php

// ...

use App\Support\Facade\Logger;

class SomeClass
{
    public function doStuff()
    {
        Logger::log('Calling ' __CLASS__ . '::doStuff()', LogLevel::DEBUG);

        // The acutal method logic ...
    }
}
```

### Testing

Although the above looks like an anti pattern, it's actually very testing friendly. During unit testing, you can use the `swapMock()` method to literally swap the real service with a Mockery mock.

```php
<?php

// ...

use App\Support\Facade\Logger;
use PHPUnit\Framework\TestCase;

class SomeClassTest extends TestCase
{
    public function tearDown(): void
    {
        Logger::clear();
    }

    // ...

    public function testDoStuff(): void
    {
        // Swap real service with mock
        $loggerMock = Logger::swapMock();

        // Set expectations for mock
        $loggerMock->shouldReceive('log')->once();

        $out = new SomeClass();
        $result = $out->doStuff(); // This will now call the mock!

        // Test assertions ...
    }
}
```

**Hint:** You must call the `clear()` method to clear out the internally cached mock instance. For PHPUnit, you could use the `tearDown()` method to do so.

## A Word of Caution

> With great power comes great responsibility.

While there are valid use cases, and even though service facades offer a high level of convenience, you should still use them only sparingly and **revert to standard dependency injection whenever possible**, because all facades interally rely on PHP's [`__callStatic`](https://www.php.net/manual/de/language.oop5.overloading.php#object.callstatic) magic method, which can make debugging more cumbersome/difficult.

## Examples

See the `examples` directory for various sample projects with a minimal integration of this package.

| Framwork    | Sample project                       |
| ----------- | ------------------------------------ |
| Symfony     | [examples/symfony](examples/symfony) |
