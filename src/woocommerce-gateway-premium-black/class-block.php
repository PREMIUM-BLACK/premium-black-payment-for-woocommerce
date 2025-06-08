<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class Premium_Black_Gateway_Blocks extends AbstractPaymentMethodType {

    protected $name = 'premium_black';// your payment gateway name

    public function initialize() {
        $this->settings = get_option( 'woocommerce_premium_black_settings', [] );
    }

    public function is_active() {
        return $this->settings['enabled'];;
    }

    public function get_payment_method_script_handles() {

        wp_register_script(
            'premium_black_gateway',
            plugin_dir_url(__FILE__) . 'checkout.js',
            [
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
            ],
            array(),
            '1.0'
        );
        if( function_exists( 'wp_set_script_translations' ) ) {            
            wp_set_script_translations( 'premium_black_gateway');
            
        }
        return [ 'premium_black_gateway' ];
    }

    public function get_payment_method_data() {

        $allCurrencies = $this->settings['all_currencies'];
        $activeCurrencies = $this->settings['currencies'];
        $filtered = [];
        if(is_array($activeCurrencies)){
            foreach ($allCurrencies as $currency) {
                if (in_array($currency->CodeChain, $activeCurrencies)) {
                    $filtered[] = $currency;
                }
            }

        }
        

        return [
            'title' => $this->settings['title'],
            'description' => $this->settings['description'],
            'instructions' => $this->settings['instructions'],
            'currencies' => $filtered,
            'icon' => plugin_dir_url(__DIR__) . 'woocommerce-gateway-premium-black/assets/premiumblack.png',
            'is_configured' => $this->is_active()
        ];
    }

}
?>