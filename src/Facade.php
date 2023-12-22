<?php

declare(strict_types=1);

namespace GeekCell\Facade;

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
    protected static array $swappedInstances = [];

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
        if (isset(static::$swappedInstances[$accessor])) {
            return static::$swappedInstances[$accessor];
        }

        if (!isset(static::$container)) {
            throw new \LogicException('A PSR-11 compatible container has not been set.');
        }

        $instance = static::$container->get($accessor);
        if (!is_object($instance)) {
            throw new \UnexpectedValueException(
                sprintf('The entry for "%s" must return an object. Got: %s', $accessor, get_debug_type($instance)),
            );
        }

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
        static::$swappedInstances = [];
        static::$container = null;
    }

    /**
     * @template T of object
     *
     * @param T $newInstance
     * @return T
     */
    public static function swap($newInstance)
    {
        $accessor = static::getFacadeAccessor();
        static::$swappedInstances[$accessor] = $newInstance;

        return $newInstance;
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
