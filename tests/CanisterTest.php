<?php
namespace Test;

use Canister\Canister;
use Canister\CanisterInterface;
use function Canister\val;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Test\Fixtures\ExampleAliasDiFixture;
use Test\Fixtures\ExampleDefinedFixture;
use Test\Fixtures\ExampleDiFixture;
use Test\Fixtures\ExampleFactoryFixture;
use Test\Fixtures\ExampleFactoryForCallableFixture;
use Test\Fixtures\ExampleFixture;
use Test\Fixtures\ExampleForSharedCallableFixture;
use Test\Fixtures\ExampleSharedFixture;
use Test\Fixtures\ExampleFixtureInterface;

class CanisterTest extends TestCase
{
    public function testContainerIsPSRContainer()
    {
        $container = new Canister;

        $this->assertInstanceOf(ContainerInterface::class, $container);
        $this->assertInstanceOf(CanisterInterface::class, $container);
    }

    public function testGettingBasicSetValueFromContainer()
    {
        $container = new Canister;
        $container['data'] = 'example';

        $this->assertEquals('example', $container->get('data'));
    }

    public function testLazyLoadingOfContainerClass()
    {
        $container = new Canister;

        $this->assertTrue(new ExampleFixture() == $container->get(ExampleFixture::class));
        $this->assertInstanceOf(ExampleDiFixture::class, $container->get(ExampleDiFixture::class));
    }

    public function testLazyLoadingOfRegisteredFactories()
    {
        $container = new Canister;

        $container->factory(ExampleFactoryFixture::class);

        $this->assertTrue($container->get(ExampleFactoryFixture::class)
            !== $container->get(ExampleFactoryFixture::class));

        $blah = $container->get(ExampleFactoryFixture::class);
        $this->assertEquals(3, $blah->getInstanceCount());
    }

    public function testLazyLoadSharedByDefault()
    {
        $container = new Canister;

        $test1 = $container->get(ExampleSharedFixture::class);
        $test2 = $container->get(ExampleSharedFixture::class);
        $test2->message("Hello #4");
        $test3 = $container->get(ExampleSharedFixture::class);
        $test4 = $container->get(ExampleSharedFixture::class);

        $this->assertEquals(1, $test4->getExampleCount());
        $this->assertEquals("Hello #4", $test4->message);
    }

    public function testLazyLoadingClassAliasing()
    {
        $container = new Canister;

        $container->alias('LoL', ExampleFixture::class);

        $this->assertInstanceOf(ExampleFixture::class, $container->get('LoL'));

        $container->alias(ExampleFixtureInterface::class, ExampleFixture::class);

        $example = $container->get(ExampleFixtureInterface::class);
        $example2 = $container->get(ExampleAliasDiFixture::class);
        $this->assertEquals('bar', $example->foo());
        $this->assertEquals('bar', $example2->output());
    }

    public function testLazyLoadingFactoryClosure()
    {
        $container = new Canister;

        $container->factory('ExampleClosure', function(ExampleFixture $example_fixture) {
           return $example_fixture->foo();
        });

        $this->assertEquals('bar', $container->get('ExampleClosure'));
    }

    public function testCallableFactories()
    {
        $container = new Canister;

        $container->factory('example1', function() {
            $blah = new ExampleFactoryForCallableFixture();

            return $blah;
        });

        $test1 = $container->get('example1');
        $test2 = $container->get('example1');
        $test3 = $container->get('example1');
        $test4 = $container->get('example1');

        $this->assertEquals(4, $test4->getCount());
    }

    public function testSharedCallables()
    {
        $container = new Canister;

        $container->share('example2', function() {
            $blah = new ExampleForSharedCallableFixture();

            return $blah;
        });

        $test1 = $container->get('example2');
        $test2 = $container->get('example2');
        $test3 = $container->get('example2');
        $test4 = $container->get('example2');

        $this->assertEquals(1, $test4->getCount());
    }

    public function testContainerDefinitions()
    {
        $container = new Canister;

        $container->define(ExampleDefinedFixture::class, [
            'foo' => val('bar')
        ]);

        $example = $container->get(ExampleDefinedFixture::class);

        $this->assertEquals('bar', $example->getFoo());
    }

    public function testContainerDefinitionForClosure()
    {
        $container = new Canister;

        $container->share('examplex', function($foo, $bar) {
            return $foo + $bar;
        });

        $container->define('examplex', [
            'foo' => val(5),
            'bar' => val(21)
        ]);

        $this->assertEquals(26, $container->get('examplex'));
    }

    public function testCallingContainerFromSharedCallable()
    {
        $container = new Canister;

        $container['testing'] = [1,2,3,4];

        $container->share('example', function(Canister $c) {
            foreach($c['testing'] as $number) {
                yield $number;
            }
        });

        $count = 0;
        foreach($container->get('example') as $value) {
            $count += $value;
        }

        $this->assertEquals(10, $count);
    }

    public function testResolvingPhpClass()
    {
        $container = new Canister;

        $container->define(\PDO::class, [
            'dsn' => val('sqlite::memory:'),
        ]);

        $pdo = $container->get(\PDO::class);

        $this->assertInstanceOf(\PDO::class, $pdo);
    }
}