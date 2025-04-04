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
class CardPaymentMethodSpecificInputBase extends DataObject
{
    // Properties
    /**
     * @var bool
     */
    private $allowDynamicLinking;
    /**
     * @var string
     */
    private $authorizationMode;
    /**
     * @var CurrencyConversionSpecificInput
     */
    private $currencyConversionSpecificInput;
    /**
     * @var string
     */
    private $initialSchemeTransactionId;
    /**
     * @var MultiplePaymentInformation
     */
    private $multiplePaymentInformation;
    /**
     * @var PaymentProduct130SpecificInput
     */
    private $paymentProduct130SpecificInput;
    /**
     * @var PaymentProduct3208SpecificInput
     */
    private $paymentProduct3208SpecificInput;
    /**
     * @var PaymentProduct3209SpecificInput
     */
    private $paymentProduct3209SpecificInput;
    /**
     * @var PaymentProduct5100SpecificInput
     */
    private $paymentProduct5100SpecificInput;
    /**
     * @var int
     */
    private $paymentProductId;
    /**
     * @var CardRecurrenceDetails
     */
    private $recurring;
    /**
     * @var ThreeDSecureBase
     */
    private $threeDSecure;
    /**
     * @var string
     */
    private $token;
    /**
     * @var bool
     */
    private $tokenize;
    /**
     * @var string
     */
    private $transactionChannel;
    /**
     * @var string
     */
    private $unscheduledCardOnFileRequestor;
    /**
     * @var string
     */
    private $unscheduledCardOnFileSequenceIndicator;
    // Methods
    /**
     * @return bool
     */
    public function getAllowDynamicLinking()
    {
        return $this->allowDynamicLinking;
    }
    /**
     * @var bool
     */
    public function setAllowDynamicLinking($value)
    {
        $this->allowDynamicLinking = $value;
    }
    /**
     * @return string
     */
    public function getAuthorizationMode()
    {
        return $this->authorizationMode;
    }
    /**
     * @var string
     */
    public function setAuthorizationMode($value)
    {
        $this->authorizationMode = $value;
    }
    /**
     * @return CurrencyConversionSpecificInput
     */
    public function getCurrencyConversionSpecificInput()
    {
        return $this->currencyConversionSpecificInput;
    }
    /**
     * @var CurrencyConversionSpecificInput
     */
    public function setCurrencyConversionSpecificInput($value)
    {
        $this->currencyConversionSpecificInput = $value;
    }
    /**
     * @return string
     */
    public function getInitialSchemeTransactionId()
    {
        return $this->initialSchemeTransactionId;
    }
    /**
     * @var string
     */
    public function setInitialSchemeTransactionId($value)
    {
        $this->initialSchemeTransactionId = $value;
    }
    /**
     * @return MultiplePaymentInformation
     */
    public function getMultiplePaymentInformation()
    {
        return $this->multiplePaymentInformation;
    }
    /**
     * @var MultiplePaymentInformation
     */
    public function setMultiplePaymentInformation($value)
    {
        $this->multiplePaymentInformation = $value;
    }
    /**
     * @return PaymentProduct130SpecificInput
     */
    public function getPaymentProduct130SpecificInput()
    {
        return $this->paymentProduct130SpecificInput;
    }
    /**
     * @var PaymentProduct130SpecificInput
     */
    public function setPaymentProduct130SpecificInput($value)
    {
        $this->paymentProduct130SpecificInput = $value;
    }
    /**
     * @return PaymentProduct3208SpecificInput
     */
    public function getPaymentProduct3208SpecificInput()
    {
        return $this->paymentProduct3208SpecificInput;
    }
    /**
     * @var PaymentProduct3208SpecificInput
     */
    public function setPaymentProduct3208SpecificInput($value)
    {
        $this->paymentProduct3208SpecificInput = $value;
    }
    /**
     * @return PaymentProduct3209SpecificInput
     */
    public function getPaymentProduct3209SpecificInput()
    {
        return $this->paymentProduct3209SpecificInput;
    }
    /**
     * @var PaymentProduct3209SpecificInput
     */
    public function setPaymentProduct3209SpecificInput($value)
    {
        $this->paymentProduct3209SpecificInput = $value;
    }
    /**
     * @return PaymentProduct5100SpecificInput
     */
    public function getPaymentProduct5100SpecificInput()
    {
        return $this->paymentProduct5100SpecificInput;
    }
    /**
     * @var PaymentProduct5100SpecificInput
     */
    public function setPaymentProduct5100SpecificInput($value)
    {
        $this->paymentProduct5100SpecificInput = $value;
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
     * @return CardRecurrenceDetails
     */
    public function getRecurring()
    {
        return $this->recurring;
    }
    /**
     * @var CardRecurrenceDetails
     */
    public function setRecurring($value)
    {
        $this->recurring = $value;
    }
    /**
     * @return ThreeDSecureBase
     */
    public function getThreeDSecure()
    {
        return $this->threeDSecure;
    }
    /**
     * @var ThreeDSecureBase
     */
    public function setThreeDSecure($value)
    {
        $this->threeDSecure = $value;
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
     * @return bool
     */
    public function getTokenize()
    {
        return $this->tokenize;
    }
    /**
     * @var bool
     */
    public function setTokenize($value)
    {
        $this->tokenize = $value;
    }
    /**
     * @return string
     */
    public function getTransactionChannel()
    {
        return $this->transactionChannel;
    }
    /**
     * @var string
     */
    public function setTransactionChannel($value)
    {
        $this->transactionChannel = $value;
    }
    /**
     * @return string
     */
    public function getUnscheduledCardOnFileRequestor()
    {
        return $this->unscheduledCardOnFileRequestor;
    }
    /**
     * @var string
     */
    public function setUnscheduledCardOnFileRequestor($value)
    {
        $this->unscheduledCardOnFileRequestor = $value;
    }
    /**
     * @return string
     */
    public function getUnscheduledCardOnFileSequenceIndicator()
    {
        return $this->unscheduledCardOnFileSequenceIndicator;
    }
    /**
     * @var string
     */
    public function setUnscheduledCardOnFileSequenceIndicator($value)
    {
        $this->unscheduledCardOnFileSequenceIndicator = $value;
    }
    /**
     * @return object
     */
    public function toObject()
    {
        $object = parent::toObject();
        if ($this->allowDynamicLinking !== null) {
            $object->allowDynamicLinking = $this->allowDynamicLinking;
        }
        if ($this->authorizationMode !== null) {
            $object->authorizationMode = $this->authorizationMode;
        }
        if ($this->currencyConversionSpecificInput !== null) {
            $object->currencyConversionSpecificInput = $this->currencyConversionSpecificInput->toObject();
        }
        if ($this->initialSchemeTransactionId !== null) {
            $object->initialSchemeTransactionId = $this->initialSchemeTransactionId;
        }
        if ($this->multiplePaymentInformation !== null) {
            $object->multiplePaymentInformation = $this->multiplePaymentInformation->toObject();
        }
        if ($this->paymentProduct130SpecificInput !== null) {
            $object->paymentProduct130SpecificInput = $this->paymentProduct130SpecificInput->toObject();
        }
        if ($this->paymentProduct3208SpecificInput !== null) {
            $object->paymentProduct3208SpecificInput = $this->paymentProduct3208SpecificInput->toObject();
        }
        if ($this->paymentProduct3209SpecificInput !== null) {
            $object->paymentProduct3209SpecificInput = $this->paymentProduct3209SpecificInput->toObject();
        }
        if ($this->paymentProduct5100SpecificInput !== null) {
            $object->paymentProduct5100SpecificInput = $this->paymentProduct5100SpecificInput->toObject();
        }
        if ($this->paymentProductId !== null) {
            $object->paymentProductId = $this->paymentProductId;
        }
        if ($this->recurring !== null) {
            $object->recurring = $this->recurring->toObject();
        }
        if ($this->threeDSecure !== null) {
            $object->threeDSecure = $this->threeDSecure->toObject();
        }
        if ($this->token !== null) {
            $object->token = $this->token;
        }
        if ($this->tokenize !== null) {
            $object->tokenize = $this->tokenize;
        }
        if ($this->transactionChannel !== null) {
            $object->transactionChannel = $this->transactionChannel;
        }
        if ($this->unscheduledCardOnFileRequestor !== null) {
            $object->unscheduledCardOnFileRequestor = $this->unscheduledCardOnFileRequestor;
        }
        if ($this->unscheduledCardOnFileSequenceIndicator !== null) {
            $object->unscheduledCardOnFileSequenceIndicator = $this->unscheduledCardOnFileSequenceIndicator;
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
        if (property_exists($object, 'allowDynamicLinking')) {
            $this->allowDynamicLinking = $object->allowDynamicLinking;
        }
        if (property_exists($object, 'authorizationMode')) {
            $this->authorizationMode = $object->authorizationMode;
        }
        if (property_exists($object, 'currencyConversionSpecificInput')) {
            if (!is_object($object->currencyConversionSpecificInput)) {
                throw new UnexpectedValueException('value \'' . print_r($object->currencyConversionSpecificInput, \true) . '\' is not an object');
            }
            $value = new CurrencyConversionSpecificInput();
            $this->currencyConversionSpecificInput = $value->fromObject($object->currencyConversionSpecificInput);
        }
        if (property_exists($object, 'initialSchemeTransactionId')) {
            $this->initialSchemeTransactionId = $object->initialSchemeTransactionId;
        }
        if (property_exists($object, 'multiplePaymentInformation')) {
            if (!is_object($object->multiplePaymentInformation)) {
                throw new UnexpectedValueException('value \'' . print_r($object->multiplePaymentInformation, \true) . '\' is not an object');
            }
            $value = new MultiplePaymentInformation();
            $this->multiplePaymentInformation = $value->fromObject($object->multiplePaymentInformation);
        }
        if (property_exists($object, 'paymentProduct130SpecificInput')) {
            if (!is_object($object->paymentProduct130SpecificInput)) {
                throw new UnexpectedValueException('value \'' . print_r($object->paymentProduct130SpecificInput, \true) . '\' is not an object');
            }
            $value = new PaymentProduct130SpecificInput();
            $this->paymentProduct130SpecificInput = $value->fromObject($object->paymentProduct130SpecificInput);
        }
        if (property_exists($object, 'paymentProduct3208SpecificInput')) {
            if (!is_object($object->paymentProduct3208SpecificInput)) {
                throw new UnexpectedValueException('value \'' . print_r($object->paymentProduct3208SpecificInput, \true) . '\' is not an object');
            }
            $value = new PaymentProduct3208SpecificInput();
            $this->paymentProduct3208SpecificInput = $value->fromObject($object->paymentProduct3208SpecificInput);
        }
        if (property_exists($object, 'paymentProduct3209SpecificInput')) {
            if (!is_object($object->paymentProduct3209SpecificInput)) {
                throw new UnexpectedValueException('value \'' . print_r($object->paymentProduct3209SpecificInput, \true) . '\' is not an object');
            }
            $value = new PaymentProduct3209SpecificInput();
            $this->paymentProduct3209SpecificInput = $value->fromObject($object->paymentProduct3209SpecificInput);
        }
        if (property_exists($object, 'paymentProduct5100SpecificInput')) {
            if (!is_object($object->paymentProduct5100SpecificInput)) {
                throw new UnexpectedValueException('value \'' . print_r($object->paymentProduct5100SpecificInput, \true) . '\' is not an object');
            }
            $value = new PaymentProduct5100SpecificInput();
            $this->paymentProduct5100SpecificInput = $value->fromObject($object->paymentProduct5100SpecificInput);
        }
        if (property_exists($object, 'paymentProductId')) {
            $this->paymentProductId = $object->paymentProductId;
        }
        if (property_exists($object, 'recurring')) {
            if (!is_object($object->recurring)) {
                throw new UnexpectedValueException('value \'' . print_r($object->recurring, \true) . '\' is not an object');
            }
            $value = new CardRecurrenceDetails();
            $this->recurring = $value->fromObject($object->recurring);
        }
        if (property_exists($object, 'threeDSecure')) {
            if (!is_object($object->threeDSecure)) {
                throw new UnexpectedValueException('value \'' . print_r($object->threeDSecure, \true) . '\' is not an object');
            }
            $value = new ThreeDSecureBase();
            $this->threeDSecure = $value->fromObject($object->threeDSecure);
        }
        if (property_exists($object, 'token')) {
            $this->token = $object->token;
        }
        if (property_exists($object, 'tokenize')) {
            $this->tokenize = $object->tokenize;
        }
        if (property_exists($object, 'transactionChannel')) {
            $this->transactionChannel = $object->transactionChannel;
        }
        if (property_exists($object, 'unscheduledCardOnFileRequestor')) {
            $this->unscheduledCardOnFileRequestor = $object->unscheduledCardOnFileRequestor;
        }
        if (property_exists($object, 'unscheduledCardOnFileSequenceIndicator')) {
            $this->unscheduledCardOnFileSequenceIndicator = $object->unscheduledCardOnFileSequenceIndicator;
        }
        return $this;
    }
}
