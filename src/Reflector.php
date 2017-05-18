<?php
namespace Canister;

use Psr\SimpleCache\CacheInterface;

/**
 * Class Reflector
 *
 * @package Canister
 */
class Reflector
{
    /**
     *
     */
    const CACHE_CALLABLE = '_cache.callable.';

    /**
     * @var CacheArray|null|CacheInterface
     */
    private $cache;

    /**
     * @var CanisterInterface|null
     */
    private $container;

    /**
     * Reflector constructor.
     *
     * @param null|CacheInterface $cache
     * @param CanisterInterface|null $container
     */
    public function __construct(?CacheInterface $cache = null, ?CanisterInterface $container = null)
    {
        $this->cache = $cache ?? new CacheArray();
        $this->container = $container;
    }

    /**
     * @param CanisterInterface $container
     */
    public function setContainer(CanisterInterface $container) : void
    {
        $this->container = $container;
    }

    /**
     * @param string $class
     * @param $value
     * @param null $ttl
     */
    public function store(string $class, $value, $ttl = null) : void
    {
        $this->cache->set($class, $value, $ttl);
    }

    /**
     * @param string $name
     * @param callable $closure
     * @param bool $factory
     *
     * @return mixed|null
     */
    public function resolveCallableString(string $name, callable $closure, $factory = false)
    {
        if($factory === true) {
            return $this->resolveCallable($name, $closure);
        }

        return $this->resolveCallableFromCache($name, $closure);
    }

    /**
     * @param string $name
     * @param callable $closure
     *
     * @return mixed|null
     */
    public function resolveCallableFromCache(string $name, callable $closure)
    {
        if($this->cache->has(self::CACHE_CALLABLE . $name)) {
            return $this->cache->get(self::CACHE_CALLABLE . $name);
        }

        $resolved = $this->resolveCallable($name, $closure);

        //cache closure
        $this->store(self::CACHE_CALLABLE . $name, $resolved);

        return $resolved;
    }

    /**
     * @param string|null $name
     * @param callable $closure
     * @param array|null $parameters
     *
     * @return mixed
     */
    public function resolveCallable(?string $name, callable $closure, ?array $parameters = null)
    {
        $definitions = isset($name) && $this->container->isDefined($name)
            ? $this->container->getDefiniition($name) : null;

        $callable = $this->getCallable($closure);
        $parameters = $parameters ?? $this->resolveParameters($callable->getParameters() ?? null, $definitions);

        return $callable->invokeArgs($parameters);
    }

    /**
     * @param string $class
     * @param bool $factory
     *
     * @return mixed|null|object
     */
    public function resolveClassString(string $class, $factory = false)
    {
        if($factory === true) {
            return $this->resolveClass($class);
        }

        return $this->resolveClassFromCache($class);
    }

    /**
     * @param string $class
     *
     * @return object
     */
    public function resolveClass(string $class)
    {
        $object = $this->getClass($class);

        $definitions = $this->container->isDefined($object->getName())
            ? $this->container->getDefiniition($object->getName()) : null;

        $constructor_parameters = $object->getConstructor() !== null ? $object->getConstructor()->getParameters() : null;
        $parameters = $this->resolveParameters($constructor_parameters ?? [], $definitions);

        return $object->newInstanceArgs($parameters);
    }

    /**
     * @param string $class
     *
     * @return mixed|null|object
     */
    public function resolveClassFromCache(string $class)
    {
        if($this->cache->has($class)) {
            return $this->cache->get($class);
        }

        $resolved = $this->resolveClass($class);

        //cache class
        $this->store($class, $resolved);

        return $resolved;
    }

    /**
     * @param array|null $params
     * @param array|null $definitions
     *
     * @return array
     */
    private function resolveParameters(?array $params = [], ?array $definitions = null) : array
    {
        $parameters = [];

        //method doesn't exist so there's no parameters
        if(!isset($params) || empty($params)) {
            return $parameters;
        }

        /** @var \ReflectionParameter $parameter */
        foreach($params as $parameter) {

            //handle definitions
            if(isset($definitions, $definitions[$parameter->name])
                && is_a($definitions[$parameter->name], Definition::class)) {

                /** @var Definition $definition */
                $definition = $definitions[$parameter->name];
                $parameters[] = $definition->getType() == Definition::CONTAINER
                    ? $this->container->get($definition->getValue()) : $definition->getValue();

            } elseif($parameter->isDefaultValueAvailable()) {
                $parameters[] = $parameter->getDefaultValue();
            } elseif($parameter->allowsNull() || $parameter->isOptional() || !$parameter->hasType()) {
                $parameters[] = null;
            } elseif($parameter->getClass() !== null) {

                $parameter_class = $parameter->getClass();
                $parameter_class_name = $parameter_class->getName();

                try {
                    $parameters[] = $this->container->get($parameter_class_name);
                } catch(\Exception $e) {
                    $parameters[] = null;
                }

            } elseif($parameter->isCallable()) {
                $parameters[] = function(){};
            } elseif($parameter->isArray()) {
                $parameters[] = [];
            } else {
                $parameters[] = $this->resolveDefaultType($parameter->getType());
            }
        }

        return $parameters;
    }

    /**
     * @param $type
     *
     * @return array|float|int|null
     */
    private function resolveDefaultType($type)
    {
        switch($type) {
            case 'int': return 0; break;
            case 'float': return 0.0; break;
            case 'string': return null; break;
            case 'iterable': return []; break;
            default: return null; break;
        }
    }

    /**
     * @param string $class
     *
     * @return \ReflectionClass
     */
    private function getClass(string $class) : \ReflectionClass
    {
        return new \ReflectionClass($class);
    }

    /**
     * @param callable $callable
     *
     * @return \ReflectionFunction
     */
    private function getCallable(callable $callable) : \ReflectionFunction
    {
        return new \ReflectionFunction($callable);
    }
}