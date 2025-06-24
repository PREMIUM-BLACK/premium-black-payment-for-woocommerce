<?php
/**
 * Plugin Name:  Premium Black Payment for WooCommerce
 * Plugin URI: https://github.com/PREMIUM-BLACK/woocommerce-premium-black
 * Author Name: Premium Black Ltd.
 * Author URI: https://premium.black
 * Description: This plugin allows you to offer crypto currency payments with Premium Black.
 * Version: 1.1.2
 * Text Domain: premium-black-payment-for-woocommerce
 * Domain Path: /languages
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * Requires Plugins: woocommerce
 * Requires at least: 5.0
 * WooCommerce tested up to: 9.8.0
 * WooCommerce Pro tested up to: 9.8.0
 */

if (!defined('ABSPATH')) exit;

// WooCommerce aktiv?
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) return;

// Textdomain laden
add_action('init', function() {
    load_plugin_textdomain('premium-black-payment-for-woocommerce', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

// Gateway registrieren
add_filter('woocommerce_payment_gateways', function($gateways) {
    $gateways[] = 'WC_Gateway_Premium_Black';
    return $gateways;
});

// Plugin-Links
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
    $plugin_links = [
        '<a href="' . admin_url('admin.php?page=premium_black_settings') . '">' . __('Settings', 'premium-black-payment-for-woocommerce') . '</a>',
        '<a href="https://github.com/PREMIUM-BLACK/premium-black-payment-for-woocommerce" target="_blank">GitHub</a>',
        '<a href="https://premium.black" target="_blank">Website</a>',
    ];
    return array_merge($plugin_links, $links);
});

// Gateway, REST & weitere Hooks laden
require_once __DIR__ . '/class-wc-gateway-premium-black.php';
require_once __DIR__ . '/class-premium-black-rest-endpoint.php';

/**
 * Custom function to declare compatibility with cart_checkout_blocks feature
 */
function premblpa_declare_cart_checkout_blocks_compatibility()
{
    if (class_exists('Automattic\\WooCommerce\\Utilities\\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
}

/**
 * Custom function to register a payment method type
 */
function premblpa_register_order_approval_payment_method_type()
{
    if (!class_exists('Automattic\\WooCommerce\\Blocks\\Payments\\Integrations\\AbstractPaymentMethodType')) {
        return;
    }
    require_once plugin_dir_path(__FILE__) . 'class-wc-block.php';
    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
            $payment_method_registry->register(new Premium_Black_Gateway_Blocks);
        }
    );
}

add_action('before_woocommerce_init', 'premblpa_declare_cart_checkout_blocks_compatibility');
add_action('woocommerce_blocks_loaded', 'premblpa_register_order_approval_payment_method_type');

function premblpa_admin_notice()
{
    if (!empty(get_option('woocommerce_premium_black_settings')['public_key']) && !empty(get_option('woocommerce_premium_black_settings')['private_key'])) {
        return;
    }
    $image_url = plugins_url('assets/premiumblack.png', __FILE__);
    ?>
    <div class="notice notice-warning is-dismissible" style="display: flex; align-items: center; gap: 12px;">
        <img src="<?php echo esc_url($image_url); ?>" style="width:32px; height:32px;" alt="Premium Black" />
        <?php echo esc_html(__('Premium Black is almost ready. To get started, fill in your API credentials to finish the installation.', 'premium-black-payment-for-woocommerce')); ?>
        <a href="<?php echo esc_url(admin_url('admin.php?page=premium-black-onboarding')); ?>">
            <?php echo esc_html(__('Finish onboarding', 'premium-black-payment-for-woocommerce')); ?>
        </a>
    </div>
    <?php
}
add_action('admin_notices', 'premblpa_admin_notice');

register_activation_hook(__FILE__, function () {
    add_option('premblpa_do_activation_redirect', true);
});

//add_action('admin_init', function () {
//    if (get_option('premblpa_do_activation_redirect', false)) {
//        delete_option('premblpa_do_activation_redirect');
//        if (!isset($_GET['activate-multi'])) {
//            wp_safe_redirect(admin_url('admin.php?page=premium-black-onboarding'));
//            exit;
//        }
//    }
//});