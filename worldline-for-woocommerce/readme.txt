=== Worldline Payments for WooCommerce ===
Contributors: woocommerce, automattic, syde
Tags: woocommerce, Worldline, payments, ecommerce
Requires at least: 6.3
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Worldline's latest payment processing solution. 

== Description ==

Worldline payments for WooCommerce allows store owners to securely accept and process payments through Worldline’s payment solutions. Our plug-in comes with regular updates and full integration support, guaranteeing a versatile out-of-the-box solution to accept online payments easily. The plugin ensures secure transactions by utilizing advanced encryption and security protocols, providing both store owners and customers peace of mind when it comes to payment security. 

**Features**

* Incredible reach of local and global payment methods
* Authorization/Sale modes
* Card tokenization
* PSD2 Compliant
* PCI-DSS Compliant
* Maintenance transactions within WooCommerce cockpit

**Effortless Integration**

Our plugin is crafted to integrate seamlessly into your WooCommerce store without hassle. With easy installation and setup, you'll be ready to accept a wide array of payment methods including credit cards, debit cards, and alternative payments in no time.

== Frequently Asked Questions ==

= Where can I find the WooCommerce Worldline Payments documentation and setup guide? =

For help setting up and configuring Worldline Payments for WooCommerce, please refer to the documentation. [tbc](https://tbc)

= Where can I get help for Worldline Payments for WooCommerce? =

For questions regarding the plugin setup, we recommend reviewing our [documentation](https://woocommerce.com/document/tbc) if you encounter any issues.
If the question or problem persists after reviewing the documentation, kindly create a new thread in the [support forums](https://wordpress.org/support/plugin/tbc/#new-topic-0) or open a support ticket via [our helpdesk](https://woocommerce.com/document/tbc/#get-help).

== Installation ==

= Requirements =

To install and configure Worldline Payments for WooCommerce, you will need:

* WordPress Version 6.3 or newer (installed)
* WooCommerce Version 8.6 or newer (installed and activated)
* PHP Version 7.4 or newer
* Worldline account

= Installation instructions =

1. Log in to WordPress admin.
2. Go to **Plugins > Add New**.
3. Search for the **Worldline Payments for WooCommerce** plugin.
4. Click on **Install Now** and wait until the plugin is installed successfully.
5. You can activate the plugin immediately by clicking on **Activate** now on the success page. If you want to activate it later, you can do so via **Plugins > Installed Plugins**.

= Setup and Configuration =

Follow the steps below to connect the plugin to your Worldline account:

1. After you have activated the Worldline Payments for WooCommerce plugin, go to **WooCommerce  > Settings**.
2. Click the **Payments** tab.
3. Click on **Worldline**.
4. Enter the details for PSPID, Live API Key and Test API Secret from your Worldline account page under "Developer" > "Payment API" page.
5. Click on "Save" to store these settings in the plugin. 
6. Copy the "Webhook endpoint".
6. Add the webhook endpoint in the Worldline back office page "Developer" > "Webhooks" by clicking "add webhook endpoint".
7. Generate the webhook keys on the back office page and copy the details into the plugin settings page into the fields "Webhook ID" and "Secret Webhook key".
8. Click on "Save" to store these settings in the plugin. 

Complete onboarding instructions can be found in the [documentation here](https://woocommerce.com/document/tbc).

= Updating =

Automatic updates should work generally smoothly, but we still recommend you back up your site.

If you encounter issues with the Worldline buttons not appearing after an update, purge your website cache.

== Screenshots ==

1. tbc by Worldline

== Changelog ==

= 1.0.1 - 2024-10-02 =
* Allow to set test and live webhook credentials separately.
* Improve refunding, mark refunded items.
* Fixed Payment Method title.

= 1.0.0 - 2024-08-01 =
* Initial release.
