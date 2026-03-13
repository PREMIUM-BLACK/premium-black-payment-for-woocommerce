<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * Premium Black Onboarding Wizard
 */


add_action('admin_menu', function () {
    $public_key = get_option('woocommerce_premium_black_settings')['public_key'];
    $private_key = get_option('woocommerce_premium_black_settings')['private_key'];

    if (empty($public_key) || !empty($private_key)) {
        add_menu_page(
            __('Premium Black Setup', 'premium-black-payment-for-woocommerce'),
            __('Premium Black Setup', 'premium-black-payment-for-woocommerce'),
            'manage_options',
            'premblpa-onboarding',
            'premblpa_onboarding_page',
            'dashicons-admin-generic',
            3
        );
    }
});

add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook !== 'toplevel_page_premblpa-onboarding') {
        return;
    }

    wp_enqueue_style('premblpa-onboarding-css', plugins_url('assets/onboarding.css', __FILE__));
    wp_enqueue_script('premblpa-onboarding-js', plugins_url('assets/onboarding.js', __FILE__), ['jquery'], null, true);
    wp_localize_script('premblpa-onboarding-js', 'PremblpaOnboarding', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('premblpa_onboarding_nonce')
    ]);
});





function premblpa_onboarding_page()
{
    $public_key = get_option('woocommerce_premium_black_settings')['public_key'];
    $private_key = get_option('woocommerce_premium_black_settings')['private_key'];

    ?>

    <div id="pb-notification" class="notice notice-success is-dismissible" style="display:none;">
        <p><?php echo esc_html(__('API keys validated successfully.', 'premium-black-payment-for-woocommerce')); ?></p>
    </div>

    <div class="wrap pb-onboarding-wrap">

        <div class="pb-header">
            <div class="pb-icon"><span class="dashicons dashicons-money-alt"></span></div>
            <div class="pb-header-content">
                <h1><?php echo esc_html(__('Premium Black: Setup Wizard', 'premium-black-payment-for-woocommerce')); ?></h1>
                <p><?php echo esc_html(__('Get started in a few simple steps to accept crypto payments.', 'premium-black-payment-for-woocommerce')); ?></p>
            </div>
            <a href="https://dash.premium.black" target="_blank" rel="noopener noreferrer" class="pb-dash-link"><span class="dashicons dashicons-external"></span> Merchant Dashboard</a>
        </div>

        <div class="pb-progress">
            <div class="pb-progress-bar" style="width: 33%"></div>
        </div>

        <ul class="pb-tab-nav">
            <li class="active" data-step="1"><span class="dashicons dashicons-admin-network"></span> 1. API Keys</li>
            <li data-step="2"><span class="dashicons dashicons-money-alt"></span> 2. Currencies</li>
            <li data-step="3"><span class="dashicons dashicons-yes-alt"></span> 3. Done</li>
        </ul>

        <!-- Step 1: API Keys -->
        <div class="pb-tab-content active" data-step="1">
            <div class="pb-card">
                <h2><span class="dashicons dashicons-admin-network"></span> <?php echo esc_html(__('Enter your API credentials', 'premium-black-payment-for-woocommerce')); ?></h2>
                <p><?php echo esc_html(__('Connect your Premium Black merchant account by entering your public and private keys.', 'premium-black-payment-for-woocommerce')); ?></p>
                <div class="pb-onboarding-step1">
                    <div class="pb-api-form">
                        <label for="pb_public_key"><?php echo esc_html(__('Public Key', 'premium-black-payment-for-woocommerce')); ?></label>
                        <input type="text" id="pb_public_key" placeholder="<?php echo esc_attr(__('Enter your public key', 'premium-black-payment-for-woocommerce')); ?>" value="<?php echo esc_attr($public_key); ?>" class="pb-input" />

                        <label for="pb_private_key"><?php echo esc_html(__('Private Key', 'premium-black-payment-for-woocommerce')); ?></label>
                        <input type="password" id="pb_private_key" placeholder="<?php echo esc_attr(__('Enter your private key', 'premium-black-payment-for-woocommerce')); ?>" value="<?php echo esc_attr($private_key); ?>" class="pb-input" />

                        <div id="pb_step1_error" class="pb-inline-error" style="display:none;"></div>
                        <div id="pb_step1_loader" class="pb-inline-loader" style="display:none;"><span class="spinner is-active" style="float:none;margin:0;"></span> <?php echo esc_html(__('Checking keys...', 'premium-black-payment-for-woocommerce')); ?></div>

                        <div class="pb-step-actions">
                            <button class="button button-primary" id="pb-step1-next"><?php echo esc_html(__('Validate & Continue', 'premium-black-payment-for-woocommerce')); ?></button>
                        </div>
                    </div>

                    <div class="pb-api-register">
                        <h3><?php echo esc_html(__('Not registered yet?', 'premium-black-payment-for-woocommerce')); ?></h3>
                        <p><?php echo esc_html(__('Start now and integrate Premium Black into your shop in just a few steps.', 'premium-black-payment-for-woocommerce')); ?></p>
                        <a href="https://dash.premium.black/register/" target="_blank" class="button"><?php echo esc_html(__('Register now', 'premium-black-payment-for-woocommerce')); ?></a>
                        <a href="https://premium.black/" target="_blank" class="button button-secondary"><?php echo esc_html(__('Learn more', 'premium-black-payment-for-woocommerce')); ?></a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 2: Currencies -->
        <div class="pb-tab-content" data-step="2">
            <div class="pb-card">
                <h2><span class="dashicons dashicons-money-alt"></span> <?php echo esc_html(__('Choose currencies', 'premium-black-payment-for-woocommerce')); ?></h2>
                <p><?php echo esc_html(__('Select which cryptocurrencies you want to accept as payment.', 'premium-black-payment-for-woocommerce')); ?></p>
                <div id="pb_step2_currency_list">
                    <div class="pb-currency-controls">
                        <button type="button" id="pb_select_all" class="button"><?php echo esc_html(__('Select All', 'premium-black-payment-for-woocommerce')); ?></button>
                        <button type="button" id="pb_select_none" class="button"><?php echo esc_html(__('Select None', 'premium-black-payment-for-woocommerce')); ?></button>
                    </div>
                    <div id="pb_currency_checkboxes" class="pb-currency-list"></div>
                    <div id="pb_step2_error" class="pb-step-error" style="display:none;">
                        <?php echo esc_html(__('Please select at least one currency to proceed.', 'premium-black-payment-for-woocommerce')); ?>
                    </div>
                </div>
                <div id="pb_step2_loader" class="pb-inline-loader" style="display:none;"><span class="spinner is-active" style="float:none;margin:0;"></span> <?php echo esc_html(__('Saving configuration...', 'premium-black-payment-for-woocommerce')); ?></div>
                <div class="pb-step-actions">
                    <button class="button prev-step">&larr; <?php echo esc_html(__('Back', 'premium-black-payment-for-woocommerce')); ?></button>
                    <button class="button button-primary" id="pb-finish"><?php echo esc_html(__('Finish Setup', 'premium-black-payment-for-woocommerce')); ?></button>
                </div>
            </div>
        </div>

        <!-- Step 3: Done -->
        <div class="pb-tab-content" data-step="3">
            <div class="pb-card">
                <div class="pb-success">
                    <div class="pb-success-icon">&#10003;</div>
                    <h2><?php echo esc_html(__('Setup complete!', 'premium-black-payment-for-woocommerce')); ?></h2>
                    <p><?php echo esc_html(__('Premium Black is ready. You can now accept crypto payments in your store.', 'premium-black-payment-for-woocommerce')); ?></p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=premblpa_settings')); ?>" class="button button-primary"><?php echo esc_html(__('Go to Settings', 'premium-black-payment-for-woocommerce')); ?></a>
                </div>
            </div>
        </div>

        <div class="pb-footer">
            <span class="dashicons dashicons-admin-links"></span>
            <?php echo esc_html(__('Manage your account, transactions and API keys in the', 'premium-black-payment-for-woocommerce')); ?>
            <a href="https://dash.premium.black" target="_blank" rel="noopener noreferrer">Premium Black Merchant Dashboard &rarr;</a>
        </div>

    </div>
    <?php
}

