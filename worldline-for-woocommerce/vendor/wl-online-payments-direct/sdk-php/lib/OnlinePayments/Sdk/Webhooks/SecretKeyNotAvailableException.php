<?php

namespace Syde\Vendor\Worldline\OnlinePayments\Sdk\Webhooks;

use Exception;
/**
 * Class SecretKeyNotAvailableException
 *
 * @package OnlinePayments\Sdk\Webhooks
 */
class SecretKeyNotAvailableException extends SignatureValidationException
{
    /** @var string */
    private $keyId;
    /**
     * @param string $keyId
     * @param string $message
     * @param Exception $previous
     */
    public function __construct($keyId, $message = null, $previous = null)
    {
        parent::__construct($message, $previous);
        $this->keyId = $keyId;
    }
    /**
     * @return string
     */
    public function getKeyId()
    {
        return $this->keyId;
    }
}
