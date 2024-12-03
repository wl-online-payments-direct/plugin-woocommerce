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
class CreatePayoutRequest extends DataObject
{
    // Properties
    /**
     * @var AmountOfMoney
     */
    private $amountOfMoney;
    /**
     * @var CardPayoutMethodSpecificInput
     */
    private $cardPayoutMethodSpecificInput;
    /**
     * @var OmnichannelPayoutSpecificInput
     */
    private $omnichannelPayoutSpecificInput;
    /**
     * @var PaymentReferences
     */
    private $references;
    // Methods
    /**
     * @return AmountOfMoney
     */
    public function getAmountOfMoney()
    {
        return $this->amountOfMoney;
    }
    /**
     * @var AmountOfMoney
     */
    public function setAmountOfMoney($value)
    {
        $this->amountOfMoney = $value;
    }
    /**
     * @return CardPayoutMethodSpecificInput
     */
    public function getCardPayoutMethodSpecificInput()
    {
        return $this->cardPayoutMethodSpecificInput;
    }
    /**
     * @var CardPayoutMethodSpecificInput
     */
    public function setCardPayoutMethodSpecificInput($value)
    {
        $this->cardPayoutMethodSpecificInput = $value;
    }
    /**
     * @return OmnichannelPayoutSpecificInput
     */
    public function getOmnichannelPayoutSpecificInput()
    {
        return $this->omnichannelPayoutSpecificInput;
    }
    /**
     * @var OmnichannelPayoutSpecificInput
     */
    public function setOmnichannelPayoutSpecificInput($value)
    {
        $this->omnichannelPayoutSpecificInput = $value;
    }
    /**
     * @return PaymentReferences
     */
    public function getReferences()
    {
        return $this->references;
    }
    /**
     * @var PaymentReferences
     */
    public function setReferences($value)
    {
        $this->references = $value;
    }
    /**
     * @return object
     */
    public function toObject()
    {
        $object = parent::toObject();
        if ($this->amountOfMoney !== null) {
            $object->amountOfMoney = $this->amountOfMoney->toObject();
        }
        if ($this->cardPayoutMethodSpecificInput !== null) {
            $object->cardPayoutMethodSpecificInput = $this->cardPayoutMethodSpecificInput->toObject();
        }
        if ($this->omnichannelPayoutSpecificInput !== null) {
            $object->omnichannelPayoutSpecificInput = $this->omnichannelPayoutSpecificInput->toObject();
        }
        if ($this->references !== null) {
            $object->references = $this->references->toObject();
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
        if (property_exists($object, 'amountOfMoney')) {
            if (!is_object($object->amountOfMoney)) {
                throw new UnexpectedValueException('value \'' . print_r($object->amountOfMoney, \true) . '\' is not an object');
            }
            $value = new AmountOfMoney();
            $this->amountOfMoney = $value->fromObject($object->amountOfMoney);
        }
        if (property_exists($object, 'cardPayoutMethodSpecificInput')) {
            if (!is_object($object->cardPayoutMethodSpecificInput)) {
                throw new UnexpectedValueException('value \'' . print_r($object->cardPayoutMethodSpecificInput, \true) . '\' is not an object');
            }
            $value = new CardPayoutMethodSpecificInput();
            $this->cardPayoutMethodSpecificInput = $value->fromObject($object->cardPayoutMethodSpecificInput);
        }
        if (property_exists($object, 'omnichannelPayoutSpecificInput')) {
            if (!is_object($object->omnichannelPayoutSpecificInput)) {
                throw new UnexpectedValueException('value \'' . print_r($object->omnichannelPayoutSpecificInput, \true) . '\' is not an object');
            }
            $value = new OmnichannelPayoutSpecificInput();
            $this->omnichannelPayoutSpecificInput = $value->fromObject($object->omnichannelPayoutSpecificInput);
        }
        if (property_exists($object, 'references')) {
            if (!is_object($object->references)) {
                throw new UnexpectedValueException('value \'' . print_r($object->references, \true) . '\' is not an object');
            }
            $value = new PaymentReferences();
            $this->references = $value->fromObject($object->references);
        }
        return $this;
    }
}
