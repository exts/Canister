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
    public function setContainer(CanisterInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $class
     * @param $value
     * @param null $ttl
     */
    public function store(string $class, $value, $ttl = null)
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
        $callable = $this->getCallable($closure);

        if($factory === true) {
            return $this->resolveCallable($name, $callable);
        }

        return $this->resolveCallableFromCache($name, $closure, $callable);
    }

    /**
     * @param string $name
     * @param \ReflectionFunction $callable
     *
     * @return mixed
     */
    private function resolveCallable(string $name, \ReflectionFunction $callable)
    {
        $definitions = $this->container->isDefined($name)
            ? $this->container->getDefiniition($name) : null;

        $parameters = $this->resolveParameters($callable->getParameters() ?? null, $definitions);

        return $callable->invokeArgs($parameters);
    }

    /**
     * @param string $name
     * @param callable $closure
     * @param null|\ReflectionFunction $callable
     *
     * @return mixed|null
     */
    private function resolveCallableFromCache(string $name, callable $closure, ?\ReflectionFunction $callable = null)
    {
        if($this->cache->has(self::CACHE_CALLABLE . $name)) {
            return $this->cache->get(self::CACHE_CALLABLE . $name);
        }

        $callable = $callable ?? $this->getClass($closure);
        $resolved = $this->resolveCallable($name, $callable);

        //cache closure
        $this->store(self::CACHE_CALLABLE . $name, $resolved);

        return $resolved;
    }

    /**
     * @param string $class
     * @param bool $factory
     *
     * @return mixed|null|object
     */
    public function resolveClassString(string $class, $factory = false)
    {
        $object = $this->getClass($class);

        if($factory === true) {
            return $this->resolveClass($object);
        }

        return $this->resolveClassFromCache($class, $object);
    }

    /**
     * @param \ReflectionClass $class
     *
     * @return object
     */
    private function resolveClass(\ReflectionClass $class)
    {
        $definitions = $this->container->isDefined($class->getName())
            ? $this->container->getDefiniition($class->getName()) : null;

        $constructor_parameters = $class->getConstructor() !== null ? $class->getConstructor()->getParameters() : null;
        $parameters = $this->resolveParameters($constructor_parameters ?? [], $definitions);

        return $class->newInstanceArgs($parameters);
    }

    /**
     * @param string $class
     * @param null|\ReflectionClass $object
     *
     * @return mixed|null|object
     */
    private function resolveClassFromCache(string $class, ?\ReflectionClass $object = null)
    {
        if($this->cache->has($class)) {
            return $this->cache->get($class);
        }

        $object = $object ?? $this->getClass($class);
        $resolved = $this->resolveClass($object);

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
    private function resolveParameters(?array $params = [], ?array $definitions = null)
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
    private function getClass(string $class)
    {
        return new \ReflectionClass($class);
    }

    /**
     * @param callable $callable
     *
     * @return \ReflectionFunction
     */
    private function getCallable(callable $callable)
    {
        return new \ReflectionFunction($callable);
    }
}