<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline;

use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\GatewayIds;
return static function () : array {
    $sepaGatewayId = GatewayIds::SEPA_DIRECT_DEBIT;
    $sepaOptionName = 'woocommerce_' . $sepaGatewayId . '_settings';
    $getGlobalSignature = static function () : string {
        $global = \get_option('woocommerce_worldline-for-woocommerce_settings', []);
        $val = isset($global['sdd_signature_type']) ? (string) $global['sdd_signature_type'] : '';
        return $val !== '' ? $val : 'SMS';
    };
    \add_filter("option_{$sepaOptionName}", static function ($value) use($getGlobalSignature) {
        if (!\is_array($value)) {
            $value = [];
        }
        $value['sdd_signature_type'] = $getGlobalSignature();
        return $value;
    });
    \add_filter("pre_update_option_{$sepaOptionName}", static function ($newValue, $oldValue) {
        if (\is_array($newValue) && isset($newValue['sdd_signature_type'])) {
            $posted = (string) $newValue['sdd_signature_type'];
            $posted = $posted === 'UNSIGNED' ? 'UNSIGNED' : 'SMS';
            $global = \get_option('woocommerce_worldline-for-woocommerce_settings', []);
            $global['sdd_signature_type'] = $posted;
            \update_option('woocommerce_worldline-for-woocommerce_settings', $global);
            unset($newValue['sdd_signature_type']);
        }
        return $newValue;
    }, 10, 2);
    return ['payment_gateways' => static function (array $gateways) : array {
        $gateways[] = GatewayIds::SEPA_DIRECT_DEBIT;
        return $gateways;
    }];
};
