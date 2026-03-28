# Premium Black Payment for WooCommerce

WordPress/WooCommerce plugin to accept cryptocurrency payments with [Premium Black](https://premium.black).

## Features

- **Cryptocurrency payments** – Accept payments in multiple cryptocurrencies directly at checkout
- **Two-step checkout UI** – Blockchain → currency selection with search and filtering
- **WooCommerce Blocks support** – Full compatibility with the block-based checkout
- **Guided onboarding wizard** – Step-by-step setup with progress tracking
- **Secure API key management** – Private keys stored securely, never exposed to the frontend
- **Real-time transaction status** – Badges and status updates for pending, confirmed, error, and partial payment states
- **Partial payment handling** – Displays received and remaining amounts when a partial payment is detected
- **QR code support** – QR codes on the order/thank-you page for easy payment
- **Merchant Dashboard links** – Quick access to the Premium Black Merchant Dashboard from the settings page
- **REST API webhook endpoint** – Receives payment status callbacks from the Premium Black platform
- **Internationalization (i18n)** – Translation-ready with included `.pot` file and Spanish translations
- **Modern admin settings UI** – Card-based layout with toggle controls, currency grid grouped by blockchain, and responsive design
- **Customizable checkout design** – Dedicated CSS for checkout, payment, admin, and onboarding

## Requirements

- WordPress 6.0 or later
- WooCommerce (tested up to 10.6.1)
- PHP 7.4 or later
- A [Premium Black](https://premium.black) merchant account with API credentials

## Installation

1. Upload the plugin folder to the `wp-content/plugins/` directory, or install via the WordPress plugins screen.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Complete the onboarding wizard that appears after activation, or go to **WooCommerce > Settings > Payments** and configure the "Premium Black" gateway.
4. Enter your API credentials (public and private key) from your Premium Black account.
5. Select the cryptocurrencies and blockchains you want to accept.

## Configuration

- Navigate to the **Premium Black** settings page in the WordPress admin
- Enter your public and private API keys (obtainable from the [Merchant Dashboard](https://dash.premium.black))
- Enable or disable the gateway, debug mode, and transaction status features via toggle controls
- Review and manage accepted currencies grouped by blockchain

## Usage

- Customers select **Premium Black** as their payment method during checkout
- They choose a blockchain and then a specific cryptocurrency through the two-step picker
- Payments are processed via the Premium Black API with real-time status updates
- The order/thank-you page shows transaction details, status badges, and a QR code

## Project Structure

```
src/premium-black-payment-for-woocommerce/
├── premium-black-payment-for-woocommerce.php   # Main plugin file
├── class-wc-gateway-premium-black.php           # Gateway implementation
├── class-premium-black-rest-endpoint.php        # REST API webhook endpoint
├── class-wc-block.php                           # WooCommerce Blocks integration
├── onboarding.php                               # Onboarding wizard
├── settings.php                                 # Admin settings page
├── readme.txt                                   # WordPress plugin directory readme
├── assets/
│   ├── checkout.js                              # Checkout block JS (React)
│   ├── payment.css                              # Payment/checkout styles
│   ├── admin.css                                # Admin settings styles
│   ├── onboarding.css                           # Onboarding wizard styles
│   ├── onboarding.js                            # Onboarding wizard JS
│   └── premiumblack.png                         # Plugin icon
├── languages/                                   # Translation files (.pot, .po, .mo)
└── libs/                                        # API request classes
```

## Development

### Generate .pot file

```bash
wp i18n make-pot . languages/premium-black-payment-for-woocommerce.pot --allow-root
```

### Naming Convention

All PHP function names, option keys, and handles use the `premblpa_` prefix (e.g., `Premblpa_WC_Gateway`, `premblpa_settings`) to avoid collisions with other plugins.

## External Services

This plugin connects to the **Premium Black API** (`https://premium.black/service/rest/Pay.svc`) to process and verify cryptocurrency payments. Data is only sent when a customer initiates a payment or when the plugin checks/updates a payment status via webhook.

- **Service provider:** [Premium Black Ltd.](https://premium.black)
- [Terms of Service](https://premium.black/terms/)
- [Privacy Policy](https://premium.black/privacy/)

## Support

For questions or issues, please [open an issue](https://github.com/PREMIUM-BLACK/premium-black-payment-for-woocommerce/issues) in this repository or send an email to [contact@premium.black](mailto:contact@premium.black).

## More Information

For more details, visit the [Premium Black website](https://premium.black).

## License

This plugin is licensed under the [GPLv2 or later](https://www.gnu.org/licenses/gpl-2.0.html).
