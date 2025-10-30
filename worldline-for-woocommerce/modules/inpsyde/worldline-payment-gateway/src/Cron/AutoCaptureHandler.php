<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Cron;

use Automattic\WooCommerce\Utilities\OrderUtil;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Config\CaptureMode;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\GatewayIds;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\OrderMetaKeys;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\WlopWcOrder;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CapturePaymentRequest;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\MerchantClientInterface;
use WC_Order;
class AutoCaptureHandler
{
    protected string $captureMode;
    protected MerchantClientInterface $apiClient;
    public function __construct(string $captureMode, MerchantClientInterface $apiClient)
    {
        $this->captureMode = $captureMode;
        $this->apiClient = $apiClient;
    }
    public function execute() : void
    {
        $wcOrders = $this->queryCapturableOrders();
        if (empty($wcOrders)) {
            return;
        }
        foreach ($wcOrders as $wcOrder) {
            if (!$this->captureDateTimeReached($wcOrder)) {
                continue;
            }
            try {
                $this->capture($wcOrder);
            } catch (Exception $exception) {
                \do_action('wlop.auto_capture_failed', ['wcOrderId' => $wcOrder->get_id(), 'exception' => $exception]);
            }
        }
    }
    protected function captureDateTimeReached(WC_Order $wcOrder) : bool
    {
        $captureDateTime = $this->expectedCaptureDateTime($wcOrder);
        $now = new DateTime('now');
        return $now >= $captureDateTime;
    }
    protected function orderDateTime(WC_Order $wcOrder) : ?DateTimeImmutable
    {
        $timestamp = $wcOrder->get_meta(OrderMetaKeys::CREATION_TIME);
        if (!$timestamp) {
            $wcOrderDateTime = $wcOrder->get_date_created();
            if (!$wcOrderDateTime instanceof DateTime) {
                return null;
            }
            return DateTimeImmutable::createFromMutable($wcOrderDateTime);
        }
        return (new DateTimeImmutable("@{$timestamp}"))->setTimezone(\wp_timezone());
    }
    protected function expectedCaptureDateTime(WC_Order $wcOrder) : DateTimeImmutable
    {
        $orderDate = $this->orderDateTime($wcOrder);
        if (!$orderDate) {
            throw new Exception('Date/time not found.');
        }
        switch ($this->captureMode) {
            case CaptureMode::END_OF_DAY:
                return $orderDate->setTime(23, 59, 59);
            case CaptureMode::AFTER_1D:
            case CaptureMode::AFTER_2D:
            case CaptureMode::AFTER_3D:
            case CaptureMode::AFTER_4D:
            case CaptureMode::AFTER_5D:
            case CaptureMode::AFTER_6D:
            case CaptureMode::AFTER_7D:
                return $orderDate->add(new DateInterval('P' . \strtoupper($this->captureMode)));
        }
        return $orderDate->add(new DateInterval('P999Y'));
    }
    protected function capture(WC_Order $wcOrder) : void
    {
        $wlopWcOrder = new WlopWcOrder($wcOrder);
        $transactionId = (string) $wcOrder->get_meta(OrderMetaKeys::TRANSACTION_ID);
        $paymentDetails = $this->apiClient->payments()->getPaymentDetails($transactionId);
        $captureAmount = $paymentDetails->getPaymentOutput()->getAcquiredAmount();
        $capturePaymentRequest = new CapturePaymentRequest();
        $capturePaymentRequest->setAmount($captureAmount->getAmount());
        $this->apiClient->payments()->capturePayment($transactionId, $capturePaymentRequest);
        $wcOrder->update_meta_data(OrderMetaKeys::AUTO_CAPTURE_SENT, 'yes');
        $wlopWcOrder->addWorldlineOrderNote(\__('Automatic fund capture request is submitted. You will receive a notification in the order notes upon completion.', 'worldline-for-woocommerce'));
        $wcOrder->save();
    }
    /**
     * @return WC_Order[]
     * phpcs:disable Inpsyde.CodeQuality.NestingLevel.High
     */
    protected function queryCapturableOrders() : array
    {
        $query = ['status' => ['wc-on-hold'], 'payment_method' => GatewayIds::ALL, 'orderby' => 'date', 'limit' => '20'];
        $metaQuery = [['key' => OrderMetaKeys::MANUAL_CAPTURE_SENT, 'compare' => 'NOT EXISTS'], ['key' => OrderMetaKeys::AUTO_CAPTURE_SENT, 'compare' => 'NOT EXISTS'], ['key' => OrderMetaKeys::TRANSACTION_STATUS_CODE, 'compare' => 'IN', 'value' => [5, 56]]];
        if (OrderUtil::custom_orders_table_usage_is_enabled()) {
            $query['meta_query'] = $metaQuery;
        } else {
            // phpcs:ignore Inpsyde.CodeQuality.NoElse.ElseFound
            $query['wlop_meta_query'] = $metaQuery;
            \add_filter(
                'woocommerce_order_data_store_cpt_get_orders_query',
                /**
                 * @param array $query - Args for WP_Query.
                 * @param array $queryVars - Query vars from WC_Order_Query.
                 * @return array modified $query
                 * @psalm-suppress MixedArgument
                 */
                static function ($query, $queryVars) {
                    if (!empty($queryVars['wlop_meta_query'])) {
                        $query['meta_query'] = \array_merge($query['meta_query'], $queryVars['wlop_meta_query']);
                    }
                    return $query;
                },
                10,
                2
            );
        }
        $result = \wc_get_orders($query);
        \assert(\is_array($result));
        return $result;
    }
}
