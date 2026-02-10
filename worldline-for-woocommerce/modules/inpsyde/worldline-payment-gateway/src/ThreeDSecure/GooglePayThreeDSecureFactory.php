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
        if ($this->enforce3ds) {
            $threedSecure->setSkipAuthentication(\false);
            $threedSecure->setChallengeIndicator(ChallengeIndicator::CHALLENGE_REQUIRED);
            return $threedSecure;
        }
        if ($this->exemptionType !== null) {
            if (!$this->exemptionAmountChecker->isUnderLimit($orderAmount, $currencyCode)) {
                $threedSecure->setSkipAuthentication(\false);
                return $threedSecure;
            } else {
                return $this->manageExemptionForOrdersUnderLimit($threedSecure);
            }
        }
        return $threedSecure;
    }
    private function manageExemptionForOrdersUnderLimit(GPayThreeDSecure $threedSecure) : GPayThreeDSecure
    {
        switch ($this->exemptionType) {
            case ExemptionType::TRA:
                $threedSecure->setChallengeIndicator(ChallengeIndicator::NO_CHALLENGE_REQUESTED_RISK_ANALYSIS_PERFORMED);
                break;
            case ExemptionType::LOW_VALUE:
            case ExemptionType::NO_CHALLENGE_REQUESTED:
                $threedSecure->setChallengeIndicator(ChallengeIndicator::NO_CHALLENGE_REQUESTED);
                break;
            default:
                $threedSecure->setChallengeIndicator(ChallengeIndicator::NO_PREFERENCE);
        }
        $threedSecure->setSkipAuthentication(\false);
        $threedSecure->setExemptionRequest($this->exemptionType);
        return $threedSecure;
    }
}
