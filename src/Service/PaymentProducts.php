<?php declare(strict_types=1);

/**
 * @author Mediaopt GmbH
 * @package MoptWorldline\Service
 */

namespace MoptWorldline\Service;

use OnlinePayments\Sdk\Domain\GetHostedTokenizationResponse;
use OnlinePayments\Sdk\Domain\PaymentDetailsResponse;

class PaymentProducts
{
    const PAYMENT_PRODUCT_INTERSOLVE = 5700;
    const PAYMENT_PRODUCT_ONEY_3X_4X = 5110;
    const PAYMENT_PRODUCT_ONEY_FINANCEMENT_LONG = 5125;
    const PAYMENT_PRODUCT_ONEY_BRANDED_GIFT_CARD = 5600;
    const PAYMENT_PRODUCT_KLARNA_PAY_NOW = 3301;
    const PAYMENT_PRODUCT_KLARNA_PAY_LATER = 3302;
    const PAYMENT_PRODUCT_TWINTWL = 5407;
    const PAYMENT_PRODUCT_NEED_DETAILS = [
          self::PAYMENT_PRODUCT_ONEY_3X_4X,
          self::PAYMENT_PRODUCT_ONEY_FINANCEMENT_LONG,
          self::PAYMENT_PRODUCT_ONEY_BRANDED_GIFT_CARD,
          self::PAYMENT_PRODUCT_KLARNA_PAY_NOW,
          self::PAYMENT_PRODUCT_KLARNA_PAY_LATER,
          self::PAYMENT_PRODUCT_TWINTWL,
    ];
    public const PAYMENT_PRODUCT_MEDIA_DIR = 'bundles/moptworldline/static/img';
    private const PAYMENT_PRODUCT_MEDIA_PREFIX = 'pp_logo_';
    public const PAYMENT_PRODUCT_MEDIA_DEFAULT = 'base';
    public const PAYMENT_PRODUCT_NAMES = [
        self::PAYMENT_PRODUCT_KLARNA_PAY_NOW => 'Klarna',
        self::PAYMENT_PRODUCT_KLARNA_PAY_LATER => 'Klarna pay later',
        self::PAYMENT_PRODUCT_ONEY_3X_4X => 'Oney 3x-4x',
        self::PAYMENT_PRODUCT_ONEY_FINANCEMENT_LONG => 'Oney Financement Long',
        self::PAYMENT_PRODUCT_ONEY_BRANDED_GIFT_CARD => 'OneyBrandedGiftCard',
        self::PAYMENT_PRODUCT_TWINTWL => 'TWINTWL',
        5100 => 'Cpay',
        320 => 'Google Pay',
        5402 => 'Mealvouchers',
        771 => 'SEPA Direct Debit',
        56 => 'UPI - UnionPay International',

        // List from Worldline for shortnames
        5405 => 'Alipay',
        2 => 'American Express',
        302 => 'Apple Pay',
        3012 => 'Bancontact',
        5001 => 'Bizum',
        130 => 'Carte Bancaire',
        132 => 'Diners Club',
        809 => 'iDEAL',
        3112 => 'Illicado',
        self::PAYMENT_PRODUCT_INTERSOLVE => 'Intersolve',
        125 => 'JCB',
        117 => 'Maestro',
        3 => 'Mastercard',
        5500 => 'Multibanco',
        840 => 'Paypal',
        1 => 'Visa',
        5404 => 'WeChat Pay',
    ];

    /**
     * @param int $paymentProductId
     * @return array
     */
    public static function getPaymentProductDetails(int $paymentProductId): array
    {
        $title = 'Unknown';
        $logoName = self::PAYMENT_PRODUCT_MEDIA_DEFAULT;
        if (array_key_exists($paymentProductId, self::PAYMENT_PRODUCT_NAMES)) {
            $title = self::PAYMENT_PRODUCT_NAMES[$paymentProductId];
            $logoName = self::PAYMENT_PRODUCT_MEDIA_PREFIX . $paymentProductId;
        }

        return [
            'title' => $title,
            'logo' => \sprintf('%s/%s.svg', self::PAYMENT_PRODUCT_MEDIA_DIR, $logoName),
            'fileName' => $logoName,
        ];
    }


    /**
     * @param $token
     * @param PaymentDetailsResponse $paymentDetailsResponse
     * @return array
     */
    public static function createRedirectPaymentProduct($token, PaymentDetailsResponse $paymentDetailsResponse): array
    {
        $paymentProductId = $paymentDetailsResponse->getPaymentOutput()->getCardPaymentMethodSpecificOutput()->getPaymentProductId();

        // Make masked card number from bin (123456) and last 4 digs (************1234) - 123456******1234
        $bin = $paymentDetailsResponse->getPaymentOutput()->getCardPaymentMethodSpecificOutput()->getCard()->getBin();
        $card = $paymentDetailsResponse->getPaymentOutput()->getCardPaymentMethodSpecificOutput()->getCard()->getCardNumber();
        $paymentCard = substr_replace($card, $bin, 0, strlen($bin));
        return array_merge(
            [
                'paymentProductId' => $paymentProductId,
                'token' => $token,
                'paymentCard' => $paymentCard,
                'default' => false
            ],
            self::getPaymentProductDetails($paymentProductId)
        );
    }


    /**
     * @param GetHostedTokenizationResponse $hostedTokenization
     * @return array
     */
    public static function buildPaymentProduct(GetHostedTokenizationResponse $hostedTokenization): array
    {
        $paymentProductId = $hostedTokenization->getToken()->getPaymentProductId();
        $token = $hostedTokenization->getToken()->getId();
        return [
            $token,
            array_merge(
                [
                    'paymentProductId' => $paymentProductId,
                    'token' => $token,
                    'paymentCard' => $hostedTokenization->getToken()->getCard()->getData()->getCardWithoutCvv()->getCardNumber(),
                    'default' => false,
                    'redirectToken' => false,
                ],
                self::getPaymentProductDetails($paymentProductId)
            )
        ];
    }
}
