=== Worldline Global Online Pay for WooCommerce ===
Contributors: worldlineisv
Tags: woocommerce, Worldline, payments, ecommerce
Requires at least: 6.3
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 2.4.6
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Worldline's latest payment processing solution.

== Description ==

Worldline Global Online Pay for WooCommerce allows store owners to securely accept and process payments through Worldline’s payment solutions. Our plug-in comes with regular updates and full integration support, guaranteeing a versatile out-of-the-box solution to accept online payments easily. The plugin ensures secure transactions by utilizing advanced encryption and security protocols, providing both store owners and customers peace of mind when it comes to payment security.

**Features**

* Supports all major global and local payment methods
* Authorization/Sale mode for transactions
* Onsite payments for increased conversion
* Advanced 3DS options
* PSD2 and PCI-DSS Compliant
* Possibility of custom branding on the payment pages
* Maintenance transactions within WooCommerce cockpit

**Effortless Integration**

Our plugin is crafted to integrate seamlessly into your WooCommerce store without hassle. With easy installation and setup, you’ll be ready to accept a wide array of payment methods including credit cards, debit cards, and alternative payments in no time.

== Frequently Asked Questions ==

= Where can I find documentation for the plugin setup? =

For help setting up and configuring the plugin, please refer to the [documentation](https://docs.direct.worldline-solutions.com/en/integration/how-to-integrate/plugins/woocommerce).

= Where can I get help for Worldline Global Online Pay for WooCommerce? =

For questions regarding the plugin setup, we recommend reviewing our [documentation](https://docs.direct.worldline-solutions.com/en/integration/how-to-integrate/plugins/woocommerce) if you encounter any issues.
If the question or problem persists after reviewing the documentation, kindly create a new thread in the [support forums](https://wordpress.org/support/plugin/worldline-for-woocommerce/) or email to us at [isvpartners@worldline.com](mailto:isvpartners@worldline.com).

== Installation ==

= Requirements =

To install and configure Worldline Global Online Pay for WooCommerce, you will need:

* WordPress Version 6.3 or newer (installed)
* WooCommerce Version 8.6 or newer (installed and activated)
* PHP Version 7.4 or newer
* Worldline account

= Installation instructions =

1. Log in to WordPress admin.
2. Go to **Plugins > Add New**.
3. Search for the **Worldline Global Online Pay for WooCommerce** plugin.
4. Click on **Install Now** and wait until the plugin is installed successfully.
5. You can activate the plugin immediately by clicking on **Activate** now on the success page. If you want to activate it later, you can do so via **Plugins > Installed Plugins**.

= Setup and Configuration =

Follow the steps below to connect the plugin to your Worldline account:

1. After you have activated the Worldline Global Online Pay for WooCommerce plugin, go to **WooCommerce  > Settings**.
2. Click the **Payments** tab.
3. Click on **Worldline Global Online Pay for WooCommerce**.
4. Enter the details for PSPID, API Key and Secret (live/test depending on the environment) from your Worldline Merchant Portal (Developer>Payment API).
5. Click on "Save" to store these settings in the plugin.
6. Copy the "Webhook endpoint".
6. Add the webhook endpoint in the Worldline back office page "Developer" > "Webhooks" by clicking "add webhook endpoint".
7. Generate the webhook keys on the back office page and copy the details into the plugin settings page into the fields "Webhook ID" and "Secret Webhook key".
8. Click on "Save" to store these settings in the plugin.

Complete onboarding instructions can be found in the [documentation here](https://docs.direct.worldline-solutions.com/en/integration/how-to-integrate/plugins/woocommerce).

= Updating =

Automatic updates should work generally smoothly, but we still recommend you back up your site.

If you encounter issues with the Worldline buttons not appearing after an update, purge your website cache.

== Screenshots ==

1. tbc by Worldline

== Changelog ==

= 2.4.6 - 2025-10-29 =
* Change surcharge settings title
* Add pending order cancellation cron job logic
* Add upload logo for hosted payment to plugin settings page
* Change author URI and contributor

= 2.4.5 - 2025-10-13 =
* Add missing 3DS parameters for Credit Card payments
* Fix storing the wrong API key in the database

= 2.4.4 - 2025-09-24 =
* Change webhook URL to inpsyde/woocommerce-for-cawl
* Remove "Global Online Pay" when using CAWL

= 2.4.3 - 2025-09-23 =
* Fix Apple pay issue

= 2.4.2 - 2025-09-19 =
* Fix plugin configuration page
* Fix translation issue
* Change plugin title to Offre e-commerce de CAWL

= 2.4.1 - 2025-09-17 =
* Fix fatal error issue

= 2.4.0 - 2025-08-11 =
* Add PayPal payment method

= 2.3.0 - 2025-07-29 =
* Add Mealvouchers payment method
* Add CVCO payment method
* Add EPS payment method

= 2.2.0 - 2025-04-29 =
* Allow SCA exemptions with Transaction Risk Analysis.
* Show totals with Surcharge on the checkout page.
* Add payment method logos on checkout.
* Improve settings tooltips.

= 2.1.0 - 2025-03-31 =
* Added single payment methods (Klarna, PostFinance, Twint).
* Allow to capture payments automatically after the specified time.
* Improve UI in WooCommerce 9.6+.
* Show saved cards on the Pay for Order page.
* Handle saved cards in Hosted Tokenization.
* Add payment method icons in checkout.
* Fix handling of orders that had multiple payment attempts.
* Enable 3DS by default.

= 2.0.0 - 2025-03-10 =
* Added Hosted Tokenization (credit cards) payment method.
* Added single payment methods (ApplePay, BankTransfer, GooglePay, iDeal).
* 3DS improvements (Frictionless Flow, Exempt Flow & Challenge Flow).
* Allow to specify templates of hosted tokenization and hosted checkout pages.
* Allow to change the payment method title.
* Allow to disable submission of the cart data.

= 1.0.1 - 2024-10-02 =
* Allow to set test and live webhook credentials separately.
* Improve refunding, mark refunded items.
* Fix payment method title.

= 1.0.0 - 2024-08-01 =
* Initial release.
