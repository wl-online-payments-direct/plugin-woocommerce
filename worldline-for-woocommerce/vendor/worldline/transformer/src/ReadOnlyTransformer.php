<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\Transformer;

use Worldline\Vendor\Worldline\Transformer\Exception\TransformerException;
//phpcs:disable Worldline.CodeQuality.NoAccessors.NoGetter
//phpcs:disable Worldline.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
//phpcs:disable Worldline.CodeQuality.ReturnTypeDeclaration.NoReturnType
/**
 * Wraps another Transformer but itself provides only the minimal interface.
 * This can be useful to "finalize" a Transformer after configuration
 */
class ReadOnlyTransformer implements Transformer
{
    private Transformer $transformer;
    private function __construct(Transformer $transformer)
    {
        $this->transformer = $transformer;
    }
    public static function fromTransformer(Transformer $transformer) : Transformer
    {
        return new self($transformer);
    }
    public function create(string $returnType, $payload)
    {
        return $this->transformer->create($returnType, $payload);
    }
}
