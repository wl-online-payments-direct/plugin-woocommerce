# Inpsyde Transformer

A tool to faciliate the creation of complex object trees out of arbitrary data. It can be used to craft object
representations of large JSON payloads - or to serialize them back into an array But really, you can convert anything
into everything if you want

## Installation

Grab the composer package via
```composer require inpsyde/transformer```

## Usage

*Inpsyde Transformer* works by supplying it with transformer functions which are inspected using the Reflection API.
This is used to determine which type of payload is able to build which type of object. Therefore, transformer functions MUST
specify parameter types and return types - which is something you would probably be doing anyway.

Consider a REST endpoint that responds with the following :

`GET /api/products/42`

```json
{
    "name": "T-Shirt",
    "description": "It's really just an example",
    "imageIds": [
        "9c07s8d089",
        "c0asd9m0as",
        "3c27893x82"
    ],
    "variants": [
        {
            "name": "Red",
            "sku": "shirt-red",
            "price": 1500
        },
        {
            "name": "Blue",
            "sku": "shirt-blue",
            "price": 1500
        }
    ]
}
```
Let's say you need to represent this payload as a `ProductInterface`. Here's how *Inpsyde Transformer* is going to help:

### Factories

The most simple and basic representation would be a `Product` that just swallows the JSON whole:

```php
use Inpsyde\Transformer\ConfigurableTransformer;

$transformer = new ConfigurableTransformer();

$transformer->addTransformer(function(array $json): ProductInterface {
    return new Product($json);
});

$json = $client->get('/api/products/42');


$product = $transformer->create(ProductInterface::class, $json);
```

But that's probably not what you're going for, because why would you need a special tool for this?
The `Product` surely is a little more versatile and also contains representations for `Images` and `Variants`.
Good thing it's pretty easy to delegate to other factories by leveraging the optional second parameter which is an instance of the current `Transformer`
```php
use Inpsyde\Transformer\ConfigurableTransformer;

$transformer = new ConfigurableTransformer();

/**
 * Create a Transformer for ImageInterface types which we'll use later inside
 * the actual Product transformer 
 */
$transformer->addTransformer(function(string $key): ImageInterface {
    return new Image($key);
});

/**
 * Create another transformer for the VariantInterface type 
 */
$transformer->addTransformer(function(array $json): VariantInterface {
    return new Variant(
        $json['name'],
        $json['sku'],
        (int)$json['price']
    );
});
/**
 * Here comes the actual transformer function.
 * Note the second $transformer param here which we use to delegate parts of our payload
 * to other factories
 */ 
$transformer->addTransformer(function(array $json, Transformer $transformer): ProductInterface {
    $images = array_map(
        function(string $key) use ($transformer){
            return $transformer->create(ImageInterface::class, $key);
        },
        $json['imageIds']
    );
    
    $variants = array_map(
        function(array $payload) use ($transformer){
            return $transformer->create(VariantInterface::class, $payload);
        },
        $json['variants']
    );
    
    return (new Product())
        ->withName($json['name'])
        ->withDescription($json['description'])
        ->withImages(...$images)
        ->withVariants(...$variants)
    ;
});

$json = $client->get('/api/products/42');

$product = $transformer->create(ProductInterface::class, $json);
```

### Scalar data

So you've built your `Product`, pushed it around a bit and now would like to POST the resulting product back to the API.
You can happily use the `$transformer` to act as a serializer as well:

```php
use Inpsyde\Transformer\ConfigurableTransformer;

$transformer = new ConfigurableTransformer();

$transformer->addTransformer(function(ProductInterface $product): array {
    return [
        'name'=> $product->name(),
        'description'=> $product->description(),
        'imageIds'=> $product->imageIds(),
        'variants'=> $product->variants(),
    ];
});


$myProduct = new Product();
$payload = $transformer->create('array', $myProduct);

$client->post('/api/products/42', $payload);

```

