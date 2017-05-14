# Canister
Canister is a psr-11 auto-wiring container for PHP 7. Will add examples later.

#### Notes

- All classes that aren't in the container are automatically resolved if they exist
- All resolved classes are shared by default
- Reflector uses a simple array cache by default, but can be overridden by using a PSR-16 simple cache interface when passed to the constructor of a new reflector instance
- Container is an implementation of `ArrayAccess` so you can do pretty much what you would normally do using `ArrayAccess`
- You can define/override default values for class instances when the code tries to resolve it from the container.
- The get method checks in this order when calling the `get` method from the container: container, factory callables, shared callables, then attempts to resolve any classes if they exists and attempt to grab them from the reflector's cache first then resolve it automatically. If all else fails it'll return `NULL`

# Examples

Below are simple examples of it's usage.

##### Basic instantiation

```php
use Canister;

$container = new Canister;
```

##### Storing basic data into the container

```php
$container['my-key-value'] = 'accessed anywhere';
 
echo $container->get('my-key-value'); // or $container['my-key-value'];
```

##### Auto-Resolving a class

```php
class Example
{
    public function foo() {
        return 'bar';
    }
}

$example = $container->get(Example::class);
echo $example->foo(); // -> 'bar'
```

##### Creating an alias to a class

```php
$container->alias(ExampleInterface::class, Example::class);

$example = $container->get(ExampleInterface::class);
echo $example->foo(); // -> 'bar'
```

This also works for automatically passing dependencies to classes

```php
class Test implements TestInterface {
    public function example() {
        return 'example';
    }
}

class TestExample {
    public function __construct(TestInterface $test) {
        $this->test = $test;
    }
    public function testing() {
        return $this->test->example();
    }
}

$container->alias(TestInterface::class, Test::class);
$test_example = $container->get(TestExample::class);
echo $test_example->testing(); // -> 'example'
```

##### Define class as a factory

Everytime you call `FactoryClass` it'll be a new instance instead of being shared by default.

```php
$container->factory(FactoryClass::class);
```

##### Define factory callable

Dependencies to callables are also auto resolved by default, so you can access the container directly because it's automatically passed to the container by default.

```php
$container->factory(FactoryClass::class, function() {
    //...
});
```

##### Define shared callable

Want to do the same thing instead store the value instead of creating new instances every call? Well replace `factory` with `shared` and you get the same functionality.

_(Note: since auto resolution is shared by default, you cannot pass a class name by itself like you can with the `factory` method)_


##### Defining default parameters for callables & classes

For callables it's as simple as

```php
$container->share('example', function($foo, $bar) {
    return $foo + $bar;
});

$container->define('example', [
    'foo' => val(5),
    'bar' => val(21)
]);

echo $container->get('example');
```

The first parameter is the class name or callable name that we're trying to define parameters for. The second parameter is an array used to match the parameters we want to define.

You don't have to order them in any order, just need to know the variable name and make that as the key.

**Global Definition functions**

There's two global definition functions inside the `Canister` namespace called `val` (or `Canister\val()`) and `bag` or (`Canister\bag()`). 

The `val` function is used to tell the resolution method that we're using a raw value.

The `bag` function is used to tell the resolution method that we should check the container for this value.


### Other Notes

- You can resolve php classes as well as define their value too.

```php
$container = new Canister;
$container->define(\PDO::class, [
    'dsn' => val('sqlite::memory:'),
]);

$pdo = $container->get(\PDO::class);

echo is_a($pdo, \PDO::class) ? 'true' : 'false'; // -> true
```