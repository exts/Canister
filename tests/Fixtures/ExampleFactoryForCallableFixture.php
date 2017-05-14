<?php
namespace Test\Fixtures;

class ExampleFactoryForCallableFixture
{
    public static $count;

    public function __construct()
    {
        ++self::$count;
    }

    public function getCount()
    {
        return self::$count;
    }
}