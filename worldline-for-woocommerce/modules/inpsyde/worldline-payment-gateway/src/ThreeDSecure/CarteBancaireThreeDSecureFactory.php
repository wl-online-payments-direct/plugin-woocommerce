<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\ThreeDSecure;

use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\AuthorizationMode;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\PaymentProduct130SpecificThreeDSecure;
class CarteBancaireThreeDSecureFactory
{
    private bool $enable3ds;
    private ?string $exemptionType;
    private ExemptionAmountChecker $exemptionAmountChecker;
    private string $authorizationMode;
    public function __construct(bool $enable3ds, ?string $exemptionType, ExemptionAmountChecker $exemptionAmountChecker, string $authorizationMode)
    {
        $this->enable3ds = $enable3ds;
        $this->exemptionType = $exemptionType;
        $this->exemptionAmountChecker = $exemptionAmountChecker;
        $this->authorizationMode = $authorizationMode;
    }
    public function create(int $orderAmount, string $currencyCode, int $numberOfItems) : ?PaymentProduct130SpecificThreeDSecure
    {
        if (!$this->enable3ds) {
            return null;
        }
        $carteBancaire3ds = new PaymentProduct130SpecificThreeDSecure();
        $carteBancaire3ds->setUsecase($this->authorizationMode === AuthorizationMode::SALE ? 'single-amount' : 'payment-upon-shipment');
        $carteBancaire3ds->setNumberOfItems(\min($numberOfItems, 99));
        if ($this->exemptionType === null) {
            $carteBancaire3ds->setAcquirerExemption(\false);
            return $carteBancaire3ds;
        }
        $carteBancaire3ds->setAcquirerExemption($this->exemptionAmountChecker->isUnderLimit($orderAmount, $currencyCode));
        return $carteBancaire3ds;
    }
}
