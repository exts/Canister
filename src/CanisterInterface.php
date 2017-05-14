<?php
namespace Canister;

use Psr\Container\ContainerInterface;

/**
 * Interface CanisterInterface
 *
 * @package Canister
 */
interface CanisterInterface extends ContainerInterface
{
    /**
     * @param string $class
     * @param string $alias
     */
    public function alias(string $class, string $alias);

    /**
     * @param string $key
     *
     * @return bool
     */
    public function isAlias(string $key) : bool;

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function getAlias(string $key);

    /**
     * @param string $class
     */
    public function factory(string $class);

    /**
     * @param $key
     *
     * @return bool
     */
    public function isFactory($key) : bool;

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function getFactory(string $key);

    /**
     * @param string $class
     * @param callable|null $callable
     */
    public function share(string $class, ?callable $callable = null);

    /**
     * @param $key
     *
     * @return bool
     */
    public function isShared($key) : bool;

    /**
     * @param string $key
     *
     * @return mixed
     */

    public function getShared(string $key);

    /**
     * @param $class
     * @param array $parameters
     */
    public function define($class, array $parameters);

    /**
     * @param $class
     *
     * @return bool
     */
    public function isDefined($class) : bool;

    /**
     * @param $class
     *
     * @return mixed
     */
    public function getDefiniition($class);
}