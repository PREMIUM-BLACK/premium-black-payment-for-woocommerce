<?php

if (!defined('ABSPATH'))
    exit;

/**
 * Premium Black Payment Gateway.
 *
 * Offers payments with crypto via Premium Black.
 *
 * @class       Premblpa_WC_Gateway
 * @extends     WC_Payment_Gateway
 * @version     1.1.1
 */
add_action('plugins_loaded', 'premblpa_gateway_init', 11);


function premblpa_gateway_init()
{
    require 'libs/api.php';
    require 'libs/classes.php';
    require 'onboarding.php';
    require 'settings.php';

    class Premblpa_WC_Gateway extends WC_Payment_Gateway
    {
        public $api;
        protected array $availableCryptos;

        static Premblpa_WC_Gateway $Instance;

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
            $this->api = new Premblpa_Pay_API($this->get_option('debug') === 'yes');
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
                    'premblpa-frontend',
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
            $this->icon = apply_filters('premblpa_gateway_icon', plugins_url('assets/premiumblack.png', __FILE__)); //esc_url($this->module_url) .
            $this->has_fields = false;
            $this->method_title = 'Premium Black';
            $this->method_description = __('Offers payments with crypto via Premium Black.', 'premium-black-payment-for-woocommerce');
        }


        public function admin_options()
        {
            // Weiterleitung zur benutzerdefinierten Einstellungsseite
            $settings_url = admin_url('admin.php?page=premblpa_settings');
            wp_safe_redirect($settings_url);
            exit;
        }


        /**
         * Output the "payment type" radio buttons fields in checkout.
         */
        public function payment_fields()
        {
            if ($description = $this->get_description()) {
                echo esc_html(wpautop(wptexturize($description)));
            }

            $option_keys = array_keys($this->availableCryptos);

            woocommerce_form_field('transaction_currency', [
                'type' => 'radio',
                // 'label' => __('Cryptocurrency', 'premium-black-payment-for-woocommerce'),
                'options' => $this->availableCryptos,
                'required' => true,
            ], reset($option_keys));
        }


        /**
         * Behandlung für "waitingforbalance" Status
         */
        public function handle_waiting_for_balance($order, $amount, $receivedAmount)
        {
            $orderUrl = $order->get_checkout_order_received_url();

            // Nachher:
            $subject = sprintf(
                /* translators: %s: Order number */
                __('Payment for order #%s insufficient', 'premium-black-payment-for-woocommerce'),
                $order->get_order_number()
            );
            $message = '<p>' . sprintf(
                /* translators: 1: received amount, 2: expected amount */
                __('We received <strong>%1$s</strong> of <strong>%2$s</strong>.', 'premium-black-payment-for-woocommerce'),
                $receivedAmount,
                $amount
            ) . '</p>';
            $message .= '<p>' . __('Please sent the missing amount.', 'premium-black-payment-for-woocommerce') . '</p>';
            $message .= "<a class='button' href='$orderUrl'>" . __('Click here to see the payment details again.', 'premium-black-payment-for-woocommerce') . '</a>';

            $this->send_ipn_email_notification($order, $subject, $message);

            // Order Status auf "on-hold" setzen
            $order->update_status('on-hold', __('Payment insufficient - waiting for remaining balance.', 'premium-black-payment-for-woocommerce'));
        }

        /**
         * Behandlung für "waitingforconfirmation" Status
         */
        public function handle_waiting_for_confirmation($order)
        {
            // Zeile 165–167: Fehlende "translators:"-Kommentare
            $subject = sprintf(
                /* translators: %s: Order id */
                __('Waiting for transaction confirmation of order #%s', 'premium-black-payment-for-woocommerce'),
                $order->get_order_number()
            );

            // translators: %s: Order id
            $message = '<p>' . sprintf(__('Thank you for submitting your payment for order #%s.', 'premium-black-payment-for-woocommerce'), $order->get_order_number()) . '</p>';
            $message .= '<p>' . __('Your transaction has been registered now and is being verified.', 'premium-black-payment-for-woocommerce') . '</p>';
            $message .= '<p>' . __('We will notify you after the completion.', 'premium-black-payment-for-woocommerce') . '</p>';

            $this->send_ipn_email_notification($order, $subject, $message);

            // Order Status auf "pending" setzen
            $order->update_status('pending', __('Payment submitted - waiting for blockchain confirmation.', 'premium-black-payment-for-woocommerce'));
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

            $currency = isset($_POST['transaction_currency']) ? esc_attr(sanitize_text_field(wp_unslash($_POST['transaction_currency']))) : '';
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
                $order->update_status('on-hold', __('Awaiting crypto transaction', 'premium-black-payment-for-woocommerce'));
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

            $request = new Premblpa_CreateTransactionRequest();

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
                throw new Exception(esc_html($response->Error));
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

            $request = new Premblpa_GetTransactionDetailsRequest();

            if (!$transactionId || !$transactionKey) {
                $order->update_status('failed', 'Missing transaction id/key');
                $order->set_transaction_id(null);
                $order->delete_meta_data('_transaction_key');

                echo '<div class="wc-gateway-premium-black">';
                echo '<div class="premblpa-pay-header"><h2>' . esc_html(__('Payment', 'premium-black-payment-for-woocommerce')) . '</h2><span class="premblpa-badge premblpa-badge--error">' . esc_html(__('Error', 'premium-black-payment-for-woocommerce')) . '</span></div>';
                echo '<p class="premblpa-pay-message">' . esc_html(__('The payment details could not be loaded. Please try reloading the page.', 'premium-black-payment-for-woocommerce')) . '</p>';
                echo '</div>';

                return;
            }


            $request->TransactionId = $transactionId;
            $request->TransactionKey = $transactionKey;
            $request->ReturnQRCode = 'true';

            $response = $this->api->GetTransactionDetails($request);

            echo '<div class="wc-gateway-premium-black">';

            if ($response === null || $response->Error != null || !$this->api->checkHash($response)) {
                echo '<div class="premblpa-pay-header"><h2>' . esc_html(__('Payment', 'premium-black-payment-for-woocommerce')) . '</h2><span class="premblpa-badge premblpa-badge--error">' . esc_html(__('Error', 'premium-black-payment-for-woocommerce')) . '</span></div>';
                echo '<p class="premblpa-pay-message">' . esc_html(__('The payment details could not be loaded. Please try reloading the page.', 'premium-black-payment-for-woocommerce')) . '</p>';
                echo '</div>';

                return;
            }

            if ($response->Status === 'confirmed') {
                echo '<div class="premblpa-pay-header"><h2>' . esc_html(__('Payment', 'premium-black-payment-for-woocommerce')) . '</h2><span class="premblpa-badge premblpa-badge--success">&#10003; ' . esc_html(__('Confirmed', 'premium-black-payment-for-woocommerce')) . '</span></div>';
                echo '<p class="premblpa-pay-message">' . esc_html(__('The order has already been paid. No further action required.', 'premium-black-payment-for-woocommerce')) . '</p>';

            } elseif ($response->Status === 'waitingforfunds' || $response->Status === 'waitingforbalance') {

                $currency = strtoupper($response->Currency);
                $blockchain = strtoupper($response->Blockchain);

                $blockchain_name = '';
                foreach ($this->blockchains as $block) {
                    if (strcmp($block->Code, $response->Blockchain) == 0)
                        $blockchain_name = $block->Name;
                }

                $amount = "{$response->Amount} {$currency}";
                $receivedAmount = "{$response->ReceivedAmount} {$currency}";
                $hasReceived = floatval($response->ReceivedAmount) > 0;
                if (function_exists('bcsub')) {
                    $remainingAmount = bcsub($response->Amount, $response->ReceivedAmount, 8);
                } else {
                    $remainingAmount = number_format(floatval($response->Amount) - floatval($response->ReceivedAmount), 8, '.', '');
                }
                $remainingFormatted = rtrim(rtrim($remainingAmount, '0'), '.') . ' ' . $currency;

                if ($response->Status === 'waitingforbalance') {
                    echo '<div class="premblpa-pay-header"><h2>' . esc_html(__('Payment', 'premium-black-payment-for-woocommerce')) . '</h2><span class="premblpa-badge premblpa-badge--balance">&#9679; ' . esc_html(__('Waiting for balance', 'premium-black-payment-for-woocommerce')) . '</span></div>';
                    echo '<p class="premblpa-pay-message">' . esc_html(__('We received a partial payment. Please send the remaining amount to complete your order:', 'premium-black-payment-for-woocommerce')) . '</p>';
                } else {
                    echo '<div class="premblpa-pay-header"><h2>' . esc_html(__('Payment', 'premium-black-payment-for-woocommerce')) . '</h2><span class="premblpa-badge premblpa-badge--pending">&#9679; ' . esc_html(__('Waiting for payment', 'premium-black-payment-for-woocommerce')) . '</span></div>';
                    echo '<p class="premblpa-pay-message">' . esc_html(__('Please pay the following amount to the following address, if not already happened:', 'premium-black-payment-for-woocommerce')) . '</p>';
                }

                // Info grid
                echo '<div class="premblpa-pay-info">';

                echo '<div class="premblpa-pay-info-item">';
                echo '<span class="premblpa-pay-info-label">' . esc_html(__('Amount', 'premium-black-payment-for-woocommerce')) . '</span>';
                echo '<span class="premblpa-pay-info-value">' . esc_html($amount) . '</span>';
                echo '</div>';

                $receivedClass = $hasReceived ? 'premblpa-pay-info-item premblpa-pay-info-item--received' : 'premblpa-pay-info-item';
                echo '<div class="' . esc_attr($receivedClass) . '">';
                echo '<span class="premblpa-pay-info-label">' . esc_html(__('Amount received', 'premium-black-payment-for-woocommerce')) . '</span>';
                echo '<span class="premblpa-pay-info-value">' . esc_html($receivedAmount) . '</span>';
                echo '</div>';

                if ($hasReceived) {
                    echo '<div class="premblpa-pay-info-item premblpa-pay-info-item--remaining premblpa-pay-info-item--full">';
                    echo '<span class="premblpa-pay-info-label">' . esc_html(__('Remaining amount', 'premium-black-payment-for-woocommerce')) . '</span>';
                    echo '<span class="premblpa-pay-info-value">' . esc_html($remainingFormatted) . '</span>';
                    echo '</div>';
                }

                echo '<div class="premblpa-pay-info-item premblpa-pay-info-item--full">';
                echo '<span class="premblpa-pay-info-label">' . esc_html(__('Blockchain', 'premium-black-payment-for-woocommerce')) . '</span>';
                echo '<span class="premblpa-pay-info-value">' . esc_html($blockchain_name) . ' (' . esc_html($blockchain) . ')</span>';
                echo '</div>';

                echo '<div class="premblpa-pay-info-item premblpa-pay-info-item--full premblpa-pay-info-item--address">';
                echo '<span class="premblpa-pay-info-label">' . esc_html(__('Address', 'premium-black-payment-for-woocommerce')) . '</span>';
                echo '<span class="premblpa-pay-info-value">' . esc_html($response->AddressToReceive) . '</span>';
                echo '</div>';

                echo '</div>';

                // Warning
                echo '<div class="premblpa-pay-warning">';
                echo '<span class="premblpa-pay-warning-icon">&#9888;</span>';
                echo '<span>' . sprintf(
                    /* translators: 1: Blockchain-Name, 2: Blockchain-Name */
                    esc_html(__('Important: This payment can only be processed via the %1$s blockchain. Please ensure you send the payment from a %2$s compatible wallet.', 'premium-black-payment-for-woocommerce')),
                    esc_html($blockchain_name),
                    esc_html($blockchain_name)
                ) . '</span>';
                echo '</div>';

                // QR Code
                if (!empty($response->QRCode)) {
                    $allowed_protocols = array_merge(wp_allowed_protocols(), array('data'));
                    echo '<div class="premblpa-pay-qr"><img src="' . esc_url($response->QRCode, $allowed_protocols) . '" alt="QR Code" /></div>';
                }

                // Actions
                if ($this->get_option('enable_external_status_page') === 'yes') {
                    echo '<div class="premblpa-pay-actions">';
                    echo '<a class="button" href="' . esc_url($response->Url) . '" target="_blank">&#8599; ' . esc_html(__('Pay now or see current status', 'premium-black-payment-for-woocommerce')) . '</a>';
                    echo '</div>';
                }

                echo '<p class="premblpa-pay-hint">' . esc_html(__('In most cases, payments are processed immediately. In rare cases, it may take a few hours.', 'premium-black-payment-for-woocommerce')) . '</p>';

            } elseif ($response->Status === 'canceled') {
                echo '<div class="premblpa-pay-header"><h2>' . esc_html(__('Payment', 'premium-black-payment-for-woocommerce')) . '</h2><span class="premblpa-badge premblpa-badge--error">' . esc_html(__('Cancelled', 'premium-black-payment-for-woocommerce')) . '</span></div>';
                echo '<p class="premblpa-pay-message">' . esc_html(__('The order was cancelled.', 'premium-black-payment-for-woocommerce')) . '</p>';

            } elseif ($response->Status === 'timeout') {
                echo '<div class="premblpa-pay-header"><h2>' . esc_html(__('Payment', 'premium-black-payment-for-woocommerce')) . '</h2><span class="premblpa-badge premblpa-badge--error">' . esc_html(__('Expired', 'premium-black-payment-for-woocommerce')) . '</span></div>';
                echo '<p class="premblpa-pay-message">' . esc_html(__('The execution of your payment transaction is no longer possible, because your session has expired. Please create a new order.', 'premium-black-payment-for-woocommerce')) . '</p>';
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

            $mailer->send($order->get_billing_email(), wp_strip_all_tags($subject), $message);
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

                    echo "<p><a class='button' href='https://premium.black/status/?id=" . esc_html($transactionId) . "&k=" . esc_html($transactionKey) . "' target='_blank'>" . esc_html(__('Pay now/See status', 'premium-black-payment-for-woocommerce')) . '</a></p>';

                    return;
                } else {
                    $orderUrl = $order->get_checkout_order_received_url();

                    echo "<p><a class='button' href='" . esc_url($orderUrl) . "'>" . esc_html(__('Pay now/See status', 'premium-black-payment-for-woocommerce')) . '</a></p>';
                }
            } elseif ($order->has_status('processing')) {
                echo '<p>' . esc_html(__('Your payment request has been approved.', 'premium-black-payment-for-woocommerce')) . '</p>';
            }
        }
    }
}

