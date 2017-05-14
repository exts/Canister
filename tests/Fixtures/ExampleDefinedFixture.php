<?php
namespace Test\Fixtures;

class ExampleDefinedFixture
{
    public function __construct(ExampleFixture $example_fixture, $foo = null)
    {
        $this->foo = $foo;
        $this->example_fixture = $example_fixture;
    }

    public function getExampleFixture()
    {
        return $this->example_fixture->foo();
    }

    public function getFoo()
    {
        return $this->foo;
    }
}