<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\Transformer;

use Syde\Vendor\Worldline\Inpsyde\Transformer\Exception\TransformerException;
//phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
interface Transformer
{
    /**
     * Attempt to build an entity of $returnType out of the $payload.
     *
     * A TransformerException MUST be thrown if no transformer is found.
     * It MAY be thrown for any number of other reasons. For example,
     * there might be a validation middleware that rejects the output
     * of a transformer for arbitrary reasons
     *
     * @param string $returnType
     * @param mixed $payload
     *
     * @return mixed An object of the type specified in $returnType
     * @throws TransformerException
     */
    public function create(string $returnType, $payload);
}
