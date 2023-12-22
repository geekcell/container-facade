<?php

declare(strict_types=1);

namespace GeekCell\Facade;

use Mockery;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class Facade.
 *
 * @package GeekCell\Facade
 */
abstract class Facade
{
    /**
     * @var null|ContainerInterface
     */
    protected static ?ContainerInterface $container = null;

    /**
     * @var array<object>
     */
    protected static array $resolvedInstances = [];

    /**
     * Return the underlying instance behind the facade.
     *
     * @return object
     *
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws \LogicException
     * @throws \UnexpectedValueException
     */
    public static function getFacadeRoot(): object
    {
        $accessor = static::getFacadeAccessor();
        if (isset(static::$resolvedInstances[$accessor])) {
            return static::$resolvedInstances[$accessor];
        }

        if (!isset(static::$container)) {
            throw new \LogicException('A PSR-11 compatible container has not been set.');
        }

        $instance = static::$container->get($accessor);
        if (!is_object($instance)) {
            throw new \UnexpectedValueException(
                sprintf('The entry for "%s" must return an object.', $accessor),
            );
        }

        self::$resolvedInstances[$accessor] = $instance;

        return $instance;
    }

    /**
     * Calls the method on the facade root instance.
     *
     * @param string $method
     * @param array<mixed> $args
     *
     * @return string
     */
    public static function __callStatic(string $method, array $args): mixed
    {
        $instance = static::getFacadeRoot();
        if (!method_exists($instance, $method)) {
            throw new \BadMethodCallException(
                sprintf('Method "%s" does not exist on "%s".', $method, get_class($instance)),
            );
        }

        return $instance->{$method}(...$args);
    }

    /**
     * Clears the resolved instances.
     *
     * @codeCoverageIgnore
     *
     * @return void
     */
    public static function clear(): void
    {
        static::$resolvedInstances = [];
        static::$container = null;
    }

    /**
     * Return the class name for the underlying instance behind the facade.
     *
     * @return string
     */
    public static function getMockableClass(): string
    {
        $instance = static::getFacadeRoot();
        return get_class($instance);
    }

    /**
     * Return a Mockery mock instance of the underlying instance behind the facade.
     *
     * @return Mockery\MockInterface
     *
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws \LogicException
     * @throws \UnexpectedValueException
     */
    public static function createMock(): Mockery\MockInterface
    {
        $classToMock = static::getMockableClass();
        return Mockery::mock($classToMock);
    }

    /**
     * Return a Mockery mock instance, but also swap the underlying instance behind the facade
     * so consecutive calls to the facade will use the mock.
     *
     * This behavior can be reset by calling Facade::clear().
     *
     * @return Mockery\MockInterface
     *
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws \LogicException
     * @throws \UnexpectedValueException
     */
    public static function swapMock(): Mockery\MockInterface
    {
        $accessor = static::getFacadeAccessor();
        $mock = static::createMock();
        static::$resolvedInstances[$accessor] = $mock;

        return $mock;
    }

    /**
     * Set the container instance. Reset any cache instances in the process.
     *
     * @codeCoverageIgnore
     *
     * @param ContainerInterface $container
     * @return void
     */
    public static function setContainer(ContainerInterface $container): void
    {
        static::clear();

        static::$container = $container;
    }

    /**
     * Return the container instance or null if not set.
     *
     * @codeCoverageIgnore
     *
     * @return null|ContainerInterface
     */
    public static function getContainer(): ?ContainerInterface
    {
        return static::$container;
    }

    /**
     * Return the identifier of the root service/object being facaded.
     *
     * @return string
     */
    abstract protected static function getFacadeAccessor(): string;
}
