<?php
namespace Test\Fixtures;

class ExampleDiFixture
{
    private $example_fixture;

    public function __construct(ExampleFixture $example_fixture, $novalue, array $test, int $blah, callable $booyah)
    {
        $this->example_fixture = $example_fixture;
    }

    public function output()
    {
        return $this->example_fixture->foo();
    }
}