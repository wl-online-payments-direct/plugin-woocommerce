<?php

/*
 * This class was auto-generated.
 */
namespace Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain;

use Syde\Vendor\Worldline\OnlinePayments\Sdk\DataObject;
use UnexpectedValueException;
/**
 * @package OnlinePayments\Sdk\Domain
 */
class PaymentProductFiltersHostedTokenization extends DataObject
{
    // Properties
    /**
     * @var PaymentProductFilterHostedTokenization
     */
    private $exclude;
    /**
     * @var PaymentProductFilterHostedTokenization
     */
    private $restrictTo;
    // Methods
    /**
     * @return PaymentProductFilterHostedTokenization
     */
    public function getExclude()
    {
        return $this->exclude;
    }
    /**
     * @var PaymentProductFilterHostedTokenization
     */
    public function setExclude($value)
    {
        $this->exclude = $value;
    }
    /**
     * @return PaymentProductFilterHostedTokenization
     */
    public function getRestrictTo()
    {
        return $this->restrictTo;
    }
    /**
     * @var PaymentProductFilterHostedTokenization
     */
    public function setRestrictTo($value)
    {
        $this->restrictTo = $value;
    }
    /**
     * @return object
     */
    public function toObject()
    {
        $object = parent::toObject();
        if ($this->exclude !== null) {
            $object->exclude = $this->exclude->toObject();
        }
        if ($this->restrictTo !== null) {
            $object->restrictTo = $this->restrictTo->toObject();
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
        if (property_exists($object, 'exclude')) {
            if (!is_object($object->exclude)) {
                throw new UnexpectedValueException('value \'' . print_r($object->exclude, \true) . '\' is not an object');
            }
            $value = new PaymentProductFilterHostedTokenization();
            $this->exclude = $value->fromObject($object->exclude);
        }
        if (property_exists($object, 'restrictTo')) {
            if (!is_object($object->restrictTo)) {
                throw new UnexpectedValueException('value \'' . print_r($object->restrictTo, \true) . '\' is not an object');
            }
            $value = new PaymentProductFilterHostedTokenization();
            $this->restrictTo = $value->fromObject($object->restrictTo);
        }
        return $this;
    }
}
