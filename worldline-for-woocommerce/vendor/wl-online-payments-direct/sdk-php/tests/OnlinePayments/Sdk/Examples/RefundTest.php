<?php

namespace Syde\Vendor\Worldline\OnlinePayments\Sdk\Examples;

use Exception;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\ClientTestCase;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\AmountOfMoney;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\RefundRequest;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\RefundResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\ApiException;

/**
 * @group examples
 *
 */
class RefundTest extends ClientTestCase
{
    /**
     * @return string
     * @return string
     * @throws ApiException*@throws \Exception
     * @throws Exception
     */
    public function testCreateRefund()
    {
        $this->markTestSkipped('No refundable payment is available at this time');

        $client = $this->getClient();
        $merchantId = $this->getMerchantId();
        $paymentId = "1_1";

        $refundRequest = new RefundRequest();

        $amountOfMoney = new AmountOfMoney();
        $amountOfMoney->setCurrencyCode("EUR");
        $amountOfMoney->setAmount(1);
        $refundRequest->setAmountOfMoney($amountOfMoney);

        /** @var RefundResponse $refundResponse * */
        $refundResponse = $client->merchant($merchantId)->payments()->refundPayment($paymentId, $refundRequest);
        return $refundResponse->getId();
    }
}
