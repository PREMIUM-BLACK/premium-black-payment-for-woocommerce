<?php

/**
 * Plugin Name: Premium Black Payment for Woocommerce
 * Author Name: Premium Black Ltd.
 * Author URI: https://premium.black
 * Description: This plugin allows you to offer crypto currency payments with Premium Black.
 * Version: 1.1.0
 * Text Domain: wc-gateway-premium-black
 * Domain Path: /languages
 *
 * Requires Plugins: woocommerce
 * WooCommerce tested up to: 9.8.0
 * WooCommerce Pro tested up to: 9.8.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if (!defined('WC_GATEWAY_PREMIUM_BLACK_VERSION')) {
    define('WC_GATEWAY_PREMIUM_BLACK_VERSION', '1.1.0');
}

// Make sure WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return;
}

/**
 * Load translations
 */
function wpdocs_load_textdomain()
{
    load_plugin_textdomain('wc-gateway-premium-black', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('init', 'wpdocs_load_textdomain');

/**
 * Add the gateway to WC Available Gateways
 *
 * @since 1.0.0
 * @param array $gateways all available WC gateways
 * @return array $gateways all WC gateways + premium black gateway
 */
function wc_premium_black_add_to_gateways($gateways)
{
    $gateways[] = 'WC_Gateway_Premium_Black';

    return $gateways;
}
add_filter('woocommerce_payment_gateways', 'wc_premium_black_add_to_gateways');

/**
 * Adds plugin page links
 *
 * @since 1.0.0
 * @param array $links all plugin links
 * @return array $links all plugin links + our custom links (i.e., "Settings")
 */
function wc_premium_black_gateway_plugin_links($links)
{
    $plugin_links = [
        '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=premium_black') . '">' . __('Settings') . '</a>'
    ];

    return array_merge($plugin_links, $links);
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'wc_premium_black_gateway_plugin_links');

/**
 * Premium Black Payment Gateway.
 *
 * Offers payments with crypto via Premium Black.
 *
 * @class       WC_Gateway_PREMIUM_BLACK
 * @extends     WC_Payment_Gateway
 * @version     1.0.0
 */
add_action('plugins_loaded', 'wc_gateway_premium_black_init', 11);

function wc_gateway_premium_black_init()
{
    require 'libs/api.php';
    require 'libs/classes.php';
    require 'onboarding.php';
    require 'settings.php';

    /**
     * Constructor for the gateway.
     */
    class WC_Gateway_Premium_Black extends WC_Payment_Gateway
    {
        public $api;
        protected array $availableCryptos;

        static WC_Gateway_Premium_Black $Instance;

        /**
         * Constructor for the gateway.
         */
        public function __construct()
        {
            

            // Setup general properties
            $this->setup_properties();

            // Load the settings.
            $this->init_form_fields();
            $this->init_settings();

            // Get settings
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->instructions = $this->get_option('instructions');
            $this->currencies = $this->get_option('currencies');
            $this->all_currencies = $this->get_option('all_currencies');
            $this->blockchains = $this->get_option('blockchains');

            $pluginVersion = get_file_data(__FILE__, ['Version'], 'plugin')[0];
            $wordpressVersion = get_bloginfo('version');

            // Initialize premium black api
            $this->api = new payAPI($this->get_option('debug') === 'yes');
            $this->api->setPublicKey($this->get_option('public_key'));
            $this->api->setPrivateKey($this->get_option('private_key'));
            $this->api->setEnvironment("WordPress=$wordpressVersion,WC-Gateway-PB=$pluginVersion");

            //$this->availableCryptos = [
            //    'btc' => 'Bitcoin (BTC)',
            //    'eth' => 'Ethereum (ETH)',
            //    'ltc' => 'Litecoin (LTC)',
            //    'dash' => 'Dash (DASH)',
            //];

            // Actions
            //add_action('woocommerce_api_wc_gateway_' . $this->id, [$this, 'check_response']);
            

            //var_dump($GLOBALS['wp']);

            add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);

            add_action('woocommerce_thankyou_' . $this->id, [$this, 'thankyou_page']);

            // Customer mails hooks
            add_action('woocommerce_email_before_order_table', [$this, 'email_instructions'], 10, 3);

            add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_styles'));

            
            //$this->register_webhook_endpoint();

            $Instance = $this;
        }

        /**
         * Frontend CSS einbinden
         */
        public function enqueue_frontend_styles()
        {
            // Nur auf relevanten Seiten laden (Checkout, Order received, etc.)
            if (is_checkout() || is_wc_endpoint_url('order-received') || is_account_page()) {
                wp_enqueue_style(
                    'wc-gateway-premium-black-frontend',
                    plugin_dir_url(__FILE__) . 'assets/payment.css',
                    array(),
                    '1.0.0' // Versionsnummer für Cache-Busting
                );
            }
        }



        public function is_available()
        {
            if ($this->get_option('enabled') != 'yes')
                return false;


            return !empty($this->get_option('public_key')) && !empty($this->get_option('private_key'));
        }

        /**
         * Setup general properties for the gateway.
         */
        protected function setup_properties()
        {
            $this->id = 'premium_black';
            $this->icon = apply_filters('woocommerce_premium_black_icon', plugins_url('assets/premiumblack.png', __FILE__)); //esc_url($this->module_url) .
            $this->has_fields = false;
            $this->method_title = 'Premium Black';
            $this->method_description = __('Offers payments with crypto via Premium Black.', 'wc-gateway-premium-black');
        }

        /**
         * Initialize Gateway Settings Form Fields
         */
        //public function init_form_fields()
        //{
        //    //var_dump(get_option('woocommerce_premium_black_settings'));

        //    $this->form_fields = apply_filters('wc_premium_black_gateway_fields', [
        //        'enabled' => [
        //            'title' => __('Enable/Disable', 'woocommerce'),
        //            'label' => __('Enable Premium Black Payments', 'wc-gateway-premium-black'),
        //            'type' => 'checkbox',
        //            'default' => 'no'
        //        ],
        //        'title' => [
        //            'title' => __('Title', 'woocommerce'),
        //            'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
        //            'type' => 'text',
        //            'default' => __('Pay with cryptocurrency', 'wc-gateway-premium-black'),
        //            'desc_tip' => true,
        //        ],
        //        'description' => [
        //            'title' => __('Description', 'woocommerce'),
        //            'description' => __('Payment method description that the customer will see on your checkout.', 'woocommerce'),
        //            'type' => 'textarea',
        //            'default' => __('Choose your preferred currency:', 'wc-gateway-premium-black'),
        //            'desc_tip' => true,
        //        ],
        //        'instructions' => [
        //            'title' => __('Instructions', 'woocommerce'),
        //            'description' => __('Instructions that will be added to the thank you page and emails.', 'woocommerce'),
        //            'type' => 'textarea',
        //            'desc_tip' => true,
        //        ],
        //        'enable_external_status_page' => [
        //            'title' => __('Enable external status page', 'wc-gateway-premium-black'),
        //            'label' => __('Shows button to external Premium Black status page instead of on the same page.', 'wc-gateway-premium-black'),
        //            'type' => 'checkbox',
        //            'default' => 'no',
        //        ],
        //        'public_key' => [
        //            'title' => __('Public key', 'wc-gateway-premium-black'),
        //            // 'description' => __('Public key', 'wc-gateway-premium-black'),
        //            'type' => 'textarea',
        //            // 'desc_tip'    => true,
        //        ],
        //        'private_key' => [
        //            'title' => __('Private key', 'wc-gateway-premium-black'),
        //            // 'description' => __('Private key', 'wc-gateway-premium-black'),
        //            'type' => 'textarea',
        //            // 'desc_tip'    => true,
        //        ],
        //        'debug' => [
        //            'title' => __('Enable/Disable', 'woocommerce'),
        //            'label' => __('Debug transactions', 'wc-gateway-premium-black'),
        //            'type' => 'checkbox',
        //            'default' => 'no',
        //        ],
        //    ]);





        //}

        public function admin_options()
        {
            // Weiterleitung zur benutzerdefinierten Einstellungsseite
            $settings_url = admin_url('admin.php?page=premium_black_settings');
            wp_redirect($settings_url);
            exit;
        }


        /**
         * Output the "payment type" radio buttons fields in checkout.
         */
        public function payment_fields()
        {
            if ($description = $this->get_description()) {
                echo wpautop(wptexturize($description));
            }

            $option_keys = array_keys($this->availableCryptos);

            woocommerce_form_field('transaction_currency', [
                'type' => 'radio',
                // 'label' => __('Cryptocurrency', 'wc-gateway-premium-black'),
                'options' => $this->availableCryptos,
                'required' => true,
            ], reset($option_keys));
        }

        //public function check_response(): void
        //{
        //    $status = isset($_GET['action']) ? esc_attr($_GET['action']) : '';
        //    $transactionId = isset($_GET['tx']) ? esc_attr($_GET['tx']) : '';

        //    $orders = wc_get_orders([
        //        'meta_key' => '_transaction_id',
        //        'meta_value' => $transactionId,
        //    ]);

        //    if (count($orders) !== 1) {
        //        status_header(404);
        //        exit;
        //    }

        //    $order = $orders[0];

        //    $request = new GetTransactionDetailsRequest();

        //    $request->TransactionId = $order->get_transaction_id();
        //    $request->TransactionKey = $order->get_meta('_transaction_key');
        //    $request->ReturnQRCode = 'false';

        //    $response = $this->api->GetTransactionDetails($request);

        //    if ($response === null || $response->Error != null || !$this->api->checkHash($response)) {
        //        status_header(500);
        //        exit;
        //    }

        //    if ($response->Status != $status) {
        //        status_header(400);
        //        exit;
        //    }

        //    $currency = strtoupper($response->Currency);
        //    $amount = "{$response->Amount} {$currency}";
        //    $receivedAmount = "{$response->ReceivedAmount} {$currency}";

        //    if ($response->Status === 'waitingforbalance') {
        //        $orderUrl = $order->get_checkout_order_received_url();

        //        $subject = sprintf(__('Payment for order #%s insufficient', 'wc-gateway-premium-black'), $order->get_order_number());

        //        $message = '<p>' . sprintf(__('We received <strong>%s</strong> of <strong>%s</strong>.', 'wc-gateway-premium-black'), $receivedAmount, $amount) . '</p>';
        //        $message .= '<p>' . __('Please sent the missing amount.', 'wc-gateway-premium-black') . '</p>';
        //        $message .= "<a class='button' href='$orderUrl'>" . __('Click here to see the payment details again.', 'wc-gateway-premium-black') . '</a>';

        //        $this->send_ipn_email_notification($order, $subject, $message);
        //    } elseif ($response->Status === 'waitingforconfirmation') {
        //        $subject = sprintf(__('Waiting for transaction confirmation of order #%s', 'wc-gateway-premium-black'), $order->get_order_number());

        //        $message = '<p>' . sprintf(__('Thank you for submitting your payment for order #%s.', 'wc-gateway-premium-black'), $order->get_order_number()) . '</p>';
        //        $message .= '<p>' . __('Your transaction has been registered now and is being verified.', 'wc-gateway-premium-black') . '</p>';
        //        $message .= '<p>' . __('We will notify you after the completion.', 'wc-gateway-premium-black') . '</p>';

        //        $this->send_ipn_email_notification($order, $subject, $message);
        //    } elseif ($response->Status === 'confirmed') {
        //        $order->update_status('processing', __('Payment was confirmed by Premium Black.', 'wc-gateway-premium-black'));
        //    } elseif ($response->Status === 'canceled') {
        //        $order->update_status('cancelled', __('The transaction was cancelled.', 'wc-gateway-premium-black'));
        //    }
        //    // elseif ($response->Status === 'reopened')
        //    // {
        //    //     $order->update_status('on-hold', __('The transaction was reopened.', 'wc-gateway-premium-black'));
        //    // }
        //    elseif ($response->Status === 'timeout') {
        //        $order->update_status('cancelled', __('The transaction timed out.', 'wc-gateway-premium-black'));
        //    }

        //    exit;
        //}

        /**
         * Modernisierte Webhook Handler Methode
         */
        

        /**
         * Behandlung für "waitingforbalance" Status
         */
        private function handle_waiting_for_balance($order, $amount, $receivedAmount)
        {
            $orderUrl = $order->get_checkout_order_received_url();

            $subject = sprintf(__('Payment for order #%s insufficient', 'wc-gateway-premium-black'), $order->get_order_number());

            $message = '<p>' . sprintf(__('We received <strong>%s</strong> of <strong>%s</strong>.', 'wc-gateway-premium-black'), $receivedAmount, $amount) . '</p>';
            $message .= '<p>' . __('Please sent the missing amount.', 'wc-gateway-premium-black') . '</p>';
            $message .= "<a class='button' href='$orderUrl'>" . __('Click here to see the payment details again.', 'wc-gateway-premium-black') . '</a>';

            $this->send_ipn_email_notification($order, $subject, $message);

            // Order Status auf "on-hold" setzen
            $order->update_status('on-hold', __('Payment insufficient - waiting for remaining balance.', 'wc-gateway-premium-black'));
        }

        /**
         * Behandlung für "waitingforconfirmation" Status
         */
        private function handle_waiting_for_confirmation($order)
        {
            $subject = sprintf(__('Waiting for transaction confirmation of order #%s', 'wc-gateway-premium-black'), $order->get_order_number());

            $message = '<p>' . sprintf(__('Thank you for submitting your payment for order #%s.', 'wc-gateway-premium-black'), $order->get_order_number()) . '</p>';
            $message .= '<p>' . __('Your transaction has been registered now and is being verified.', 'wc-gateway-premium-black') . '</p>';
            $message .= '<p>' . __('We will notify you after the completion.', 'wc-gateway-premium-black') . '</p>';

            $this->send_ipn_email_notification($order, $subject, $message);

            // Order Status auf "pending" setzen
            $order->update_status('pending', __('Payment submitted - waiting for blockchain confirmation.', 'wc-gateway-premium-black'));
        }

        /**
         * Neue Webhook URL für deinen Payment Provider
         */
        public function get_webhook_url()
        {
            return rest_url('premium-black/v1/webhook');
        }

        /**
         * Process the payment and return the result.
         *
         * @param int $order_id Order ID.
         */
        public function process_payment($order_id): array
        {
            $order = wc_get_order($order_id);

            if (!isset($_POST['transaction_currency'])) {
                return [
                    'result' => 'error',
                    'message' => 'Currency not set',
                ];
            }

            $currency = isset($_POST['transaction_currency']) ? esc_attr($_POST['transaction_currency']) : '';
            if ($currency) {
                $order->update_meta_data('_transaction_currency', $currency);
            }

            try {
                $response = $this->create_api_transaction_request($order, $currency);
            } catch (Exception $exception) {
                error_log($exception->getMessage());

                $order->update_status('failed', $exception->getMessage());

                return [
                    'result' => 'error',
                ];
            }

            $order->set_transaction_id($response->TransactionId);
            $order->update_meta_data('_transaction_key', $response->TransactionKey);

            if ($order->get_total() > 0) {
                $order->update_status('on-hold', __('Awaiting crypto transaction', 'wc-gateway-premium-black'));
            } else {
                $order->payment_complete();
            }

            // Reduce stock levels
            wc_reduce_stock_levels($order);

            // Remove cart.
            WC()->cart->empty_cart();

            // Return thankyou redirect.
            return [
                'result' => 'success',
                'redirect' => $this->get_return_url($order),
            ];
        }

        private function create_api_transaction_request(WC_Order $order, $codechain): object
        {
            if (!isset($codechain)) {
                throw new Exception('Currency not set');
            }

            if(!isset($this->all_currencies) || !is_array($this->all_currencies) || empty($this->all_currencies)) {
                throw new Exception("All currencies not available");
            }

            //Search for blockchain
            $blockchain = null;
            $transaction_currency = null;
            foreach($this->all_currencies as $currency){
                if(strcmp($currency->CodeChain, $codechain) != 0)
                    continue;

                $blockchain = $currency->Blockchain;
                $transaction_currency = $currency->Symbol;
            }

            if (!isset($blockchain)) {
                throw new Exception('Blockchain not found');
            }

            $request = new CreateTransactionRequest();

            $request->Amount = $order->get_total();
            $request->Currency = $transaction_currency;
            $request->Blockchain = $blockchain;
            $request->PriceCurrency = $order->get_currency();
            $request->IPN = get_webhook_url();//home_url("/wc-api/wc_gateway_$this->id");
            $request->BlockAddress = 'true';
            $request->CustomUserId = $order->get_customer_id();
            $request->CustomOrderId = $order->get_id();
            $request->CustomerMail = $order->get_billing_email();

            $response = $this->api->CreateTransaction($request);

            if ($response === null) {
                throw new Exception('Api response was empty.');
            }

            if ($response->Error != null) {
                throw new Exception($response->Error);
            }

            if (!$this->api->checkHash($response)) {
                throw new Exception('Response is malformed/manipulated.');
            }

            return $response;
        }

        /**
         * Output for the order received page.
         */
        public function thankyou_page(int $order_id): void
        {
            $order = wc_get_order($order_id);

            if ($this->instructions) {
                echo wp_kses_post(wpautop(wptexturize(wp_kses_post($this->instructions))));
            }

            $this->get_payment_details($order);
        }

        /**
         * Get payment details and place into a list format.
         */
        private function get_payment_details(WC_Order $order): void
        {
            


            if ($order->has_status('failed')) {
                return;
            }

            $transactionId = $order->get_transaction_id();
            $transactionKey = $order->get_meta('_transaction_key');

            $request = new GetTransactionDetailsRequest();

            if (!$transactionId || !$transactionKey) {
                $order->update_status('failed', 'Missing transaction id/key');
                $order->set_transaction_id(null);
                $order->delete_meta_data('_transaction_key');

                echo '<p class="status-message">' . __('The payment details could not be loaded. Please try reloading the page.', 'wc-gateway-premium-black') . '</p>';

                return;
            }


            $request->TransactionId = $transactionId;
            $request->TransactionKey = $transactionKey;
            $request->ReturnQRCode = 'true';

            $response = $this->api->GetTransactionDetails($request);

            echo '<div class="wc-gateway-premium-black">';

            echo '<h2 class="status-heading">' . __('Status', 'wc-gateway-premium-black') . '</h2>';

            if ($response === null || $response->Error != null || !$this->api->checkHash($response)) {
                echo '<p class="status-message">' . __('The payment details could not be loaded. Please try reloading the page.', 'wc-gateway-premium-black') . '</p>';

                return;
            }

            if ($response->Status === 'confirmed') {
                echo '<p class="status-message">' . __('The order has already been paid. No further action required.', 'wc-gateway-premium-black') . "</p>";
            } elseif ($response->Status === 'waitingforfunds') {

                $currency = strtoupper($response->Currency);
                $blockchain = strtoupper($response->Blockchain);

                $blockchain_name = '';
                foreach($this->blockchains as $block){
                    if(strcmp($block->Code, $response->Blockchain) == 0)
                        $blockchain_name = $block->Name;
                }

                $amount = "{$response->Amount} {$currency}";
                $receivedAmount = "{$response->ReceivedAmount} {$currency}";

                echo '<p class="status-message">' . __('Please pay the following amount to the following address, if not already happend:', 'wc-gateway-premium-black') . "</p>";

                echo '<p class="amount">' . __('Amount:', 'wc-gateway-premium-black') . " <strong>$amount</strong></p>";

                echo '<p class="amount-received">' . __('Amount received:', 'wc-gateway-premium-black') . " <strong>$receivedAmount</strong></p>";

                echo '<p class="blockchain">' . __('Blockchain:', 'wc-gateway-premium-black') . " <strong>via $blockchain_name ($blockchain)</strong></p>";

                // Neuer Hinweis zur Blockchain-spezifischen Zahlung
                echo '<p class="blockchain-notice warning">' . sprintf(
                    __('⚠️ Important: This payment can only be processed via the %s blockchain. Please ensure you send the payment from a %s compatible wallet.', 'wc-gateway-premium-black'),
                    $blockchain_name,
                    $blockchain_name
                ) . '</p>';

                echo '<p class="address">' . __('Address:', 'wc-gateway-premium-black') . " <strong>$response->AddressToReceive</strong></p>";


                if ($this->get_option('enable_external_status_page') === 'yes') {
                    echo '<a class="button" href="' . $response->Url . '" target="_blank">' . __('Pay now or see current status') . '</a><br /><br />';

                }

                echo '<br/><img class="qr-code" src="' . $response->QRCode . '" /><br/>';

                echo '<br/><p class="hint">' . __('In most cases, payments are processed immediately. In rare cases, it may take a few hours.', 'wc-gateway-premium-black') . '</p>';
            } elseif ($response->Status === 'canceled') {
                echo '<p class="status-message">' . __('The order was cancelled.', 'wc-gateway-premium-black') . "</p>";
            } elseif ($response->Status === 'timeout') {
                echo '<p class="status-message">' . __('The execution of your payment transaction is no longer possible, because your session has expired. Please create a new order.', 'wc-gateway-premium-black') . "</p>";
            }

            echo '</div>';
        }

        /**
         * Send a notification to the user handling orders.
         */
        private function send_ipn_email_notification(WC_Order $order, string $subject, string $message)
        {
            $mailer = WC()->mailer();
            $message = $mailer->wrap_message($subject, $message);

            $mailer->send($order->get_billing_email(), strip_tags($subject), $message);
        }

        /**
         * Add content to the WC emails.
         */
        public function email_instructions(WC_Order $order): void
        {
            if ($order->has_status('on-hold')) {
                if ($this->get_option('enable_external_status_page') === 'yes') {
                    $transactionId = $order->get_transaction_id();
                    $transactionKey = $order->get_meta('_transaction_key');

                    echo "<p><a class='button' href='https://premium.black/status/?id=$transactionId&k=$transactionKey' target='_blank'>" . __('Pay now/See status', 'wc-gateway-premium-black') . '</a></p>';

                    return;
                } else {
                    $orderUrl = $order->get_checkout_order_received_url();

                    echo "<p><a class='button' href='$orderUrl'>" . __('Pay now/See status', 'wc-gateway-premium-black') . '</a></p>';
                }
            } elseif ($order->has_status('processing')) {
                echo '<p>' . __('Your payment request has been approved.', 'wc-gateway-premium-black') . '</p>';
            }
        }
    }

    add_filter('woocommerce_available_payment_gateways', 'premium_black_filter_gateways');

}