Note that we have to refer to the scalar return types as strings here.
PHP does not offer any other means to do this. 
Also, it is currently not possible to provide IDE support for these return types. That's just how it is, sadly.

### Middlewares

Say you need to create this `ProductInterface` from data sources that are less reliable than a REST api. 
You might have a front-end that allows the creation of products by users. 
So you need to validate the payload before it reaches the transformer. That's where Middleware comes in.

```php
use Inpsyde\Transformer\ConfigurableTransformer;use Inpsyde\Transformer\Exception\TransformerException;

$transformer = new ConfigurableTransformer();

// All the factories from above are already in place, okay?

$transformer->addMiddleware(function(array $payload, callable $next): ProductInterface {
    // We simply check if all keys are present
    $requiredKeys=['name','description','imageIds','variants'];
    if(!empty(array_diff_key($requiredKeys, $payload))){
        // Exceptions should implement `TransformerException` in order to satisfy the interface
        throw new MyCustomValidationException('Payload missing required keys');
    }
    // Still here? Then move on to the next entry
    return $next($payload);
});


$payload=[
    'someBogusValue'=>3
];
$product = $transformer->create(ProductInterface::class,$payload);
```

Of course you can also use a middleware to modify the payload before passing it on.
Let's say you still have to deal with legacy data that allowed the `imageIds` to be passed as a comma-separated string.
You can keep your actual transformer function clean of this back-compat code and generate the needed data via middleware:

```php
$transformer->addMiddleware(function(array $payload, callable $next): ProductInterface {
    if(is_string($payload['imageIds'])){
      $payload['imageIds']=explode(',',$payload['imageIds']);
    }
    return $next($payload);
});
```

Load order might become an issue with multiple middlewares, so you can control when to run a specific middleware using the `$priority` parameter:

```php
// Lower means earlier
$transformer->addMiddleware($middleware1, 999); // runs last
$transformer->addMiddleware($middleware2, 0); //runs first
$transformer->addMiddleware($middleware3, 60);
```

### Limiting access
You have probably noticed that the suggested use pattern is to mutate the `$transformer` instance by calling `addTransformer` and `addMiddleware()`
This is done for two reasons:

* It allows a nice, fluent interface: `$transformer->addTransformer($f1)->addMiddleware($m1)->addMiddleware($m2)`
* ...which lends itself well to extensions of a `ServiceProvider`: 
```php
$extensions=[
    MutableTransformer::class => fn(ContainerInterface $c, MutableTransformer $f) => $f
        ->addTransformer($c->get(FooTransformer::class)
        ->addTransformer($c->get(BarTransformer::class)
        ->addTransformer($c->get(BazTransformer::class)
];
```
  
But of course it's entirely valid to insist that client code is unable to modify the factories, which is why `ConfigurableTransformer::export()` can be used to obtain a read-only version of the current configuration.

This method really only wraps a clone into a `ReadOnlyTransformer`, which you can easily do yourself without having to use the `ConfigurableTransformer` implementation.
So your ServiceProvider might look like this:

```php
use Inpsyde\Transformer\Transformer;
use Inpsyde\Transformer\MutableTransformer;
use Inpsyde\Transformer\ReadOnlyTransformer;
use Psr\Container\ContainerInterface;

$services=[
    Transformer::class => function(ContainerInterface $c) {
       // Service extensions in other modules can add factories in a concise manner
       $configuredTransformer = $c->get(MutableTransformer::class);
       return ReadOnlyTransformer::fromTransformer($configuredTransformer);
    }
];
```


If you want to go immutable from square one, then `ImmutableTransformer` is your friend:

```php
use Inpsyde\Transformer\ImmutableTransformer;

$factories=[/* loads of callables */];
$middlewares=[/* loads of callables */];

$transformer = ImmutableTransformer::fromCallables($factories, $middlewares);

```

## License

Copyright (c) 2021 Inpsyde GmbH

This software is released under the [MIT](LICENSE.md) license.