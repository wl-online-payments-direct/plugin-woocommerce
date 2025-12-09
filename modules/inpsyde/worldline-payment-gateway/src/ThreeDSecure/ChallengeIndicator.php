<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\ThreeDSecure;

interface ChallengeIndicator
{
    public const NO_PREFERENCE = 'no-preference';
    public const CHALLENGE_REQUIRED = 'challenge-required';
    public const NO_CHALLENGE_REQUESTED_RISK_ANALYSIS_PERFORMED = 'no-challenge-requested-risk-analysis-performed';
    public const NO_CHALLENGE_REQUESTED = 'no-challenge-requested';
}
