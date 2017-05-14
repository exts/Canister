<?php
namespace Canister;

use Canister\Exceptions\OffsetNotFound;
use Canister\Exceptions\OffsetNotValid;

/**
 * Class Canister
 *
 * @package Canister
 */
class Canister extends Container implements CanisterInterface
{
    /**
     * Used as key prefixes when storing aliases, shared callables, factories and definitions
     */
    const REFLECTOR_ALIAS = '_reflector.alias';
    const REFLECTOR_SHARED = '_reflector.shared';
    const REFLECTOR_FACTORY = '_reflector.factory';
    const REFLECTOR_DEFINITIONS = '_reflector.definitions';

    /**
     * @var Reflector
     */
    private $reflector;

    /**
     * Canister constructor.
     *
     * @param array $container
     * @param Reflector|null $reflector
     */
    public function __construct(array $container = [], Reflector $reflector = null)
    {
        parent::__construct($container);

        $this->setupDefaults();

        $this->reflector = $reflector ?? new Reflector();
        $this->reflector->setContainer($this);

        //store reference of the container inside itself, can be overridden
        $this->instance($this);
    }

    /**
     * @param string $offset
     *
     * @return bool
     */
    public function has($offset)
    {
        return $this->offsetExists($offset);
    }

    /**
     * @param string $key
     *
     * @return mixed|null|object
     * @throws OffsetNotValid
     */
    public function get($key)
    {
        if(!is_string($key)) {
            throw new OffsetNotValid(sprintf("Offset must be a string, %s passed", gettype($key)));
        }

        //automatically resolve factory callables if they exist
        if(!$this->has($key) && $this->isFactory($key) && is_callable(($factory = $this->getFactory($key)))) {
            return $this->reflector->resolveCallableString($key, $factory, true);
        }

        //automatically resolve shared callables if they exist
        if(!$this->has($key) && $this->isShared($key) && is_callable(($shared = $this->getShared($key)))) {
            return $this->reflector->resolveCallableString($key, $shared);
        }

        //get alias
        $alias = $this->isAlias($key) ? $this->getAlias($key) : $key;

        //automatically resolve classes if they exist even if they don't exist in our reflector cache
        if(!$this->has($key) && class_exists($alias)) {
            return $this->reflector->resolveClassString($alias, $this->isFactory($alias));
        }

        //return basic container value
        return $this->offsetGet($key);
    }

    /**
     * @param $classOrInstance
     *
     * @return bool
     * @throws OffsetNotValid
     */
    public function instance($classOrInstance)
    {
        if(!is_object($classOrInstance) && !is_string($classOrInstance)) {
            throw new OffsetNotValid("Class or instance you're trying to pass is not a valid string or object");
        }

        if(is_object($classOrInstance)) {
            $this[get_class($classOrInstance)] = $classOrInstance;
            return true;
        }

        //get alias if it exists
        $alias = $this->isAlias($classOrInstance) ? $this->getAlias($classOrInstance) : $classOrInstance;
        if(is_string($alias) && class_exists($alias)) {
            $this[$classOrInstance] = $this->reflector->resolveClassString($alias, $this->isFactory($alias));
            return true;
        }

        return false;
    }

    /**
     * @param string $class
     * @param string $alias
     */
    public function alias(string $class, string $alias)
    {
        if(!$this->isAlias($class)) {
            $this[self::REFLECTOR_ALIAS][$class] = $alias;
        }
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function isAlias(string $key) : bool
    {
        return isset($this[self::REFLECTOR_ALIAS][$key]);
    }

    /**
     * @param string $key
     *
     * @return mixed
     * @throws OffsetNotFound
     */
    public function getAlias(string $key)
    {
        if(!$this->isAlias($key)) {
            throw new OffsetNotFound("Trying to get an alias offset that doesn't exist");
        }

        return $this[self::REFLECTOR_ALIAS][$key];
    }

    /**
     * @param string $class
     * @param callable|null $callable
     */
    public function factory(string $class, ?callable $callable = null)
    {
        if(!$this->isFactory($class)) {
            $this[self::REFLECTOR_FACTORY][$class] = $callable ?? $class;
        }
    }

    /**
     * @param $key
     *
     * @return bool
     */
    public function isFactory($key) : bool
    {
        return isset($this[self::REFLECTOR_FACTORY][$key]);
    }

    /**
     * @param string $key
     *
     * @return mixed
     * @throws OffsetNotFound
     */
    public function getFactory(string $key)
    {
        if(!$this->isFactory($key)) {
            throw new OffsetNotFound("Trying to get a factory offset that doesn't exist");
        }

        return $this[self::REFLECTOR_FACTORY][$key];
    }

    /**
     * @param string $class
     * @param callable|null $callable
     */
    public function share(string $class, ?callable $callable = null)
    {
        if(!$this->isShared($class)) {
            $this[self::REFLECTOR_SHARED][$class] = $callable ?? $class;
        }
    }

    /**
     * @param $key
     *
     * @return bool
     */
    public function isShared($key) : bool
    {
        return isset($this[self::REFLECTOR_SHARED][$key]);
    }

    /**
     * @param string $key
     *
     * @return mixed
     * @throws OffsetNotFound
     */
    public function getShared(string $key)
    {
        if(!$this->isShared($key)) {
            throw new OffsetNotFound("Trying to get a shared offset that doesn't exist");
        }

        return $this[self::REFLECTOR_SHARED][$key];
    }

    /**
     * @param $class
     * @param array $parameters
     */
    public function define($class, array $parameters)
    {
        if(!$this->isDefined($class)) {
            $this[self::REFLECTOR_DEFINITIONS][$class] = $parameters;
        }
    }

    /**
     * @param $class
     *
     * @return bool
     */
    public function isDefined($class) : bool
    {
        return isset($this[self::REFLECTOR_DEFINITIONS][$class]);
    }

    /**
     * @param $class
     *
     * @return mixed
     * @throws OffsetNotFound
     */
    public function getDefiniition($class)
    {
        if(!$this->isDefined($class)) {
            throw new OffsetNotFound("Trying to get a definition offset that doesn't exist");
        }

        return $this[self::REFLECTOR_DEFINITIONS][$class];
    }

    /**
     * @return void
     */
    private function setupDefaults()
    {
        //setup factory array
        $this[self::REFLECTOR_ALIAS] = new \ArrayObject([]);
        $this[self::REFLECTOR_SHARED] = new \ArrayObject([]);
        $this[self::REFLECTOR_FACTORY] = new \ArrayObject([]);
        $this[self::REFLECTOR_DEFINITIONS] = new \ArrayObject([]);
    }
}