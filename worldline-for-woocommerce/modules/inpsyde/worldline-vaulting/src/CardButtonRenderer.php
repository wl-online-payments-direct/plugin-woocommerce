<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Vaulting;

use WC_Payment_Token_CC;
class CardButtonRenderer
{
    public function render(WC_Payment_Token_CC $token) : string
    {
        /*
         * translators: %1$s - last digits of the card.
         */
        $text = (string) \sprintf(\__('Pay with your stored card xxxx-%1$s', 'worldline-for-woocommerce'), $token->get_last4());
        return \sprintf('<div class="wlop-saved-card-button">
						<button class="button alt" data-token="%1$d">%2$s</button>
					</div>', $token->get_id(), \esc_html($text));
    }
}
