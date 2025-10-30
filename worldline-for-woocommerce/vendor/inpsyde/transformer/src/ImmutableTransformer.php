<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\Transformer;

use Syde\Vendor\Worldline\Inpsyde\Transformer\Exception\TransformerException;
//phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
/**
 * This class does not do much on its own, but wraps a ConfigurableTransformer
 * and provides a way to instantiate it in a way that is
 */
class ImmutableTransformer implements Transformer
{
    private Transformer $transformer;
    /**
     * ImmutableTransformer constructor.
     *
     * @param array $factories
     * @param array $middlewares
     *
     * @throws TransformerException
     */
    private function __construct(array $factories, array $middlewares)
    {
        $configurableTransformer = new ConfigurableTransformer();
        foreach ($factories as $transformer) {
            $configurableTransformer->addTransformer($transformer);
        }
        foreach ($middlewares as $middleware) {
            $configurableTransformer->addMiddleware($middleware);
        }
        $this->transformer = $configurableTransformer->export();
        // Technically not needed, but whatever
    }
    /**
     * @param array $factories
     * @param array $middlewares
     *
     * @return Transformer
     * @throws TransformerException
     */
    public static function fromCallables(array $factories, array $middlewares = []) : Transformer
    {
        return new self($factories, $middlewares);
    }
    public function create(string $returnType, $payload)
    {
        return $this->transformer->create($returnType, $payload);
    }
}
