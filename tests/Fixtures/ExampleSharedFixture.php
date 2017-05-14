<?php
namespace Test\Fixtures;

class ExampleSharedFixture
{
    static $example = 0;

    public $message;

    public function __construct()
    {
        ++self::$example;
    }

    public function getExampleCount()
    {
        return self::$example;
    }

    public function message($message)
    {
        $this->message = $message;
    }

}