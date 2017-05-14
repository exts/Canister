<?php
namespace Test\Fixtures;

class ExampleFactoryFixture
{
    private $count = 0;
    static $instance = 0;

    public function __construct($count = 0)
    {
        $this->count = $count;
        ++self::$instance;
    }

    public function increase()
    {
        ++$this->count;
    }

    public function getCount()
    {
        return $this->count;
    }

    public function getInstanceCount()
    {
        return self::$instance;
    }
}