function premium_black_filter_gateways($methods)
{
    if (!is_array($methods) || is_admin()) {
        return $methods;
    }

    if (!isset($methods['premium_black'])) {
        return $methods;
    }

    //$gateway = $methods['premium_black'];

    //print_r($gateway);

    // Prüfung: sind verfügbare Coins gesetzt?
    // if (empty($gateway->availablecryptos) || !is_array($gateway->availablecryptos)) {

    // unset($methods['premium_black']);
    // }

    //print_r($methods);

    return $methods;
}


/**
 * Custom function to declare compatibility with cart_checkout_blocks feature
 */
function wc_premium_black_declare_cart_checkout_blocks_compatibility()
{

    // Check if the required class exists
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        // Declare compatibility for 'cart_checkout_blocks'
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
}

/**
 * Custom function to register a payment method type

 */
function wc_premium_black_register_order_approval_payment_method_type()
{

    // Check if the required class exists
    if (!class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        return;
    }

    // Include the custom Blocks Checkout class
    require_once plugin_dir_path(__FILE__) . 'class-block.php';

    // Hook the registration function to the 'woocommerce_blocks_payment_method_type_registration' action
    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
            $payment_method_registry->register(new Premium_Black_Gateway_Blocks);
        }
    );
}

// Hook the custom function to the 'before_woocommerce_init' action
add_action('before_woocommerce_init', 'wc_premium_black_declare_cart_checkout_blocks_compatibility');

