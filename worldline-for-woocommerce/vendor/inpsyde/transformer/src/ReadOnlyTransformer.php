<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\Transformer;

use Syde\Vendor\Inpsyde\Transformer\Exception\TransformerException;
//phpcs:disable Inpsyde.CodeQuality.NoAccessors.NoGetter
//phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
//phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType
/**
 * Wraps another Transformer but itself provides only the minimal interface.
 * This can be useful to "finalize" a Transformer after configuration
 */
class ReadOnlyTransformer implements Transformer
{
    /**
     * @var Transformer
     */
    private $transformer;
    private function __construct(Transformer $transformer)
    {
        $this->transformer = $transformer;
    }
    public static function fromTransformer(Transformer $transformer): Transformer
    {
        return new self($transformer);
    }
    public function create(string $returnType, $payload)
    {
        return $this->transformer->create($returnType, $payload);
    }
}
