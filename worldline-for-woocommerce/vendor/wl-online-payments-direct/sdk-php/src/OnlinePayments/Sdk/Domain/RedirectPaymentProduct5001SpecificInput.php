<?php

/*
 * This class was auto-generated.
 */
namespace Syde\Vendor\OnlinePayments\Sdk\Domain;

use Syde\Vendor\OnlinePayments\Sdk\DataObject;
use UnexpectedValueException;
/**
 * @package OnlinePayments\Sdk\Domain
 */
class RedirectPaymentProduct5001SpecificInput extends DataObject
{
    // Properties
    /**
     * @var string
     */
    private $subsequentType;
    // Methods
    /**
     * @return string
     */
    public function getSubsequentType()
    {
        return $this->subsequentType;
    }
    /**
     * @var string
     */
    public function setSubsequentType($value)
    {
        $this->subsequentType = $value;
    }
    /**
     * @return object
     */
    public function toObject()
    {
        $object = parent::toObject();
        if ($this->subsequentType !== null) {
            $object->subsequentType = $this->subsequentType;
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
        if (property_exists($object, 'subsequentType')) {
            $this->subsequentType = $object->subsequentType;
        }
        return $this;
    }
}
