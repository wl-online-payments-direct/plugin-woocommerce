<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\ThreeDSecure;

use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\GPayThreeDSecure;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\RedirectionData;
class GooglePayThreeDSecureFactory
{
    private bool $enable3ds;
    private bool $enforce3ds;
    private ?string $exemptionType;
    private ExemptionAmountChecker $exemptionAmountChecker;
    public function __construct(bool $enable3ds, bool $enforce3ds, ?string $exemptionType, ExemptionAmountChecker $exemptionAmountChecker)
    {
        $this->enable3ds = $enable3ds;
        $this->enforce3ds = $enforce3ds;
        $this->exemptionType = $exemptionType;
        $this->exemptionAmountChecker = $exemptionAmountChecker;
    }
    public function create(int $orderAmount, string $currencyCode, string $returnUrl = '') : GPayThreeDSecure
    {
        $threedSecure = new GPayThreeDSecure();
        if (!empty($returnUrl)) {
            $redirectionData = new RedirectionData();
            $redirectionData->setReturnUrl($returnUrl);
            $threedSecure->setRedirectionData($redirectionData);
        }
        if (!$this->enable3ds) {
            $threedSecure->setSkipAuthentication(\true);
            return $threedSecure;
        }
        if ($this->exemptionType === null || !$this->exemptionAmountChecker->isUnderLimit($orderAmount, $currencyCode)) {
            $threedSecure->setSkipAuthentication(\false);
            $threedSecure->setChallengeIndicator($this->enforce3ds ? ChallengeIndicator::CHALLENGE_REQUIRED : ChallengeIndicator::NO_PREFERENCE);
            return $threedSecure;
        }
        $threedSecure->setSkipAuthentication(\true);
        $threedSecure->setExemptionRequest($this->exemptionType);
        return $threedSecure;
    }
}
