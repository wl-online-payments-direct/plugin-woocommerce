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
class RefundCardMethodSpecificOutput extends DataObject
{
    // Properties
    /**
     * @var CurrencyConversion
     */
    private $currencyConversion;
    /**
     * @var int
     */
    private $totalAmountPaid;
    /**
     * @var int
     */
    private $totalAmountRefunded;
    // Methods
    /**
     * @return CurrencyConversion
     */
    public function getCurrencyConversion()
    {
        return $this->currencyConversion;
    }
    /**
     * @var CurrencyConversion
     */
    public function setCurrencyConversion($value)
    {
        $this->currencyConversion = $value;
    }
    /**
     * @return int
     */
    public function getTotalAmountPaid()
    {
        return $this->totalAmountPaid;
    }
    /**
     * @var int
     */
    public function setTotalAmountPaid($value)
    {
        $this->totalAmountPaid = $value;
    }
    /**
     * @return int
     */
    public function getTotalAmountRefunded()
    {
        return $this->totalAmountRefunded;
    }
    /**
     * @var int
     */
    public function setTotalAmountRefunded($value)
    {
        $this->totalAmountRefunded = $value;
    }
    /**
     * @return object
     */
    public function toObject()
    {
        $object = parent::toObject();
        if ($this->currencyConversion !== null) {
            $object->currencyConversion = $this->currencyConversion->toObject();
        }
        if ($this->totalAmountPaid !== null) {
            $object->totalAmountPaid = $this->totalAmountPaid;
        }
        if ($this->totalAmountRefunded !== null) {
            $object->totalAmountRefunded = $this->totalAmountRefunded;
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
        if (property_exists($object, 'currencyConversion')) {
            if (!is_object($object->currencyConversion)) {
                throw new UnexpectedValueException('value \'' . print_r($object->currencyConversion, \true) . '\' is not an object');
            }
            $value = new CurrencyConversion();
            $this->currencyConversion = $value->fromObject($object->currencyConversion);
        }
        if (property_exists($object, 'totalAmountPaid')) {
            $this->totalAmountPaid = $object->totalAmountPaid;
        }
        if (property_exists($object, 'totalAmountRefunded')) {
            $this->totalAmountRefunded = $object->totalAmountRefunded;
        }
        return $this;
    }
}
