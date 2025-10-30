<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\Transformer;

use Syde\Vendor\Worldline\Inpsyde\Transformer\Exception\TransformerException;
use Syde\Vendor\Worldline\Inpsyde\Transformer\Exception\TransformerReflectionException;
use Syde\Vendor\Worldline\Inpsyde\Transformer\Exception\InvalidTransformerSignatureException;
use Syde\Vendor\Worldline\Inpsyde\Transformer\Exception\MissingTransformerException;
use ReflectionNamedType;
//phpcs:disable Inpsyde.CodeQuality.NoAccessors.NoGetter
//phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
//phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType
class ConfigurableTransformer implements Transformer, MutableTransformer
{
    /**
     * Flat list of transformer functions
     *
     * @var callable[]
     */
    protected array $factories = [];
    /**
     * Flat list of middleware functions
     *
     * @var callable[]
     */
    protected array $middlewares = [];
    /**
     * Associative array of compiled transformer functions that use matching middlewares
     *
     * @var callable[]
     */
    protected array $compiled = [];
    public function addTransformer(callable $transformer) : MutableTransformer
    {
        $this->assertValidFunction($transformer);
        $this->factories[] = $transformer;
        $this->compiled = [];
        return $this;
    }
    public function addMiddleware(callable $middleware, int $priority = 50) : MutableTransformer
    {
        $this->assertValidFunction($middleware, \false);
        \array_splice($this->middlewares, $priority, 0, $middleware);
        $this->compiled = [];
        return $this;
    }
    /**
     * Inspects the function signature and throws TransformerExceptions
     * if the signature is unusable for us
     *
     * @param callable $function
     * @param bool $isTransformer
     *
     * @throws TransformerException
     */
    private function assertValidFunction(callable $function, bool $isTransformer = \true)
    {
        try {
            $reflectionFunction = new \ReflectionFunction($function);
        } catch (\ReflectionException $exception) {
            throw new TransformerReflectionException('Could not use reflection on the given transformer function', 0, $exception);
        }
        if (!$reflectionFunction->hasReturnType()) {
            throw new InvalidTransformerSignatureException('Transformer or Extension did not specify a return type.');
        }
        $requiredParamCount = $isTransformer ? 1 : 2;
        if ($reflectionFunction->getNumberOfParameters() < $requiredParamCount) {
            throw new InvalidTransformerSignatureException(\sprintf("Transformer or extension used less or more than %s arguments.", $requiredParamCount));
        }
        $parameters = $reflectionFunction->getParameters();
        $firstParam = $parameters[0];
        $paramType = $firstParam->getType();
        if ($paramType === null) {
            throw new InvalidTransformerSignatureException(\sprintf("Transformer or Extension did not specify a parameter type for its payload."));
        }
    }
    private function composeCacheKey(string $returnType, $payload) : string
    {
        $parameterType = $this->getType($payload);
        return $returnType . '|' . $parameterType;
    }
    /**
     * @param string $returnType
     * @param $payload
     *
     * @return callable
     * @throws TransformerReflectionException
     * @throws MissingTransformerException
     */
    private function searchTransformer(string $returnType, $payload) : callable
    {
        foreach ($this->factories as $transformer) {
            if ($this->isMatchingCallable($returnType, $payload, $transformer)) {
                return $transformer;
            }
        }
        throw new MissingTransformerException(\sprintf('Could not find a transformer for "%s" with payload type "%s"', $returnType, $this->getType($payload)));
    }
    /**
     * @param string $returnType
     * @param $payload
     * @param callable $callable
     *
     * @return bool
     * @throws TransformerReflectionException
     */
    private function isMatchingCallable(string $returnType, $payload, callable $callable) : bool
    {
        try {
            $reflectionFunction = new \ReflectionFunction($callable);
        } catch (\ReflectionException $exception) {
            throw new TransformerReflectionException('Could not use reflection on the given transformer function', 0, $exception);
        }
        $transformerReturnType = $reflectionFunction->getReturnType();
        $parameters = $reflectionFunction->getParameters();
        $firstParam = $parameters[0];
        $paramType = $firstParam->getType();
        if ($this->isMatchingType($this->getType($payload), $paramType) && $this->isMatchingType($returnType, $transformerReturnType)) {
            return \true;
        }
        return \false;
    }
    /**
     * @param string $type
     * @param \ReflectionType $reflectionType
     *
     * @return bool
     */
    private function isMatchingType(string $type, \ReflectionType $reflectionType) : bool
    {
        /**
         * Wrap the given type in an array and check for php8 union types
         * This is currently untested, but it should result in any specified type yielding a match
         *
         * @var ReflectionNamedType[] $types
         */
        $types = $type instanceof \ReflectionUnionType ? $reflectionType->getTypes() : [$reflectionType];
        foreach ($types as $possibleType) {
            $reflectionTypeName = $possibleType->getName();
            if ($reflectionTypeName === 'int') {
                $reflectionTypeName .= 'eger';
                // yeah..
            }
            if ($type === $reflectionTypeName) {
                return \true;
            }
            if (\class_exists($type) || \interface_exists($type)) {
                return \is_a($type, $reflectionTypeName, \true);
            }
        }
        return \false;
    }
    /**
     * @param string $returnType
     * @param $payload
     *
     * @return array
     * @throws TransformerReflectionException
     */
    private function collectApplicableMiddlewares(string $returnType, $payload) : array
    {
        return \array_filter($this->middlewares, function (callable $middleware) use($returnType, $payload) {
            return $this->isMatchingCallable($returnType, $payload, $middleware);
        });
    }
    /**
     * @param string $returnType
     * @param $payload
     *
     * @return mixed
     * @throws TransformerException
     */
    public function create(string $returnType, $payload)
    {
        if ($returnType === 'int') {
            $returnType .= 'eger';
        }
        $key = $this->composeCacheKey($returnType, $payload);
        if (!isset($this->compiled[$key])) {
            $this->compiled[$key] = $this->compileTransformer($returnType, $payload);
        }
        $transformer = $this->compiled[$key];
        return $transformer($payload, $this);
    }
    /**
     * Creates a transformer from the original transformer function and its accompanying middleware stack
     *
     * @param string $returnType
     * @param $payload
     *
     * @return mixed
     * @throws TransformerReflectionException
     * @throws MissingTransformerException
     */
    private function compileTransformer(string $returnType, $payload) : callable
    {
        $transformer = $this->searchTransformer($returnType, $payload);
        $middlewares = $this->collectApplicableMiddlewares($returnType, $payload);
        $extensionWrapper = function ($previous, $extension) {
            return function ($payload, Transformer $transformer = null) use($extension, $previous) {
                return $extension($payload, $previous, $transformer ?? $this);
            };
        };
        return \array_reduce($middlewares, $extensionWrapper, $transformer);
    }
    /**
     * If $payload is a class, return its FQCN.
     * If not, return its type
     *
     * @param mixed $payload
     *
     * @return string
     */
    private function getType($payload) : string
    {
        $type = \is_object($payload) ? \get_class($payload) : \gettype($payload);
        if ($type === 'int') {
            $type .= 'eger';
        }
        return $type;
    }
    /**
     * Returns a Transformer instance with the current configuration that is no longer editable
     *
     * @return Transformer
     */
    public function export() : Transformer
    {
        return ReadOnlyTransformer::fromTransformer(clone $this);
    }
}
