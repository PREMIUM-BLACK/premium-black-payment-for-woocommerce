<?php

/**
 * Premium Black Payment Gateway.
 *
 * Offers payments with crypto via Premium Black.
 *
 * @class       WC_Gateway_PREMIUM_BLACK
 * @extends     WC_Payment_Gateway
 * @version     1.1.0
 */
add_action('plugins_loaded', 'wc_gateway_premium_black_init', 11);


function wc_gateway_premium_black_init()
{
    require 'libs/api.php';
    require 'libs/classes.php';
    require 'onboarding.php';
    require 'settings.php';

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

            add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);

            add_action('woocommerce_thankyou_' . $this->id, [$this, 'thankyou_page']);

            // Customer mails hooks
            add_action('woocommerce_email_before_order_table', [$this, 'email_instructions'], 10, 3);

            add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_styles'));

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
                    'message'=> $exception->getMessage(),   
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

            if (!isset($this->all_currencies) || !is_array($this->all_currencies) || empty($this->all_currencies)) {
                throw new Exception("All currencies not available");
            }

            //Search for blockchain
            $blockchain = null;
            $transaction_currency = null;
            foreach ($this->all_currencies as $currency) {
                if (strcmp($currency->CodeChain, $codechain) != 0)
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
            $request->IPN = $this->get_webhook_url();//home_url("/wc-api/wc_gateway_$this->id");
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
                foreach ($this->blockchains as $block) {
                    if (strcmp($block->Code, $response->Blockchain) == 0)
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
}

//// Optional: Singleton-Instanz bereitstellen
//function wc_gateway_premium_black_instance() {
//    static $instance = null;
//    if ($instance === null) {
//        $instance = new WC_Gateway_Premium_Black();
//    }
//    return $instance;
//}
