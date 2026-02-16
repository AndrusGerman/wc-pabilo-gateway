=== Pabilo Payment Gateway for WooCommerce ===
Contributors: Pabilo
Donate link: https://pabilo.app
Tags: woocommerce, payment gateway, venezuela, bank transfer, pago movil
Requires at least: 5.0
Tested up to: 6.9
Stable tag: 1.0.4
Requires PHP: 7.4
Requires Plugins: woocommerce
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Accept mobile payments (Pago Móvil) and bank transfers from Venezuela (Banco de Venezuela, Mercantil, Banesco, Provincial) via Pabilo.

== Description ==

Pabilo Payment Gateway lets WooCommerce stores accept payments from major Venezuelan banks through a simple, verified flow.

This plugin connects your store to the [Pabilo](https://pabilo.app) payment service. When you configure your API key, you consent to sending order data (amount, description, order ID) to Pabilo to generate payment links and verify transactions. See [Pabilo Terms of Use](https://pabilo.app/terms) for data handling details.

Configure your API key, choose the bank account to receive funds, and start accepting:

* **Pago Móvil** (mobile payments)
* **Bank transfers**

Supported banks include Banco de Venezuela, Mercantil, Banesco, Provincial, and other banks supported by Pabilo.

The plugin creates a secure payment link for each order and verifies payment automatically via webhooks (with API verification), for a smooth checkout experience.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/pabilo-payment-gateway-for-woocommerce`, or install via the Plugins screen in WordPress.
2. Activate the plugin from the Plugins screen.
3. Go to WooCommerce > Settings > Payments > Pabilo Payment Gateway to configure.
4. Enter your **API Key** from your Pabilo dashboard.
5. Select the bank account where you want to receive funds.

== Frequently Asked Questions ==

= Do I need a Pabilo account? =

Yes. Sign up at [pabilo.app](https://pabilo.app) to get your API credentials.

= Which banks are supported? =

The plugin supports transfers and Pago Móvil from major Venezuelan banks, including Banco de Venezuela, Mercantil, Banesco, and Provincial.

== Screenshots ==

1. Plugin settings in WooCommerce.
2. Payment option at checkout.

== Changelog ==

= 1.0.4 =
* Feature: Support for WooCommerce Blocks Checkout (new checkout experience).
* Security: Enhanced webhook verification (Payment Link ID check).
* Security: Validate payment link ownership against store admin account.
* Fix: Prevented fatal error in Blocks checkout during payment processing.

= 1.0.3 =
* Verify webhook with Pabilo API before marking order paid (security).
* WordPress.org compatibility: Requires Plugins, Requires PHP, Tested up to 6.9.
* Privacy and Terms of Use documentation.

= 1.0.2 =
* Full Spanish translation.
* Pago Móvil and bank transfer messaging.

= 1.0.1 =
* Description and icon improvements.
* Webhook verification logic updated.

= 1.0.0 =
* Initial release.
