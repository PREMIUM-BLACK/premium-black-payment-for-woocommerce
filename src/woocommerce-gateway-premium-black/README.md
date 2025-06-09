# WooCommerce Gateway Premium Black

WooCommerce Payment Gateway for Premium Black.

## Features
- Integration of the Premium Black payment provider into WooCommerce
- Support for credit cards and other payment methods (depending on provider)
- Easy configuration in the WooCommerce backend
- Support for WooCommerce Blocks (if applicable)
- REST API endpoints for payment processing (if available)
- Customizable checkout design

## Installation

1. Upload the plugin folder to the `wp-content/plugins/` directory.
2. Activate the plugin via the __Plugins__ menu in WordPress.
3. Configure the gateway under __WooCommerce > Settings > Payments__.

## Configuration

- Enter API keys and credentials in the backend
- Enable payment options and test mode if needed
- Adjust further settings as required

## Usage

- Customers can select Premium Black as a payment method during checkout.
- Payments are processed via the Premium Black API.

## Development

### Generate .pot file
`wp i18n make-pot . languages/woocommerce-gateway-premium-black.pot --allow-root`

### Important Files

- `premium-black-plugin.php` – Main plugin file
- `class-wc-gateway-premium-black.php` – Gateway implementation
- `class-premium-black-rest-endpoint.php` – REST API endpoints
- `assets/` – CSS & JS for frontend

## Support

For questions or issues, please open an issue in the repository or send us a mail to [contact@premium.black](mailto:contact@premium.black).

## License

[MIT License](LICENSE) or specify the appropriate license.
