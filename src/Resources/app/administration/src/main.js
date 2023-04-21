import './service/apiTestService';
import './service/transactionsControlService';

import './component/api-test-button';
import './component/payment-method-button';
import './component/orders-unprocessed';
import './component/orders-paid';
import './component/orders-refunded';
import './component/orders-canceled';

import './extension/sw-order/view/sw-order-detail-base';

import localeDE from '../../../snippet/storefront/worldline.de-DE.json';
import localeEN from '../../../snippet/storefront/worldline.en-GB.json';

Shopware.Locale.extend('de-DE', localeDE);
Shopware.Locale.extend('en-GB', localeEN);
