<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_action('rest_api_init', function() {
    register_rest_route('premium-black/v1', '/webhook', [
        'methods' => ['GET', 'POST'],
        'callback' => 'premium_black_handle_webhook',
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
    ]);
});

function premium_black_handle_webhook(WP_REST_Request $request)
{
    $status = sanitize_text_field($request->get_param('action'));
    $transactionId = sanitize_text_field($request->get_param('tx'));

    // Gateway-Instanz holen
    //$gateway = function_exists('wc_gateway_premium_black_instance') ? wc_gateway_premium_black_instance() : new WC_Gateway_Premium_Black();

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
            $order->add_order_note(__('Payment was confirmed by Premium Black.', 'premium-black-payment-for-woocommerce'));
            break;

        case 'canceled':
            $order->update_status('cancelled', __('The transaction was cancelled.', 'premium-black-payment-for-woocommerce'));
            break;

        case 'timeout':
            $order->update_status('cancelled', __('The transaction timed out.', 'premium-black-payment-for-woocommerce'));
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