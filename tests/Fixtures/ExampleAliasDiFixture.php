<?php
namespace Test\Fixtures;

class ExampleAliasDiFixture
{
    private $example_fixture;
    public function __construct(ExampleFixtureInterface $example_fixture)
    {
        $this->example_fixture = $example_fixture;
    }

    public function output()
    {
        return $this->example_fixture->foo();
    }
}