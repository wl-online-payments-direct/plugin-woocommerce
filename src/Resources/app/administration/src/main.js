import './service/apiTestService';
import './service/transactionsControlService';
import './service/supportFormService';

import './component/api-test-button';
import './component/payment-method-button';
import './component/orders-unprocessed';
import './component/orders-paid';
import './component/orders-refunded';
import './component/orders-canceled';
import './component/config-tabs';
import './component/order-buttons'
import './component/support-form'

import './extension/sw-order/view/sw-order-detail-details';
import './extension/sw-settings-payment/page/sw-settings-payment-detail';

import localeDE from '../../../snippet/storefront/worldline.de-DE.json';
import localeEN from '../../../snippet/storefront/worldline.en-GB.json';

Shopware.Locale.extend('de-DE', localeDE);
Shopware.Locale.extend('en-GB', localeEN);
