<?php declare(strict_types=1);

/**
 * @author Mediaopt GmbH
 * @package MoptWorldline\Service
 */

namespace MoptWorldline\Service;

use Doctrine\DBAL\ParameterType;
use Monolog\Level;
use MoptWorldline\Bootstrap\Form;
use OnlinePayments\Sdk\Domain\GetHostedTokenizationResponse;
use OnlinePayments\Sdk\Domain\PaymentDetailsResponse;
use Shopware\Core\Kernel;

class PaymentProducts
{
    const PAYMENT_PRODUCT_INTERSOLVE = 5700;
    const PAYMENT_PRODUCT_ONEY_3X_4X = 5110;
    const PAYMENT_PRODUCT_ONEY_FINANCEMENT_LONG = 5125;
    const PAYMENT_PRODUCT_ONEY_BRANDED_GIFT_CARD = 5600;
    const PAYMENT_PRODUCT_KLARNA_PAY_NOW = 3301;
    const PAYMENT_PRODUCT_KLARNA_PAY_LATER = 3302;
    const PAYMENT_PRODUCT_TWINTWL = 5407;
    const PAYMENT_PRODUCT_POSTFINANCE = 3203;
    const PAYMENT_PRODUCT_PRZELEWY24 = 3124;
    const PAYMENT_PRODUCT_BANK_TRANSFER = 5408;
    const PAYMENT_PRODUCT_CARTE_BANCAIRE = 130;
    const PAYMENT_PRODUCT_VISA = 1;
    const PAYMENT_PRODUCT_NEED_DETAILS = [
        self::PAYMENT_PRODUCT_ONEY_3X_4X,
        self::PAYMENT_PRODUCT_ONEY_FINANCEMENT_LONG,
        self::PAYMENT_PRODUCT_ONEY_BRANDED_GIFT_CARD,
        self::PAYMENT_PRODUCT_KLARNA_PAY_NOW,
        self::PAYMENT_PRODUCT_KLARNA_PAY_LATER,
        self::PAYMENT_PRODUCT_TWINTWL,
        self::PAYMENT_PRODUCT_CARTE_BANCAIRE,
        self::PAYMENT_PRODUCT_VISA,
        Payment::FULL_REDIRECT_PAYMENT_METHOD_ID,
    ];
    const INTERNAL_PAYMENT_METHODS = [
        Payment::FULL_REDIRECT_PAYMENT_METHOD_ID,
        Payment::SAVED_CARD_PAYMENT_METHOD_ID,
        Payment::IFRAME_PAYMENT_METHOD_ID
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
        self::PAYMENT_PRODUCT_POSTFINANCE => 'Postfinance Pay',
        self::PAYMENT_PRODUCT_PRZELEWY24 => 'Przelewy24',
        self::PAYMENT_PRODUCT_BANK_TRANSFER => 'Bank Transfer by Worldline',
        self::PAYMENT_PRODUCT_CARTE_BANCAIRE => 'Carte Bancaire',
        Payment::FULL_REDIRECT_PAYMENT_METHOD_ID => Payment::FULL_REDIRECT_PAYMENT_METHOD_NAME,
        Payment::SAVED_CARD_PAYMENT_METHOD_ID => Payment::SAVED_CARD_PAYMENT_METHOD_NAME,
        Payment::IFRAME_PAYMENT_METHOD_ID => Payment::IFRAME_PAYMENT_METHOD_NAME,
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
        132 => 'Diners Club',
        809 => 'iDEAL',
        3112 => 'Illicado',
        self::PAYMENT_PRODUCT_INTERSOLVE => 'Intersolve',
        125 => 'JCB',
        117 => 'Maestro',
        3 => 'Mastercard',
        5500 => 'Multibanco',
        840 => 'Paypal',
        self::PAYMENT_PRODUCT_VISA => 'Visa',
        5404 => 'WeChat Pay',
    ];

    public const PAYMENT_PRODUCT_PNG_LOGO = [
        self::PAYMENT_PRODUCT_TWINTWL,
        self::PAYMENT_PRODUCT_POSTFINANCE,
        self::PAYMENT_PRODUCT_PRZELEWY24,
        self::PAYMENT_PRODUCT_BANK_TRANSFER,
    ];

    public const PAYMENT_PRODUCT_RULES = [
        self::PAYMENT_PRODUCT_POSTFINANCE => ['EUR', 'CHF'],
        self::PAYMENT_PRODUCT_TWINTWL => ['CHF'],
        self::PAYMENT_PRODUCT_PRZELEWY24 => ['PLN'],
    ];

    /**
     * @param int $paymentProductId
     * @return array
     */
    public static function getPaymentProductDetails(int $paymentProductId): array
    {
        $title = 'Unknown';
        $logoName = self::PAYMENT_PRODUCT_MEDIA_DEFAULT;
        $format = '%s/%s.svg';
        if (in_array($paymentProductId, self::PAYMENT_PRODUCT_PNG_LOGO)) {
            $format = '%s/%s.png';
        }
        if (array_key_exists($paymentProductId, self::PAYMENT_PRODUCT_NAMES)) {
            $title = self::PAYMENT_PRODUCT_NAMES[$paymentProductId];
            $logoName = self::PAYMENT_PRODUCT_MEDIA_PREFIX . $paymentProductId;
        }

        return [
            'title' => $title,
            'logo' => \sprintf($format, self::PAYMENT_PRODUCT_MEDIA_DIR, $logoName),
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

    /**
     * @param string $hostedCheckoutId
     * @return string
     */
    public static function getPaymentProductIdByTransactionId(string $hostedCheckoutId): string
    {
        $connection = Kernel::getConnection();
        $qb = $connection->createQueryBuilder();
        $hostedCheckoutId = (int) $hostedCheckoutId;
        $qb->select('DISTINCT pmt.custom_fields')
            ->from('`order`', 'o')
            ->leftJoin('o', 'order_transaction', 'ot', 'o.id = ot.order_id')
            ->leftJoin('ot', 'payment_method_translation', 'pmt', 'pmt.payment_method_id= ot.payment_method_id')
            ->where("o.custom_fields like '%payment_transaction_id\"\: \"$hostedCheckoutId\"%'");

        $result = '';
        try {
            $result = $qb->fetchOne();
            $parsed = json_decode($result, true);
            $result = (string) $parsed[Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_METHOD_ID];
        } catch (\Exception $e) {
            LogHelper::addLog(Level::Error, "The order with hostedCheckoutId $hostedCheckoutId could not be found.");
        }

        return $result;
    }
}
