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
class RedirectionData extends DataObject
{
    // Properties
    /**
     * @var string
     */
    private $returnUrl;
    // Methods
    /**
     * @return string
     */
    public function getReturnUrl()
    {
        return $this->returnUrl;
    }
    /**
     * @var string
     */
    public function setReturnUrl($value)
    {
        $this->returnUrl = $value;
    }
    /**
     * @return object
     */
    public function toObject()
    {
        $object = parent::toObject();
        if ($this->returnUrl !== null) {
            $object->returnUrl = $this->returnUrl;
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
        if (property_exists($object, 'returnUrl')) {
            $this->returnUrl = $object->returnUrl;
        }
        return $this;
    }
}
