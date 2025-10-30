<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Vaulting;

use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CardEssentials;
use WC_Payment_Token;
use WC_Payment_Token_CC;
use WC_Payment_Tokens;
class WcTokenRepository
{
    protected string $gatewayId;
    protected CardBinParser $cardBinParser;
    /**
     * @param string $gatewayId The WC gateway ID.
     * @param CardBinParser $cardBinParser
     */
    public function __construct(string $gatewayId, CardBinParser $cardBinParser)
    {
        $this->gatewayId = $gatewayId;
        $this->cardBinParser = $cardBinParser;
    }
    /**
     * Saves the card token into the WC customer account.
     *
     * @param string $token The token value.
     * @param int $userId The WC customer ID.
     * @param CardEssentials $card The card info.
     * @param int $paymentProductId The Worldline payment product ID.
     */
    public function addCard(string $token, int $userId, CardEssentials $card, int $paymentProductId) : void
    {
        $existingTokens = $this->customerTokens($userId);
        $expiryDate = $this->determineExpiryDate($card);
        foreach ($existingTokens as $existingToken) {
            if ($existingToken->get_token() === $token || $expiryDate && $existingToken->get_meta('last4') === $this->determineLast4($card) && $existingToken->get_meta('card_type') === $this->determineCardType($card) && $existingToken->get_meta('expiry_year') === (string) $expiryDate->year() && $existingToken->get_meta('expiry_month') === \str_pad((string) $expiryDate->month(), 2, '0', \STR_PAD_LEFT)) {
                \do_action('wlop.card_token_already_exists', ['token' => $token, 'userId' => $userId, 'wcTokenObj' => $existingToken, 'paymentProductId' => $paymentProductId]);
                return;
            }
        }
        $meta = ['paymentProductId' => $paymentProductId];
        $wcToken = new WC_Payment_Token_CC();
        $wcToken->set_token($token);
        $wcToken->set_user_id($userId);
        $wcToken->set_gateway_id($this->gatewayId);
        $wcToken->set_meta_data($meta);
        $wcToken->set_card_type($this->determineCardType($card));
        $wcToken->set_last4($this->determineLast4($card));
        $this->fillExpiryDate($card, $wcToken);
        $wcToken->save();
        \do_action('wlop.card_token_saved', ['token' => $token, 'userId' => $userId, 'wcTokenObj' => $wcToken, 'paymentProductId' => $paymentProductId]);
    }
    public function get(int $tokenId) : ?WC_Payment_Token
    {
        return WC_Payment_Tokens::get($tokenId);
    }
    /**
     * @param int $userId
     * @return WC_Payment_Token[]
     */
    public function customerTokens(int $userId) : array
    {
        return WC_Payment_Tokens::get_customer_tokens($userId, $this->gatewayId);
    }
    /**
     * Returns the tokens sorted from last to first,
     * and the first token is the one marked as default.
     *
     * @param int $userId
     * @return WC_Payment_Token[]
     */
    public function sortedCustomerTokens(int $userId) : array
    {
        $tokens = $this->customerTokens($userId);
        if (\count($tokens) < 2) {
            return $tokens;
        }
        $sortKey = static fn(WC_Payment_Token $token): int => $token->is_default() ? \PHP_INT_MAX : $token->get_id();
        \usort($tokens, static fn(WC_Payment_Token $tk1, WC_Payment_Token $tk2): int => $sortKey($tk2) - $sortKey($tk1));
        return $tokens;
    }
    protected function determineLast4(CardEssentials $card) : string
    {
        $last4 = \substr($card->getCardNumber(), -4);
        if (\is_string($last4) && \strlen($last4) === 4) {
            return $last4;
        }
        return '0000';
    }
    protected function determineCardType(CardEssentials $card) : string
    {
        $brand = $this->cardBinParser->detectBrand((string) $card->getBin());
        if ($brand === null) {
            return 'card';
        }
        return $brand;
    }
    protected function determineExpiryDate(CardEssentials $card) : ?ExpiryDate
    {
        return ExpiryDate::fromMMYY((string) $card->getExpiryDate());
    }
    protected function fillExpiryDate(CardEssentials $card, WC_Payment_Token_CC $wcToken) : void
    {
        $expiryDate = $this->determineExpiryDate($card);
        if (!$expiryDate) {
            $wcToken->set_expiry_year('0000');
            $wcToken->set_expiry_month('00');
            return;
        }
        $wcToken->set_expiry_year((string) $expiryDate->year());
        $wcToken->set_expiry_month($expiryDate->mm());
    }
}
