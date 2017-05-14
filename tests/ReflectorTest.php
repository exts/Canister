<?php
namespace Test;

use Canister\Canister;
use Canister\Reflector;
use PHPUnit\Framework\TestCase;
use Test\Fixtures\ExampleDiFixture;
use Test\Fixtures\ExampleFixture;

class ReflectorTest extends TestCase
{
    public function testResolveCallableFactoryFromString()
    {
        $reflector = new Reflector();
        $canister = new Canister([], $reflector);

        $canister->factory('example', function() {
           return 'example';
        });

        $canister->factory('example2', function() {
            return new ExampleFixture;
        });

        $test1 = $reflector->resolveCallableString('example2', $canister->getFactory('example2'), true);
        $test2 = $reflector->resolveCallableString('example2', $canister->getFactory('example2'), true);

        $this->assertTrue('example' ==
            $reflector->resolveCallableString('example', $canister->getFactory('example'), true)
        );

        $this->assertTrue($test1 !== $test2);
    }

    public function testResolveCachedCallableFromString()
    {
        $reflector = new Reflector();
        $canister = new Canister([], $reflector);

        $canister->share('example', function() {
            return new ExampleFixture();
        });

        $resolved1 = $reflector->resolveCallableString('example', $canister->getShared('example'));
        $resolved2 = $reflector->resolveCallableString('example', $canister->getShared('example'));

        $this->assertTrue($resolved1 === $resolved2);
    }

    public function testResolveCallableFromStringUsingDiParameters()
    {
        $reflector = new Reflector();
        $canister = new Canister([], $reflector);

        $canister->factory('example', function(ExampleFixture $example_fixture) {
            return $example_fixture->foo();
        });

        $this->assertTrue('bar' ==
            $reflector->resolveCallableString('example', $canister->getFactory('example'), true)
        );
    }

    public function testResolveOfClassFixtureFromString()
    {
        $reflector = new Reflector();
        $canister = new Canister([], $reflector);

        $this->assertInstanceOf(
            ExampleFixture::class,
            $reflector->resolveClassString(ExampleFixture::class)
        );
    }

    public function testResolveOfExampleFixtureThatTakesDependencies()
    {
        $reflector = new Reflector();
        $canister = new Canister([], $reflector);

        $this->assertInstanceOf(
            ExampleDiFixture::class,
            $reflector->resolveClassString(ExampleDiFixture::class)
        );
    }
}