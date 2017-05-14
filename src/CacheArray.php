<?php
namespace Canister;

use Canister\Exceptions\CacheInvalidArgumentException;
use Psr\SimpleCache\CacheInterface;

/**
 * Class CacheArray
 *
 * @package Canister
 */
class CacheArray implements CacheInterface
{
    /**
     * @var array
     */
    private $cache = [];

    /**
     * @param string $key
     * @param null $default
     *
     * @return mixed|null
     */
    public function get($key, $default = null)
    {
        $this->throwInvalidStringArgumentException($key);

        return $this->has($key) ? $this->cache[$key] : $default;
    }

    /**
     * @param string $key
     * @param mixed $val
     * @param null $ttl
     *
     * @return bool
     */
    public function set($key, $val, $ttl = null)
    {
        $this->throwInvalidStringArgumentException($key);

        $this->cache[$key] = $val;

        return true;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function delete($key)
    {
        $this->throwInvalidStringArgumentException($key);

        if($this->has($key)) {
            unset($this->cache[$key]);
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function clear()
    {
        $this->cache = [];

        return true;
    }

    /**
     * @param iterable $keys
     * @param null $default
     *
     * @return array
     */
    public function getMultiple($keys, $default = null)
    {
        $this->throwInvalidArrayArgumentException($keys);

        $results = [];
        foreach($keys as $key) {
            $results[$key] = $this->get($key, $default);
        }

        return $results;
    }

    /**
     * @param iterable $value_pair
     * @param null $ttl
     *
     * @return bool
     */
    public function setMultiple($value_pair, $ttl = null)
    {
        $this->throwInvalidArrayArgumentException($value_pair);

        foreach($value_pair as $key => $value) {
            $this->set($key, $value, $ttl);
        }

        return true;
    }

    /**
     * @param iterable $keys
     *
     * @return bool
     */
    public function deleteMultiple($keys)
    {
        $this->throwInvalidArrayArgumentException($keys);

        foreach($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function has($key)
    {
        $this->throwInvalidStringArgumentException($key);

        return isset($this->cache[$key]);
    }

    /**
     * @param $keys
     *
     * @throws CacheInvalidArgumentException
     */
    private function throwInvalidArrayArgumentException($keys)
    {
        if(!is_array($keys)) {
            throw new CacheInvalidArgumentException("The key must be a valid array");
        }
    }

    /**
     * @param $key
     *
     * @throws CacheInvalidArgumentException
     */
    private function throwInvalidStringArgumentException($key)
    {
        if(!is_string($key)) {
            throw new CacheInvalidArgumentException("The key must be a valid string");
        }
    }
}