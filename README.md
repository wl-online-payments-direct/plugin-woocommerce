# Worldline Payments for WooCommerce

Worldline payments for WooCommerce allows store owners to securely accept and process payments through Worldline’s payment solutions. Our plug-in comes with regular updates and full integration support, guaranteeing a versatile out-of-the-box solution to accept online payments easily. The plugin ensures secure transactions by utilizing advanced encryption and security protocols, providing both store owners and customers peace of mind when it comes to payment security.

## Features

- Incredible reach of local and global payment methods
- Authorization/Sale modes
- Card tokenization
- PSD2 Compliant
- PCI-DSS Compliant
- Maintenance transactions within WooCommerce cockpit

## Effortless Integration

Our plugin is crafted to integrate seamlessly into your WooCommerce store without hassle. With easy installation and setup, you'll be ready to accept a wide array of payment methods including credit cards, debit cards, and alternative payments in no time.

# Installation

## Requirements

To install and configure Worldline Payments for WooCommerce, you will need:

- WordPress Version 6.3 or newer (installed)
- WooCommerce Version 8.6 or newer (installed and activated)
- PHP Version 7.4 or newer
- Worldline account

## Installation instructions

1. Log in to WordPress admin.
2. Go to **Plugins > Add New**.
3. Search for the **Worldline Payments for WooCommerce** plugin.
4. Click on **Install Now** and wait until the plugin is installed successfully.
5. You can activate the plugin immediately by clicking on **Activate** now on the success page. If you want to activate it later, you can do so via **Plugins > Installed Plugins**.

## Setup and Configuration

Follow the steps below to connect the plugin to your Worldline account:

1. After you have activated the Worldline Payments for WooCommerce plugin, go to **WooCommerce  > Settings**.
2. Click the **Payments** tab.
3. Click on **Worldline**.
4. Enter the details for PSPID, Live API Key and Test API Secret from your Worldline account page under "Developer" > "Payment API" page.
5. Click on "Save" to store these settings in the plugin.
6. Copy the "Webhook endpoint".
7. Add the webhook endpoint in the Worldline back office page "Developer" > "Webhooks" by clicking "add webhook endpoint".
8. Generate the webhook keys on the back office page and copy the details into the plugin settings page into the fields "Webhook ID" and "Secret Webhook key".
9. Click on "Save" to store these settings in the plugin.

## Updating

Automatic updates should work generally smoothly, but we still recommend you back up your site.

If you encounter issues with the Worldline buttons not appearing after an update, purge your website cache.
