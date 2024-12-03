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
class GetIINDetailsResponse extends DataObject
{
    // Properties
    /**
     * @var string
     */
    private $cardType;
    /**
     * @var IINDetail[]
     */
    private $coBrands;
    /**
     * @var string
     */
    private $countryCode;
    /**
     * @var bool
     */
    private $isAllowedInContext;
    /**
     * @var int
     */
    private $paymentProductId;
    // Methods
    /**
     * @return string
     */
    public function getCardType()
    {
        return $this->cardType;
    }
    /**
     * @var string
     */
    public function setCardType($value)
    {
        $this->cardType = $value;
    }
    /**
     * @return IINDetail[]
     */
    public function getCoBrands()
    {
        return $this->coBrands;
    }
    /**
     * @var IINDetail[]
     */
    public function setCoBrands($value)
    {
        $this->coBrands = $value;
    }
    /**
     * @return string
     */
    public function getCountryCode()
    {
        return $this->countryCode;
    }
    /**
     * @var string
     */
    public function setCountryCode($value)
    {
        $this->countryCode = $value;
    }
    /**
     * @return bool
     */
    public function getIsAllowedInContext()
    {
        return $this->isAllowedInContext;
    }
    /**
     * @var bool
     */
    public function setIsAllowedInContext($value)
    {
        $this->isAllowedInContext = $value;
    }
    /**
     * @return int
     */
    public function getPaymentProductId()
    {
        return $this->paymentProductId;
    }
    /**
     * @var int
     */
    public function setPaymentProductId($value)
    {
        $this->paymentProductId = $value;
    }
    /**
     * @return object
     */
    public function toObject()
    {
        $object = parent::toObject();
        if ($this->cardType !== null) {
            $object->cardType = $this->cardType;
        }
        if ($this->coBrands !== null) {
            $object->coBrands = [];
            foreach ($this->coBrands as $element) {
                if ($element !== null) {
                    $object->coBrands[] = $element->toObject();
                }
            }
        }
        if ($this->countryCode !== null) {
            $object->countryCode = $this->countryCode;
        }
        if ($this->isAllowedInContext !== null) {
            $object->isAllowedInContext = $this->isAllowedInContext;
        }
        if ($this->paymentProductId !== null) {
            $object->paymentProductId = $this->paymentProductId;
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
        if (property_exists($object, 'cardType')) {
            $this->cardType = $object->cardType;
        }
        if (property_exists($object, 'coBrands')) {
            if (!is_array($object->coBrands) && !is_object($object->coBrands)) {
                throw new UnexpectedValueException('value \'' . print_r($object->coBrands, \true) . '\' is not an array or object');
            }
            $this->coBrands = [];
            foreach ($object->coBrands as $element) {
                $value = new IINDetail();
                $this->coBrands[] = $value->fromObject($element);
            }
        }
        if (property_exists($object, 'countryCode')) {
            $this->countryCode = $object->countryCode;
        }
        if (property_exists($object, 'isAllowedInContext')) {
            $this->isAllowedInContext = $object->isAllowedInContext;
        }
        if (property_exists($object, 'paymentProductId')) {
            $this->paymentProductId = $object->paymentProductId;
        }
        return $this;
    }
}
