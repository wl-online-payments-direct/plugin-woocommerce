<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\ThreeDSecure;

interface ChallengeIndicator
{
    public const NO_PREFERENCE = 'no-preference';
    public const CHALLENGE_REQUIRED = 'challenge-required';
}
