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
class MandatePersonalInformationResponse extends DataObject
{
    // Properties
    /**
     * @var MandatePersonalNameResponse
     */
    private $name;
    /**
     * @var string
     */
    private $title;
    // Methods
    /**
     * @return MandatePersonalNameResponse
     */
    public function getName()
    {
        return $this->name;
    }
    /**
     * @var MandatePersonalNameResponse
     */
    public function setName($value)
    {
        $this->name = $value;
    }
    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }
    /**
     * @var string
     */
    public function setTitle($value)
    {
        $this->title = $value;
    }
    /**
     * @return object
     */
    public function toObject()
    {
        $object = parent::toObject();
        if ($this->name !== null) {
            $object->name = $this->name->toObject();
        }
        if ($this->title !== null) {
            $object->title = $this->title;
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
        if (property_exists($object, 'name')) {
            if (!is_object($object->name)) {
                throw new UnexpectedValueException('value \'' . print_r($object->name, \true) . '\' is not an object');
            }
            $value = new MandatePersonalNameResponse();
            $this->name = $value->fromObject($object->name);
        }
        if (property_exists($object, 'title')) {
            $this->title = $object->title;
        }
        return $this;
    }
}