// Hook the custom function to the 'woocommerce_blocks_loaded' action
add_action('woocommerce_blocks_loaded', 'wc_premium_black_register_order_approval_payment_method_type');


add_action('admin_notices', 'premium_black_admin_notice');

function premium_black_admin_notice()
{
    if (!empty(get_option('woocommerce_premium_black_settings')['public_key']) && !empty(get_option('woocommerce_premium_black_settings')['private_key'])) {

        return;
    }

    $image_url = plugins_url('assets/premiumblack.png', __FILE__);

    ?>
    <div class="notice notice-warning is-dismissible" style="display: flex; align-items: center; gap: 12px;">
        <img src="<?php echo esc_url($image_url); ?>" style="width:32px; height:32px;" alt="Premium Black" />
        <?php _e('Premium Black is almost ready. To get started, fill in your API credentials to finish the installation.', 'wc-gateway-premium-black'); ?>
        <a href="<?php echo esc_url(admin_url('admin.php?page=premium-black-onboarding')); ?>">
            <?php _e('Start onboarding', 'wc-gateway-premium-black'); ?>
        </a>.
    </div>
    <?php
}

register_activation_hook(__FILE__, function () {
    add_option('premium_black_do_activation_redirect', true);
});

add_action('admin_init', function () {
    if (get_option('premium_black_do_activation_redirect', false)) {
        delete_option('premium_black_do_activation_redirect');
        if (!isset($_GET['activate-multi'])) {
            wp_safe_redirect(admin_url('admin.php?page=premium-black-onboarding'));
            exit;
        }
    }
});


