=== Premium Black Payment for WooCommerce ===
Contributors: premiumblack
Tags: woocommerce, crypto, payment, gateway, blockchain
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 1.1.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==
Premium Black Payment for WooCommerce enables your WooCommerce shop to accept cryptocurrency payments via the Premium Black platform. This plugin provides a seamless integration, allowing your customers to pay with a variety of cryptocurrencies directly at checkout.

== Features ==
* Accept payments in multiple cryptocurrencies
* Easy onboarding and configuration
* Secure API key management
* WooCommerce Blocks support
* Real-time transaction confirmation and status updates

== Installation ==
1. Upload the plugin files to the `/wp-content/plugins/premium-black-plugin` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to WooCommerce > Settings > Payments and configure the "Premium Black" payment gateway.
4. Enter your API credentials (public and private key) provided by Premium Black.
5. Select the cryptocurrencies you want to accept.

== Usage ==
Once configured, your customers will see the "Premium Black" payment option at checkout. They can select their preferred cryptocurrency and complete the payment securely.

== Frequently Asked Questions ==
= What cryptocurrencies are supported? =
Supported currencies depend on your Premium Black account and configuration.

= Is this plugin secure? =
Yes, all sensitive data is handled securely and API keys are never exposed to the frontend.

== External services ==
This plugin connects to the Premium Black API (https://premium.black/service/rest/Pay.svc) to process and verify cryptocurrency payments, and to transmit payment status and transaction details between your WooCommerce shop and the Premium Black platform.

**What data is sent and when?**
- When a customer selects Premium Black as payment method and places an order, the following data is sent to the Premium Black API: order amount, selected cryptocurrency, blockchain, WooCommerce order ID, customer email, and other transaction-related information required to process the payment.
- During payment status checks (e.g. webhook callbacks), the plugin transmits the transaction ID and key to verify and update the payment status.

**Why is this data sent?**
- The data is required to create, process, and verify cryptocurrency payments, and to keep the order status in sync with the payment status on the Premium Black platform.

**Where is the data sent?**
- All data is sent securely to the Premium Black API endpoint: https://premium.black/service/rest/Pay.svc

**Under which conditions?**
- Data is only sent when a customer initiates a payment using the Premium Black gateway, or when the plugin checks or updates the payment status for an order.

**Service provider:**
- Premium Black Ltd. (https://premium.black)
- [Terms of Service](https://premium.black/terms/)
- [Privacy Policy](https://premium.black/privacy/)

No other external services are used by this plugin.

= Where can I get support? =
For support, visit https://premium.black or the plugin's GitHub repository.

== Changelog ==
= 1.1.1 =
* Initial public release

== License ==
This plugin is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

This plugin is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this plugin. If not, see <https://www.gnu.org/licenses/gpl-2.0.html>.