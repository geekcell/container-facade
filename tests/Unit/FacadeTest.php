<?php

declare(strict_types=1);

namespace GeekCell\Facade\Test\Unit;

use GeekCell\Facade\Facade;
use GeekCell\Facade\Test\Fixture\Container;
use GeekCell\Facade\Test\Fixture\Service;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test facade class.
 */
class ServiceFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Service::class;
    }
}

class ErrorFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'invalid';
    }
}

class FacadeTest extends TestCase
{
    /**
     * @var Container
     */
    private Container $container;

    /**
     * @var Service
     */
    private Service $service;

    public function setUp(): void
    {
        $this->service = new Service();
        $this->container = new Container($this->service);
    }

    public function tearDown(): void
    {
        ServiceFacade::clear();
    }

    public function testGetFacadeRoot(): void
    {
        // Given
        ServiceFacade::setContainer($this->container);

        // When
        $instance = ServiceFacade::getFacadeRoot();
        $another = ServiceFacade::getFacadeRoot();

        // Then
        $this->assertSame($this->service, $instance);
        $this->assertSame($this->service, $another);
        $this->assertSame($instance, $another);
    }

    public function testGetFacadeRootWithoutContainer(): void
    {
        // Given
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('A PSR-11 compatible container has not been set.');

        // When - Then
        ServiceFacade::getFacadeRoot();
    }

    public function testGetFacadeRootWithoutObject(): void
    {
        // Given
        ServiceFacade::setContainer(new Container());

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage(
            sprintf('The entry for "%s" must return an object.', Service::class)
        );

        // When - Then
        ServiceFacade::getFacadeRoot();
    }

    public function testCallStatic(): void
    {
        // Given
        ServiceFacade::setContainer($this->container);

        // When
        $result = ServiceFacade::greeting('World'); // @phpstan-ignore-line

        // Then
        $this->assertSame($this->service->greeting('World'), $result);
    }

    public function testCallStaticWithMissingMethod(): void
    {
        // Given
        ServiceFacade::setContainer($this->container);

        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage(
            sprintf('Method "invalid" does not exist on "%s".', Service::class),
        );

        // When - Then
        ServiceFacade::invalid(); // @phpstan-ignore-line
    }

    public function testGetFacadeRootUsesSwappedValueWithoutContainer(): void
    {
        $service = new Service();
        ServiceFacade::swap($service);

        $root = ServiceFacade::getFacadeRoot();

        $this->assertSame($root, $service);
    }

    public function testSwap(): void
    {
        // Given
        ServiceFacade::setContainer($this->container);

        $root = ServiceFacade::getFacadeRoot();
        $this->assertSame($this->service, $root);

        $otherService = new Service();
        ServiceFacade::swap($otherService);

        $otherRoot = ServiceFacade::getFacadeRoot();
        $this->assertSame($otherRoot, $otherService);
        $this->assertNotSame($root, $otherRoot);
    }

    public function testSettingContainerClearsFacadeCache(): void
    {
        $firstService = new Service();
        $firstContainer = new Container($firstService);
        ServiceFacade::setContainer($firstContainer);
        $firstRoot = ServiceFacade::getFacadeRoot();
        $this->assertSame($firstRoot, $firstService);

        $secondService = new Service();
        $secondContainer = new Container($secondService);
        ServiceFacade::setContainer($secondContainer);
        $secondRoot = ServiceFacade::getFacadeRoot();
        $this->assertSame($secondRoot, $secondService);

        $this->assertNotSame($firstRoot, $secondRoot);
    }
}