// AJAX handler to validate keys
add_action('wp_ajax_premblpa_validate_keys', function () {

    check_ajax_referer('premblpa_onboarding_nonce', 'nonce');

    $public = sanitize_text_field(wp_unslash($_POST['public'] ?? ''));
    $private = sanitize_text_field(wp_unslash($_POST['private'] ?? ''));

    if (empty($public) || empty($private)) {
        wp_send_json_error(['message' => 'Both keys are required.']);
    }


    $api = new Premblpa_Pay_API(false);
    $api->setPublicKey($public);
    $api->setPrivateKey($private);

    $cc = new Premblpa_GetConfigurationsRequest();

    $response = $api->GetConfigurations($cc);

    $valid = true;
    if ($response === null || $response->Error != null || !$api->checkHash($response)) {
        $valid = false;
    }

    if (!$valid) {
        if ($response != null) {
            wp_send_json_error(['message' => 'Error:' . $response->Error]);
        } else {
            wp_send_json_error(['message' => 'Error during API call']);
        }
        return;
    }

    //Save into settings
    $options = get_option('woocommerce_premium_black_settings');

    $options['all_currencies'] = $response->Currencies;
    $options['blockchains'] = $response->Blockchains;

    update_option('woocommerce_premium_black_settings', $options, false);

    wp_send_json_success(['message' => 'API keys are valid.', 'currencies' => $response->Currencies, 'blockchains' => $response->Blockchains]);
});


add_action("wp_ajax_premblpa_save_onboarding_data", function () {

    check_ajax_referer('premblpa_onboarding_nonce', 'nonce');


    $public = sanitize_text_field(wp_unslash($_POST['public_key'] ?? ''));
    $private = sanitize_text_field(wp_unslash($_POST['private_key'] ?? ''));

    //Save into settings
    $options = get_option('woocommerce_premium_black_settings');

    $options['enabled'] = 'yes';
    $options['enable_external_status_page'] = 'yes';
    $options['public_key'] = $public;
    $options['private_key'] = $private;
    $options['debug'] = 'no';
    $options['title'] = 'Premium Black - pay with crypto';
    $options['description'] = 'Choose your preferred currency:';
    $options['instructions'] = 'Please pay the amount.';

    $currencies = isset($_POST['currencies']) ? (array) $_POST['currencies'] : array();
    $options['currencies'] = array_map('sanitize_text_field', $currencies);
     
    update_option('woocommerce_premium_black_settings', $options, false);

    wp_send_json_success(['message' => 'Onboarding finished']);

});