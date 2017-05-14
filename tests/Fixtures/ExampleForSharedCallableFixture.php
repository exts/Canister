<?php
namespace Test\Fixtures;

class ExampleForSharedCallableFixture
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