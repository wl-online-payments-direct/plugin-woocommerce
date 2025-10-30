<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment;

use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\RedirectionData;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\ThreeDSecure;
class ThreeDSecureFactory
{
    public const EXEMPTION_LOW_VALUE = 'low-value';
    public const CHALLENGE_REQUIRED = 'challenge-required';
    private bool $enable3ds;
    private ?string $enforce3ds;
    private ?string $exemption3ds;
    public function __construct(bool $enable3ds, ?string $enforce3ds, ?string $exemption3ds)
    {
        $this->enable3ds = $enable3ds;
        $this->enforce3ds = $enforce3ds;
        $this->exemption3ds = $exemption3ds;
    }
    public function create(int $orderAmount, string $currencyCode, string $returnUrl = '') : ThreeDSecure
    {
        $threedSecure = new ThreeDSecure();
        if (!empty($returnUrl)) {
            $redirectionData = new RedirectionData();
            $redirectionData->setReturnUrl($returnUrl);
            $threedSecure->setRedirectionData($redirectionData);
        }
        if (!$this->enable3ds) {
            $threedSecure->setSkipAuthentication(\true);
            return $threedSecure;
        }
        $enforce3ds = null;
        $exemption3ds = null;
        if ($this->enforce3ds === self::CHALLENGE_REQUIRED) {
            $enforce3ds = self::CHALLENGE_REQUIRED;
        }
        if ($this->exemption3ds === self::EXEMPTION_LOW_VALUE && $currencyCode === 'EUR' && $orderAmount < 3000) {
            $enforce3ds = null;
            $exemption3ds = self::EXEMPTION_LOW_VALUE;
        }
        if ($enforce3ds) {
            $threedSecure->setChallengeIndicator($enforce3ds);
        }
        $threedSecure->setExemptionRequest($exemption3ds);
        return $threedSecure;
    }
}
