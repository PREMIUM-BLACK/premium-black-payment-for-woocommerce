<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class PremiumBlackSettings {
	private $premium_black_settings_options;

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'premium_black_settings_add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'premium_black_settings_page_init' ) );
	}

	public function premium_black_settings_add_plugin_page() {
		add_options_page(
			'Premium Black Settings', // page_title
			'Premium Black', // menu_title
			'manage_options', // capability
			'premium_black_settings', // menu_slug
			array( $this, 'premium_black_settings_create_admin_page' ) // function
		);
	}

	public function premium_black_settings_create_admin_page() {
		$this->premium_black_settings_options = get_option( 'woocommerce_premium_black_settings' ); ?>

		<style>
			.pb-wrap { max-width: 900px; }
			.pb-header { background: linear-gradient(135deg, #1e1e2f 0%, #2d2d44 100%); border-radius: 8px; padding: 28px 32px; margin-bottom: 24px; display: flex; align-items: center; gap: 20px; }
			.pb-header .pb-icon { background: rgba(255,255,255,0.1); border-radius: 12px; width: 56px; height: 56px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
			.pb-header .pb-icon .dashicons { font-size: 28px; width: 28px; height: 28px; color: #f0c674; }
			.pb-header h1 { color: #fff; font-size: 22px; margin: 0 0 4px 0; font-weight: 600; }
			.pb-header p { color: rgba(255,255,255,0.7); margin: 0; font-size: 13px; }

			.pb-wrap .pb-form h2 {
				background: #fff; margin: 24px 0 0 0; padding: 16px 20px; font-size: 15px; font-weight: 600;
				border: 1px solid #dcdcde; border-bottom: none; border-radius: 8px 8px 0 0; color: #1d2327;
				display: flex; align-items: center; gap: 8px;
			}
			.pb-wrap .pb-form h2 .dashicons { color: #8c8f94; font-size: 18px; width: 18px; height: 18px; }
			.pb-wrap .pb-form .pb-section-desc { background: #fff; border: 1px solid #dcdcde; border-top: none; border-bottom: none; padding: 4px 20px 0; margin: 0; color: #646970; font-size: 13px; }
			.pb-wrap .pb-form .form-table { background: #fff; border: 1px solid #dcdcde; border-top: none; border-radius: 0 0 8px 8px; margin-top: 0; padding: 0 8px 8px; }
			.pb-wrap .pb-form .form-table th { font-weight: 500; color: #1d2327; padding-left: 14px; }
			.pb-wrap .pb-form .form-table td { padding-top: 16px; padding-bottom: 16px; }
			.pb-wrap .pb-form .form-table tr:not(:last-child) td,
			.pb-wrap .pb-form .form-table tr:not(:last-child) th { border-bottom: 1px solid #f0f0f1; }

			.pb-wrap .pb-form .form-table input.regular-text { border-radius: 4px; }

			/* Radio toggle style */
			.pb-toggle { display: inline-flex; gap: 0; border: 1px solid #8c8f94; border-radius: 4px; overflow: hidden; }
			.pb-toggle label { display: flex; align-items: center; padding: 5px 14px; cursor: pointer; font-size: 13px; color: #50575e; transition: background .15s, color .15s; border-right: 1px solid #8c8f94; margin: 0; }
			.pb-toggle label:last-child { border-right: none; }
			.pb-toggle input[type="radio"] { display: none; }
			.pb-toggle input[type="radio"]:checked + span { font-weight: 600; }
			.pb-toggle label:has(input:checked) { background: #2271b1; color: #fff; }

			/* Currency grid */
			.pb-currency-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 8px; margin-top: 8px; }
			.pb-currency-item { display: flex; align-items: center; gap: 8px; padding: 8px 12px; border: 1px solid #dcdcde; border-radius: 6px; background: #f9f9f9; transition: border-color .15s, background .15s; }
			.pb-currency-item:hover { border-color: #2271b1; background: #f0f6fc; }
			.pb-currency-item input[type="checkbox"] { margin: 0; }
			.pb-currency-item label { margin: 0; cursor: pointer; font-size: 13px; }
			.pb-blockchain-heading { font-size: 14px; font-weight: 600; color: #1d2327; margin: 16px 0 4px; padding-bottom: 4px; border-bottom: 2px solid #f0c674; display: inline-block; }
			.pb-currency-count { background: #f0c674; color: #1e1e2f; padding: 2px 10px; border-radius: 10px; font-size: 12px; font-weight: 600; }

			.pb-notice { padding: 10px 14px; border-left: 4px solid #dba617; background: #fff8e5; border-radius: 0 4px 4px 0; margin: 4px 0; }
			.pb-notice.pb-notice-error { border-left-color: #d63638; background: #fcf0f1; }

			.pb-wrap .submit { margin-top: 20px; }
			.pb-wrap .submit .button-primary { padding: 4px 24px; border-radius: 4px; }

			/* Dashboard link in header */
			.pb-header { flex-wrap: wrap; }
			.pb-header-content { flex: 1; min-width: 200px; }
			.pb-header .pb-dash-link { display: inline-flex; align-items: center; gap: 6px; background: rgba(240,198,116,0.15); color: #f0c674; border: 1px solid rgba(240,198,116,0.3); padding: 8px 18px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 500; transition: background .15s, border-color .15s; white-space: nowrap; }
			.pb-header .pb-dash-link:hover { background: rgba(240,198,116,0.25); border-color: rgba(240,198,116,0.5); color: #f0c674; }
			.pb-header .pb-dash-link .dashicons { font-size: 16px; width: 16px; height: 16px; }

			/* Inline dashboard link */
			.pb-section-desc a.pb-inline-link { color: #2271b1; text-decoration: none; font-weight: 500; }
			.pb-section-desc a.pb-inline-link:hover { text-decoration: underline; }

			/* Footer dashboard link */
			.pb-footer { margin-top: 16px; padding: 14px 20px; background: #f6f7f7; border: 1px solid #dcdcde; border-radius: 8px; display: flex; align-items: center; gap: 8px; color: #646970; font-size: 13px; }
			.pb-footer .dashicons { color: #8c8f94; font-size: 16px; width: 16px; height: 16px; }
			.pb-footer a { color: #2271b1; text-decoration: none; font-weight: 500; }
			.pb-footer a:hover { text-decoration: underline; }
		</style>

		<div class="wrap pb-wrap">
			<div class="pb-header">
				<div class="pb-icon"><span class="dashicons dashicons-money-alt"></span></div>
				<div class="pb-header-content">
					<h1>Premium Black Settings</h1>
					<p>Configure your cryptocurrency payment gateway for WooCommerce.</p>
				</div>
				<a href="https://dash.premium.black" target="_blank" rel="noopener noreferrer" class="pb-dash-link"><span class="dashicons dashicons-external"></span> Merchant Dashboard</a>
			</div>

			<?php settings_errors(); ?>

			<form method="post" action="options.php" class="pb-form">
				<?php
					settings_fields( 'premium_black_settings_option_group' );
					do_settings_sections( 'premium-black-settings-admin' );
					submit_button( 'Save Settings' );
				?>
			</form>

			<div class="pb-footer">
				<span class="dashicons dashicons-admin-links"></span>
				Manage your account, transactions and API keys in the <a href="https://dash.premium.black" target="_blank" rel="noopener noreferrer">Premium Black Merchant Dashboard &rarr;</a>
			</div>
		</div>
	<?php }

	public function premium_black_settings_page_init() {
		register_setting(
			'premium_black_settings_option_group', // option_group
			'woocommerce_premium_black_settings', // option_name
			array( $this, 'premium_black_settings_sanitize' ) // sanitize_callback
		);

        add_settings_section(
            'premium_black_settings_payment_section', // id
            '<span class="dashicons dashicons-cart"></span> Payment Settings', // title
            array( $this, 'payment_section_callback' ), // callback
            'premium-black-settings-admin' // page
        );

        add_settings_field(
            'enabled', // id
            'Enabled', // title
            array($this, 'enabled_callback'), // callback
            'premium-black-settings-admin', // page
            'premium_black_settings_payment_section' // section
        );

        add_settings_field(
            'title', // id
            'Title', // title
            array($this, 'title_callback'), // callback
            'premium-black-settings-admin', // page
            'premium_black_settings_payment_section' // section
        );

        add_settings_field(
            'description', // id
            'Description', // title
            array($this, 'description_callback'), // callback
            'premium-black-settings-admin', // page
            'premium_black_settings_payment_section' // section
        );

        add_settings_field(
            'instructions', // id
            'Instructions', // title
            array($this, 'instructions_callback'), // callback
            'premium-black-settings-admin', // page
            'premium_black_settings_payment_section' // section
        );

        add_settings_field(
            'enable_external_status_page', // id
            'Show Status Page Link', // title
            array($this, 'enable_external_status_page_callback'), // callback
            'premium-black-settings-admin', // page
            'premium_black_settings_payment_section' // section
        );

		add_settings_section(
			'premium_black_settings_apisetting_section', // id
			'<span class="dashicons dashicons-admin-network"></span> API Settings', // title
			array( $this, 'api_section_callback' ), // callback
			'premium-black-settings-admin' // page
		);

		add_settings_field(
			'public_key', // id
			'Public Key', // title
			array( $this, 'public_key_callback' ), // callback
			'premium-black-settings-admin', // page
			'premium_black_settings_apisetting_section' // section
		);

		add_settings_field(
			'private_key', // id
			'Private Key', // title
			array( $this, 'private_key_callback' ), // callback
			'premium-black-settings-admin', // page
			'premium_black_settings_apisetting_section' // section
		);

		add_settings_section(
			'premium_black_settings_currency_setting_section', // id
			'<span class="dashicons dashicons-money-alt"></span> Currency Settings', // title
			array( $this, 'currency_section_callback' ), // callback
			'premium-black-settings-admin' // page
		);

		
		add_settings_field(
			'currencies', // id
			'Available Currencies', // title
			array( $this, 'currencies_callback' ), // callback
			'premium-black-settings-admin', // page
			'premium_black_settings_currency_setting_section' // section
		);

        add_settings_section(
            'premium_black_settings_development_setting_section', // id
            '<span class="dashicons dashicons-code-standards"></span> Development Settings', // title
            array( $this, 'development_section_callback' ), // callback
            'premium-black-settings-admin' // page
        );

        add_settings_field(
            'debug', // id
            'Debug Mode', // title
            array($this, 'debug_mode_callback'), // callback
            'premium-black-settings-admin', // page
            'premium_black_settings_development_setting_section' // section
        );

    }

	public function payment_section_callback() {
		echo '<p class="pb-section-desc">General payment gateway configuration and display settings.</p>';
	}

	public function api_section_callback() {
		echo '<p class="pb-section-desc">Enter your Premium Black API credentials to connect the payment gateway. You can find your keys in the <a href="https://dash.premium.black" target="_blank" rel="noopener noreferrer" class="pb-inline-link">Merchant Dashboard &#8599;</a>.</p>';
	}

	public function currency_section_callback() {
		echo '<p class="pb-section-desc">Select which cryptocurrencies you want to accept as payment.</p>';
	}

	public function development_section_callback() {
		echo '<p class="pb-section-desc">Options for debugging and development purposes.</p>';
	}

	public function premium_black_settings_sanitize($input) {
		$sanitary_values = array();

        if (isset($input['enabled'])) {
            $sanitary_values['enabled'] = $input['enabled'];
        }

        if (isset($input['title'])) {
            $sanitary_values['title'] = sanitize_text_field($input['title']);
        }

        if (isset($input['description'])) {
            $sanitary_values['description'] = sanitize_text_field($input['description']);
        }

        if (isset($input['instructions'])) {
            $sanitary_values['instructions'] = sanitize_text_field($input['instructions']);
        }

        if (isset($input['enable_external_status_page'])) {
            $sanitary_values['enable_external_status_page'] = $input['enable_external_status_page'];
        }

		if ( isset( $input['private_key'] ) ) {
			$sanitary_values['private_key'] = sanitize_text_field( $input['private_key'] );
		}

		if ( isset( $input['public_key'] ) ) {
			$sanitary_values['public_key'] = sanitize_text_field( $input['public_key'] );
		}

		if ( isset( $input['currencies'] ) ) {
			$sanitary_values['currencies'] = $input['currencies'];
		}

        if (isset($input['debug'])) {
            $sanitary_values['debug'] = $input['debug'];
        }

		if(isset($input['all_currencies'])){
            $sanitary_values['all_currencies'] = $input['all_currencies'];
        }

        if (isset($input['blockchains'])) {
            $sanitary_values['blockchains'] = $input['blockchains'];
        }

		return $sanitary_values;
	}

	public function enabled_callback()
	{
		?> <div class="pb-toggle">
		<?php $checked = (isset($this->premium_black_settings_options['enabled']) && $this->premium_black_settings_options['enabled'] === 'yes') ? 'checked' : ''; ?>
		<label for="enabled_0-0"><input type="radio" name="woocommerce_premium_black_settings[enabled]" id="enabled_0-0" value="yes" <?php echo esc_html($checked); ?>><span>Yes</span></label>
		<?php $checked = (isset($this->premium_black_settings_options['enabled']) && $this->premium_black_settings_options['enabled'] === 'no') ? 'checked' : ''; ?>
		<label for="enabled_0-1"><input type="radio" name="woocommerce_premium_black_settings[enabled]" id="enabled_0-1" value="no" <?php echo esc_html($checked); ?>><span>No</span></label>
		</div> <?php
	}

    public function title_callback()
    {
        printf(
            '<input class="regular-text" type="text" name="woocommerce_premium_black_settings[title]" id="title" value="%s">',
            isset($this->premium_black_settings_options['title']) ? esc_attr($this->premium_black_settings_options['title']) : ''
        );
    }

    public function description_callback()
    {
        printf(
            '<input class="regular-text" type="text" name="woocommerce_premium_black_settings[description]" id="description" value="%s">',
            isset($this->premium_black_settings_options['description']) ? esc_attr($this->premium_black_settings_options['description']) : ''
        );
    }

    public function instructions_callback()
    {
        printf(
            '<input class="regular-text" type="text" name="woocommerce_premium_black_settings[instructions]" id="instructions" value="%s">',
            isset($this->premium_black_settings_options['instructions']) ? esc_attr($this->premium_black_settings_options['instructions']) : ''
        );
    }

	public function enable_external_status_page_callback()
	{
		?> <div class="pb-toggle">
		<?php $checked = (isset($this->premium_black_settings_options['enable_external_status_page']) && $this->premium_black_settings_options['enable_external_status_page'] === 'yes') ? 'checked' : ''; ?>
		<label for="enable_external_status_page_0-0"><input type="radio" name="woocommerce_premium_black_settings[enable_external_status_page]" id="enable_external_status_page_0-0" value="yes" <?php echo esc_html($checked); ?>><span>Yes</span></label>
		<?php $checked = (isset($this->premium_black_settings_options['enable_external_status_page']) && $this->premium_black_settings_options['enable_external_status_page'] === 'no') ? 'checked' : ''; ?>
		<label for="enable_external_status_page_0-1"><input type="radio" name="woocommerce_premium_black_settings[enable_external_status_page]" id="enable_external_status_page_0-1" value="no" <?php echo esc_html($checked); ?>><span>No</span></label>
		</div> <?php
	}

    public function public_key_callback()
    {
        printf(
            '<input class="regular-text" type="text" name="woocommerce_premium_black_settings[public_key]" id="public_key" value="%s">',
            isset($this->premium_black_settings_options['public_key']) ? esc_attr($this->premium_black_settings_options['public_key']) : ''
        );
    }

	public function private_key_callback() {
		printf(
			'<input class="regular-text" type="password" name="woocommerce_premium_black_settings[private_key]" id="private_key" value="%s">',
			isset( $this->premium_black_settings_options['private_key'] ) ? esc_attr( $this->premium_black_settings_options['private_key']) : ''
		);
	}


	public function currencies_callback() {

		$public = isset($this->premium_black_settings_options['public_key']) ? $this->premium_black_settings_options['public_key'] : '';
		$private = isset($this->premium_black_settings_options['private_key']) ? $this->premium_black_settings_options['private_key'] : '';

		if(empty($public) | empty($private))
		{
			echo '<div class="pb-notice">Please set API credentials in the section above to load available currencies.</div>';
			return;
		}

        $debug = isset($this->premium_black_settings_options['debug']) && $this->premium_black_settings_options['debug'] === 'yes';
		$api = new payAPI($debug);
		$api->setPublicKey($public);
		$api->setPrivateKey($private);
	
		$cc = new GetConfigurationsRequest();

		$response = $api->GetConfigurations($cc);

		$valid = true;
		if($response === null || $response->Error != null || !$api->checkHash($response)){
			$valid = false;

			echo '<div class="pb-notice pb-notice-error">Error during currency retrieval.';

			if ($response->Error != null)
				echo ' <strong>Details:</strong> ' . esc_html( $response->Error );
			echo '</div>';
			return;
		}

        $options = get_option('woocommerce_premium_black_settings');

        $options['all_currencies'] = $response->Currencies;
        $options['blockchains'] = $response->Blockchains;

        update_option('woocommerce_premium_black_settings', $options, false);

		$selected_currencies = isset($this->premium_black_settings_options['currencies']) ? $this->premium_black_settings_options['currencies'] : [];

        if(!is_array($selected_currencies)){
            $selected_currencies = [];
        }

		printf('<p><span class="pb-currency-count">' . count($response->Currencies) . ' Currencies available</span> <span class="pb-currency-count">' . count($response->Blockchains) . ' Blockchains available</span></p>');

		foreach($response->Blockchains as $blockchain) {

			$blockchain_currencies = array_filter($response->Currencies, function($c) use ($blockchain) {
				return $c->Blockchain === $blockchain->Code;
			});

			printf('<div class="pb-blockchain-heading">' . esc_html($blockchain->Name) . '</div>');

			if(empty($blockchain_currencies)) {
				echo '<p class="pb-notice" style="margin: 4px 0 12px;">No currencies configured for this blockchain.</p>';
				continue;
			}

			echo '<div class="pb-currency-grid">';

			foreach($blockchain_currencies as $currency) {
				printf('<div class="pb-currency-item"><input type="checkbox" name="woocommerce_premium_black_settings[currencies][' . esc_html($currency->CodeChain) . ']" id="currencies_' . esc_html($currency->CodeChain) . '" value="' . esc_html($currency->CodeChain) . '" %s> <label for="currencies_' . esc_html($currency->CodeChain) . '">' . esc_html($currency->Name) . ' (' . esc_html(strtoupper( $currency->Symbol)) .  ')</label></div>',
				( isset( $selected_currencies ) && in_array($currency->CodeChain, $selected_currencies)) ? 'checked' : ''
				);
			}

			echo '</div>';

		}


	}

	public function debug_mode_callback()
	{
		?> <div class="pb-toggle">
		<?php $checked = (isset($this->premium_black_settings_options['debug']) && $this->premium_black_settings_options['debug'] === 'yes') ? 'checked' : ''; ?>
		<label for="debug_mode_0-0"><input type="radio" name="woocommerce_premium_black_settings[debug]" id="debug_mode_0-0" value="yes" <?php echo esc_html($checked); ?>><span>Yes</span></label>
		<?php $checked = (isset($this->premium_black_settings_options['debug']) && $this->premium_black_settings_options['debug'] === 'no') ? 'checked' : ''; ?>
		<label for="debug_mode_0-1"><input type="radio" name="woocommerce_premium_black_settings[debug]" id="debug_mode_0-1" value="no" <?php echo esc_html($checked); ?>><span>No</span></label>
		</div> <?php
	}

}
if ( is_admin() )
	$premium_black_settings = new PremiumBlackSettings();
