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
class TestConnection extends DataObject
{
    // Properties
    /**
     * @var string
     */
    private $result;
    // Methods
    /**
     * @return string
     */
    public function getResult()
    {
        return $this->result;
    }
    /**
     * @var string
     */
    public function setResult($value)
    {
        $this->result = $value;
    }
    /**
     * @return object
     */
    public function toObject()
    {
        $object = parent::toObject();
        if ($this->result !== null) {
            $object->result = $this->result;
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
        if (property_exists($object, 'result')) {
            $this->result = $object->result;
        }
        return $this;
    }
}
