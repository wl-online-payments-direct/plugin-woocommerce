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
class RegularExpressionValidator extends DataObject
{
    // Properties
    /**
     * @var string
     */
    private $regularExpression;
    // Methods
    /**
     * @return string
     */
    public function getRegularExpression()
    {
        return $this->regularExpression;
    }
    /**
     * @var string
     */
    public function setRegularExpression($value)
    {
        $this->regularExpression = $value;
    }
    /**
     * @return object
     */
    public function toObject()
    {
        $object = parent::toObject();
        if ($this->regularExpression !== null) {
            $object->regularExpression = $this->regularExpression;
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
        if (property_exists($object, 'regularExpression')) {
            $this->regularExpression = $object->regularExpression;
        }
        return $this;
    }
}
