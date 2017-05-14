<?php
namespace Canister\Exceptions;

use Psr\SimpleCache\CacheException as SimpleCacheException;

/**
 * Class CacheException
 *
 * @package Canister\Exceptions
 */
class CacheException extends \Exception implements SimpleCacheException
{

}