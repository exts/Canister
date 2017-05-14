<?php
namespace Canister;

/**
 * Class Container
 *
 * @package Canister
 */
class Container implements \ArrayAccess
{
    /**
     * @var array
     */
    private $container = [];

    /**
     * Container constructor.
     *
     * @param array $array
     */
    public function __construct(array $array = [])
    {
        $this->container = $array;
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }

    /**
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->container[$offset]);
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->container[$offset]);
    }

    /**
     * @param mixed $offset
     *
     * @return mixed|null
     */
    public function offsetGet($offset)
    {
        return $this->container[$offset] ?? null;
    }
}