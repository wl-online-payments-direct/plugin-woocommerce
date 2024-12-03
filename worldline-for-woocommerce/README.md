# Worldline Payments for WooCommerce

Worldline payment gateway for WooCommerce

## Table Of Contents

* [Installation](#installation)
* [Crafted by Syde](#crafted-by-syde)
* [Contributing](#contributing)

## Installation

The best way to install this plugin is download the latest build in the Releases section as zip archive.
Then, install it from WordPress admin.

## Development

You can use the DDEV setup provided in this repository which includes WP, WC and all developments tools.

To set up the DDEV environment, follow these steps:

0. Install Docker and [DDEV](https://ddev.readthedocs.io/en/stable/).
1. Edit the configuration in the [`.ddev/config.yml`](.ddev/config.yaml) file if needed.
2. `ddev start`
3. `ddev orchestrate` to install WP/WC.
4. Open <https://worldline-for-woocommerce.ddev.site> to check that it works.

Use `ddev orchestrate -f` for reinstalattion (will destroy all site data).
You may also need `ddev restart` to apply the config changes.

### Webhooks

For testing webhooks locally, install and set up [ngrok](https://ngrok.com/), run `vendor/bin/ddev-share`

If you want to use the same domain every time instead of the random domain generated on each ngrok execution,
create it in your ngrok account (one domain is [available in the free tier](https://ngrok.com/blog-post/free-static-domains-ngrok-users)) and create `.ddev/config.local.yaml` with content like this:

```yml
ngrok_args: --domain my-domain.ngrok-free.app
```

### Running tests and other tasks

* `ddev yarn lint:js`
* `ddev yarn lint:style`
* `ddev yarn lint:php`
* `ddev yarn lint:php-fix` - PHPCBF to fix basic code style issues
* `ddev yarn test` - run PHPUnit tests
* `ddev yarn assets` - build JS/CSS assets (including external modules)
* `ddev yarn watch` - automatically rebuilds JS/CSS assets in local modules when source code changes

See [package.json](/package.json) for other useful commands.

For debugging, see [the DDEV docs](https://ddev.readthedocs.io/en/stable/users/step-debugging/).
Enable xdebug via `ddev xdebug`, and press `Start Listening for PHP Debug Connections` in PHPStorm.
After creating the server in the PHPStorm dialog, you need to set the local project path for the server plugin path.
It should look [like this](https://github.com/inpsyde/worldline-for-woocommerce/assets/5680466/8afa8262-d2b7-42f4-93c2-1b37b5ba09a0).
To turn off debugging, use `ddev xdebug off` (always keeping it on may be inconvenient because of slower performance).

## Configuration & Merchant onboarding

* Go to [the signup page](https://signup.direct.preprod.worldline-solutions.com/) and apply for a test account. Note that the company name you set here serves as the PSPID during onboarding
* Click the link in the verification email and proceed to set a password
* You will be required to set up a 2FA with an authenticator app. (Note: FreeOTP+ from F-Droid works just fine, or password managers like [1Password](https://support.1password.com/one-time-passwords/))
* Create new API credentials here: https://merchant-portal.preprod.worldline-solutions.com/developer/payment-api
* Go to [the plugin settings in WooCommerce](https://worldline-for-woocommerce.ddev.site/wp-admin/admin.php?page=wc-settings&tab=checkout&section=worldline-for-woocommerce) and fill in the credentials
* Remember: Your PSPID is the company name you used + possibly a generated number suffix. You will find it on the right-hand side at the top of the merchant portal


## Crafted by Syde

The team at [Syde](https://Syde.com) is engineering the Web since 2006.

## Contributing

All feedback / bug reports / pull requests are welcome.