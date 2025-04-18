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
class DccCardSource extends DataObject
{
    // Properties
    /**
     * @var CardInfo
     */
    private $card;
    /**
     * @var string
     */
    private $encryptedCustomerInput;
    /**
     * @var string
     */
    private $hostedTokenizationId;
    /**
     * @var string
     */
    private $token;
    // Methods
    /**
     * @return CardInfo
     */
    public function getCard()
    {
        return $this->card;
    }
    /**
     * @var CardInfo
     */
    public function setCard($value)
    {
        $this->card = $value;
    }
    /**
     * @return string
     */
    public function getEncryptedCustomerInput()
    {
        return $this->encryptedCustomerInput;
    }
    /**
     * @var string
     */
    public function setEncryptedCustomerInput($value)
    {
        $this->encryptedCustomerInput = $value;
    }
    /**
     * @return string
     */
    public function getHostedTokenizationId()
    {
        return $this->hostedTokenizationId;
    }
    /**
     * @var string
     */
    public function setHostedTokenizationId($value)
    {
        $this->hostedTokenizationId = $value;
    }
    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }
    /**
     * @var string
     */
    public function setToken($value)
    {
        $this->token = $value;
    }
    /**
     * @return object
     */
    public function toObject()
    {
        $object = parent::toObject();
        if ($this->card !== null) {
            $object->card = $this->card->toObject();
        }
        if ($this->encryptedCustomerInput !== null) {
            $object->encryptedCustomerInput = $this->encryptedCustomerInput;
        }
        if ($this->hostedTokenizationId !== null) {
            $object->hostedTokenizationId = $this->hostedTokenizationId;
        }
        if ($this->token !== null) {
            $object->token = $this->token;
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
        if (property_exists($object, 'card')) {
            if (!is_object($object->card)) {
                throw new UnexpectedValueException('value \'' . print_r($object->card, \true) . '\' is not an object');
            }
            $value = new CardInfo();
            $this->card = $value->fromObject($object->card);
        }
        if (property_exists($object, 'encryptedCustomerInput')) {
            $this->encryptedCustomerInput = $object->encryptedCustomerInput;
        }
        if (property_exists($object, 'hostedTokenizationId')) {
            $this->hostedTokenizationId = $object->hostedTokenizationId;
        }
        if (property_exists($object, 'token')) {
            $this->token = $object->token;
        }
        return $this;
    }
}