/**
    * REST API Webhook Endpoint registrieren
    */
function wc_premium_black_register_webhook_endpoint()
{

    $result = register_rest_route('premium-black/v1', '/webhook', array(
        'methods' => array('GET', 'POST'), // Beide Methoden erlauben
        'callback' => 'handle_webhook_request',
        'permission_callback' => '__return_true',
        'args' => array(
            'action' => array(
                'required' => false, // Nicht required für Debug
                'validate_callback' => function ($param) {
                    return is_string($param);
                }
            ),
            'tx' => array(
                'required' => false, // Nicht required für Debug
                'validate_callback' => function ($param) {
                    return is_string($param);
                }
            )
        )
    ));
}

function handle_webhook_request(WP_REST_Request $request)
{
    $status = sanitize_text_field($request->get_param('action'));
    $transactionId = sanitize_text_field($request->get_param('tx'));

    // Logging für Debug-Zwecke
    error_log("Premium Black Webhook: action={$status}, tx={$transactionId}");

    // Bestellung anhand Transaction ID finden
    $orders = wc_get_orders([
        'transactionId' => $transactionId,
        'limit' => 1
    ]);

    if (count($orders) !== 1) {
        return new WP_Error('order_not_found', 'Order not found for transaction ID: ' . $transactionId, array('status' => 404));
    }

    $order = $orders[0];

    // Transaction Details von API abrufen
    $request_details = new GetTransactionDetailsRequest();
    $request_details->TransactionId = $order->get_transaction_id();
    $request_details->TransactionKey = $order->get_meta('_transaction_key');
    $request_details->ReturnQRCode = 'false';

    $instance = new WC_Gateway_Premium_Black();

    $response = $instance->api->GetTransactionDetails($request_details);

    // API Response validieren
    if ($response === null || $response->Error != null || !$instance->api->checkHash($response)) {
        return new WP_Error('api_error', 'Failed to validate transaction details', array('status' => 500));
    }

    // Status-Konsistenz prüfen
    if ($response->Status != $status) {
        return new WP_Error('status_mismatch', 'Status mismatch between webhook and API', array('status' => 400));
    }

    // Beträge für E-Mail-Benachrichtigungen
    $currency = strtoupper($response->Currency);
    $amount = "{$response->Amount} {$currency}";
    $receivedAmount = "{$response->ReceivedAmount} {$currency}";

    // Status-spezifische Behandlung
    switch ($response->Status) {
        case 'waitingforbalance':
            $instance->handle_waiting_for_balance($order, $amount, $receivedAmount);
            break;

        case 'waitingforconfirmation':
            $instance->handle_waiting_for_confirmation($order);
            break;

        case 'confirmed':
            $order->payment_complete($transactionId);
            $order->add_order_note(__('Payment was confirmed by Premium Black.', 'wc-gateway-premium-black'));
            break;

        case 'canceled':
            $order->update_status('cancelled', __('The transaction was cancelled.', 'wc-gateway-premium-black'));
            break;

        case 'timeout':
            $order->update_status('cancelled', __('The transaction timed out.', 'wc-gateway-premium-black'));
            break;

        default:
            return new WP_Error('unknown_status', 'Unknown transaction status: ' . $response->Status, array('status' => 400));
    }

    // Erfolgreiche Antwort
    return rest_ensure_response(array(
        'success' => true,
        'order_id' => $order->get_id(),
        'transaction_id' => $transactionId,
        'status' => $response->Status,
        'message' => 'Webhook processed successfully'
    ));
}


add_action('rest_api_init', 'wc_premium_black_register_webhook_endpoint');