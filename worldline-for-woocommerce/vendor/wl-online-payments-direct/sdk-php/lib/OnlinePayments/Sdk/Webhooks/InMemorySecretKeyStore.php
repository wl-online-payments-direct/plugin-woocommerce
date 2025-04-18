<?php

namespace Syde\Vendor\Worldline\OnlinePayments\Sdk\Webhooks;

use UnexpectedValueException;
/**
 * Class InMemorySecretKeyStore
 *
 * @package OnlinePayments\Sdk\Webhooks
 */
class InMemorySecretKeyStore implements SecretKeyStore
{
    /** @var array<string> */
    private $secretKeys;
    /**
     * @param array<string> $secretKeys
     */
    public function __construct($secretKeys = [])
    {
        $this->secretKeys = $secretKeys;
    }
    /**
     * @param string $keyId
     * @return string
     * @throws SecretKeyNotAvailableException
     */
    public function getSecretKey($keyId)
    {
        if (!isset($this->secretKeys[$keyId]) || is_null($this->secretKeys[$keyId])) {
            throw new SecretKeyNotAvailableException($keyId, "could not find secret key for key id {$keyId}");
        }
        return $this->secretKeys[$keyId];
    }
    /**
     * Stores the given secret key for the given key id.
     * @param string|null $keyId
     * @param string|null $secretKey
     */
    public function storeSecretKey($keyId, $secretKey)
    {
        if (is_null($keyId) || strlen(trim($keyId)) == 0) {
            throw new UnexpectedValueException("keyId is required");
        }
        if (is_null($secretKey) || strlen(trim($secretKey)) == 0) {
            throw new UnexpectedValueException("secretKey is required");
        }
        $this->secretKeys[$keyId] = $secretKey;
    }
    /**
     * Removes the secret key for the given key id.
     * @param string $keyId
     */
    public function removeSecretKey($keyId)
    {
        unset($this->secretKeys[$keyId]);
    }
    /**
     * Removes all stored secret keys.
     */
    public function clear()
    {
        unset($this->secretKeys);
        $this->secretKeys = array();
    }
}
