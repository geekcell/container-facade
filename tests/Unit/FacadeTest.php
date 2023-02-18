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

    public function testGetMockableClass(): void
    {
        // Given
        ServiceFacade::setContainer($this->container);

        // When
        $result = ServiceFacade::getMockableClass();

        // Then
        $this->assertSame(Service::class, $result);
    }

    public function testCreateMock(): void
    {
        // Given
        ServiceFacade::setContainer($this->container);

        // When
        $mock = ServiceFacade::createMock();

        // Then
        $this->assertInstanceOf(Mockery\MockInterface::class, $mock);
    }

    public function testSwapMock(): void
    {
        // Given
        ServiceFacade::setContainer($this->container);

        // When
        $mock = ServiceFacade::swapMock();
        $mock->shouldReceive('greeting')->andReturn('Hello Mock!');

        // Then
        $this->assertInstanceOf(Mockery\MockInterface::class, $mock);

        $result = ServiceFacade::greeting('World'); // @phpstan-ignore-line
        $this->assertEquals('Hello Mock!', $result);

        ServiceFacade::clear();
        ServiceFacade::setContainer($this->container);
        $result = ServiceFacade::greeting('World'); // @phpstan-ignore-line
        $this->assertEquals('Hello World!', $result);
    }
}
