<?php
/**
 * Premium Black Onboarding Wizard
 */


add_action('admin_menu', function () {
    $public_key = get_option('woocommerce_premium_black_settings')['public_key'];
    $private_key = get_option('woocommerce_premium_black_settings')['private_key'];

    if (empty($public_key) || !empty($private_key)) {
        add_menu_page(
            __('Premium Black Setup', 'woocommerce-gateway-premium-black'),
            __('Premium Black Setup', 'woocommerce-gateway-premium-black'),
            'manage_options',
            'premium-black-onboarding',
            'premium_black_onboarding_page',
            'dashicons-admin-generic',
            3
        );
    }
});

add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook !== 'toplevel_page_premium-black-onboarding') {
        return;
    }

    wp_enqueue_style('premium-black-onboarding', plugins_url('assets/onboarding.css', __FILE__));
    wp_enqueue_script('premium-black-onboarding', plugins_url('assets/onboarding.js', __FILE__), ['jquery'], null, true);
    wp_localize_script('premium-black-onboarding', 'PremiumBlackOnboarding', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('pb_onboarding_nonce')
    ]);
});





function premium_black_onboarding_page()
{
    $public_key = get_option('woocommerce_premium_black_settings')['public_key'];
    $private_key = get_option('woocommerce_premium_black_settings')['private_key'];

    ?>

    <div id="pb-notification" class="notice notice-success is-dismissible" style="display:none;">
        <p>API keys validated successfully.</p>
    </div>


    <div class="wrap premium-black-onboarding">
        <h1>🚀 Premium Black: Setup Wizard</h1>
        <div class="pb-progress">
            <div class="pb-progress-bar" style="width: 33%"></div>
        </div>
        <div class="pb-tabs">
            <ul class="pb-tab-nav">
                <li class="active" data-step="1">1. API Keys</li>
                <li data-step="2">2. Choose currencies</li>
            </ul>
            <div class="pb-tab-content active" data-step="1">
                <div class="pb-onboarding-step1">
                    <p>Please enter your public and private keys.</p>
                    <div class="pb-api-form">
                        <label for="pb_public_key">Public Key</label>
                        <input type="text" id="pb_public_key" placeholder="Enter your public key" value="<?php echo esc_js($public_key) ?>" class="pb-input" />

                        <label for="pb_private_key">Private Key</label>
                        <input type="text" id="pb_private_key" placeholder="Enter your private key" value="<?php echo esc_js($private_key) ?>" class="pb-input" />

                        <div id="pb_step1_error" class="notice notice-error" style="display:none;"></div>
                        <div id="pb_step1_loader" style="display:none;">Checking keys...</div>
                        <button class="button button-primary" id="pb-step1-next">Next</button>
                    </div>
                    <br /><br />
                    <div class="pb-api-register">
                        <h3>Not registered yet?</h3>
                        <p>Start now and integrate Premium Black into your shop in just a few steps.</p>
                        <a href="https://premium.black/register/" target="_blank" class="button">Register now</a>
                        <a href="https://premium.black/" target="_blank" class="button button-secondary">Learn more</a>
                    </div>
                </div>
            </div>
            <div class="pb-tab-content" data-step="2">
                <p>Which currencies do you want accept?:</p>
                <div id="pb_step2_currency_list">
                    <div class="pb-currency-controls">
                        <button type="button" id="pb_select_all" class="button">Select All</button>
                        <button type="button" id="pb_select_none" class="button">Select None</button>
                    </div>
                    <div id="pb_currency_checkboxes" class="pb-currency-list"></div>
                    <div id="pb_step2_error" class="notice notice-error" style="display:none;">
                        <p>Please select at least one currency to proceed.</p>
                    </div>
                </div>
                <br /><br />
                <button class="button prev-step">Back</button>
                <button class="button button-primary" id="pb-finish">Finish</button>
            </div>

            <div class="pb-tab-content" data-step="3">
                <p>✅ Setup complete! You can now use Premium Black.</p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=premium_black')); ?>" class="button button-primary">Go to Settings</a>
            </div>
        </div>
    </div>
    <?php
}

// AJAX handler to validate keys
add_action('wp_ajax_pb_validate_keys', function () {

    check_ajax_referer('pb_onboarding_nonce', 'nonce');

    $public = sanitize_text_field(wp_unslash($_POST['public'] ?? ''));
    $private = sanitize_text_field(wp_unslash($_POST['private'] ?? ''));

    if (empty($public) || empty($private)) {
        wp_send_json_error(['message' => 'Both keys are required.']);
    }


    $api = new payAPI(false);
    $api->setPublicKey($public);
    $api->setPrivateKey($private);

    $cc = new GetConfigurationsRequest();

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


add_action("wp_ajax_pb_save_onboarding_data", function () {

    check_ajax_referer('pb_onboarding_nonce', 'nonce');


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

    $options['currencies'] = $_POST['currencies'] ?? '';
     
    update_option('woocommerce_premium_black_settings', $options, false);

    wp_send_json_success(['message' => 'Onboarding finished']);

});