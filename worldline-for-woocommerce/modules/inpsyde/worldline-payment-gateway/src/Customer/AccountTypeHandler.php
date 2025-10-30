<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Customer;

use Exception;
class AccountTypeHandler
{
    /** @var int Time limit (in minutes) for a customer account to be considered 'created'. */
    protected const CREATED_CUSTOMER_TIME_LIMIT = 5;
    /**
     * @throws Exception
     */
    public function determineAccountType(\WC_Order $wcOrder) : string
    {
        $orderCustomerId = $wcOrder->get_customer_id();
        if (!$orderCustomerId) {
            return 'none';
        }
        $customer = new \WC_Customer($orderCustomerId);
        $customerCreationDate = $customer->get_date_created();
        if ($customerCreationDate) {
            $customerCreationDate->setTimezone(new \DateTimeZone(\wc_timezone_string()));
            $dateTimeNow = new \WC_DateTime();
            $dateTimeNow->setTimezone(new \DateTimeZone(\wc_timezone_string()));
            $timeDifferenceInSeconds = $dateTimeNow->getTimestamp() - $customerCreationDate->getTimestamp();
            if ($timeDifferenceInSeconds <= self::CREATED_CUSTOMER_TIME_LIMIT * 60 && $customer->get_order_count() === 1) {
                return 'created';
            }
        }
        return 'existing';
    }
}
