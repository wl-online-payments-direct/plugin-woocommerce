<?php
/*
 * This file was automatically generated.
 */
namespace OnlinePayments\Sdk\Domain;

use UnexpectedValueException;

/**
 * @package OnlinePayments\Sdk\Domain
 */
class PayoutErrorResponse extends DataObject
{
    /**
     * @var string
     */
    public $errorId = null;

    /**
     * @var APIError[]
     */
    public $errors = null;

    /**
     * @var PayoutResult
     */
    public $payoutResult = null;

    /**
     * @return string
     */
    public function getErrorId()
    {
        return $this->errorId;
    }

    /**
     * @param string
     */
    public function setErrorId($value)
    {
        $this->errorId = $value;
    }

    /**
     * @return APIError[]
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @param APIError[]
     */
    public function setErrors($value)
    {
        $this->errors = $value;
    }

    /**
     * @return PayoutResult
     */
    public function getPayoutResult()
    {
        return $this->payoutResult;
    }

    /**
     * @param PayoutResult
     */
    public function setPayoutResult($value)
    {
        $this->payoutResult = $value;
    }

    /**
     * @return object
     */
    public function toObject()
    {
        $object = parent::toObject();
        if (!is_null($this->errorId)) {
            $object->errorId = $this->errorId;
        }
        if (!is_null($this->errors)) {
            $object->errors = [];
            foreach ($this->errors as $element) {
                if (!is_null($element)) {
                    $object->errors[] = $element->toObject();
                }
            }
        }
        if (!is_null($this->payoutResult)) {
            $object->payoutResult = $this->payoutResult->toObject();
        }
        return $object;
    }

    /**
     * @param object $object
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject($object)
    {
        parent::fromObject($object);
        if (property_exists($object, 'errorId')) {
            $this->errorId = $object->errorId;
        }
        if (property_exists($object, 'errors')) {
            if (!is_array($object->errors) && !is_object($object->errors)) {
                throw new UnexpectedValueException('value \'' . print_r($object->errors, true) . '\' is not an array or object');
            }
            $this->errors = [];
            foreach ($object->errors as $element) {
                $value = new APIError();
                $this->errors[] = $value->fromObject($element);
            }
        }
        if (property_exists($object, 'payoutResult')) {
            if (!is_object($object->payoutResult)) {
                throw new UnexpectedValueException('value \'' . print_r($object->payoutResult, true) . '\' is not an object');
            }
            $value = new PayoutResult();
            $this->payoutResult = $value->fromObject($object->payoutResult);
        }
        return $this;
    }
}
