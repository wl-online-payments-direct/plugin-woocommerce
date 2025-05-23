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
class CustomerBankAccount extends DataObject
{
    // Properties
    /**
     * @var string
     */
    private $accountHolderName;
    /**
     * @var string
     */
    private $bic;
    /**
     * @var string
     */
    private $iban;
    // Methods
    /**
     * @return string
     */
    public function getAccountHolderName()
    {
        return $this->accountHolderName;
    }
    /**
     * @var string
     */
    public function setAccountHolderName($value)
    {
        $this->accountHolderName = $value;
    }
    /**
     * @return string
     */
    public function getBic()
    {
        return $this->bic;
    }
    /**
     * @var string
     */
    public function setBic($value)
    {
        $this->bic = $value;
    }
    /**
     * @return string
     */
    public function getIban()
    {
        return $this->iban;
    }
    /**
     * @var string
     */
    public function setIban($value)
    {
        $this->iban = $value;
    }
    /**
     * @return object
     */
    public function toObject()
    {
        $object = parent::toObject();
        if ($this->accountHolderName !== null) {
            $object->accountHolderName = $this->accountHolderName;
        }
        if ($this->bic !== null) {
            $object->bic = $this->bic;
        }
        if ($this->iban !== null) {
            $object->iban = $this->iban;
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
        if (property_exists($object, 'accountHolderName')) {
            $this->accountHolderName = $object->accountHolderName;
        }
        if (property_exists($object, 'bic')) {
            $this->bic = $object->bic;
        }
        if (property_exists($object, 'iban')) {
            $this->iban = $object->iban;
        }
        return $this;
    }
}
