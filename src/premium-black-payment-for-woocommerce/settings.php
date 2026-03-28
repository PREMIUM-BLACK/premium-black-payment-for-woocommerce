<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Premblpa_Settings {
	private $premium_black_settings_options;

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'premium_black_settings_add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'premium_black_settings_page_init' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'premium_black_enqueue_admin_assets' ) );
	}

	public function premium_black_settings_add_plugin_page() {
		add_options_page(
			'Premium Black Settings', // page_title
			'Premium Black', // menu_title
			'manage_options', // capability
			'premblpa_settings', // menu_slug
			array( $this, 'premium_black_settings_create_admin_page' ) // function
		);
	}

	public function premium_black_settings_create_admin_page() {
		$this->premium_black_settings_options = get_option( 'premblpa_settings' ); ?>

		<div class="wrap pb-wrap">
			<div class="pb-header">
				<div class="pb-icon"><span class="dashicons dashicons-money-alt"></span></div>
				<div class="pb-header-content">
					<h1>Premium Black Settings</h1>
					<p>Configure your cryptocurrency payment gateway for WooCommerce.</p>
				</div>
				<a href="https://dash.premium.black" target="_blank" rel="noopener noreferrer" class="pb-dash-link"><span class="dashicons dashicons-external"></span> Merchant Dashboard</a>
			</div>

			<form method="post" action="options.php" class="pb-form">
				<?php
					settings_fields( 'premblpa_settings_option_group' );
					do_settings_sections( 'premblpa-settings-admin' );
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
			'premblpa_settings_option_group', // option_group
			'premblpa_settings', // option_name
			array( $this, 'premium_black_settings_sanitize' ) // sanitize_callback
		);

        add_settings_section(
            'premblpa_payment_section', // id
            '<span class="dashicons dashicons-cart"></span> Payment Settings', // title
            array( $this, 'payment_section_callback' ), // callback
            'premblpa-settings-admin' // page
        );

        add_settings_field(
            'enabled', // id
            'Enabled', // title
            array($this, 'enabled_callback'), // callback
            'premblpa-settings-admin', // page
            'premblpa_payment_section' // section
        );

        add_settings_field(
            'title', // id
            'Title', // title
            array($this, 'title_callback'), // callback
            'premblpa-settings-admin', // page
            'premblpa_payment_section' // section
        );

        add_settings_field(
            'description', // id
            'Description', // title
            array($this, 'description_callback'), // callback
            'premblpa-settings-admin', // page
            'premblpa_payment_section' // section
        );

        add_settings_field(
            'instructions', // id
            'Instructions', // title
            array($this, 'instructions_callback'), // callback
            'premblpa-settings-admin', // page
            'premblpa_payment_section' // section
        );

        add_settings_field(
            'enable_external_status_page', // id
            'Show Status Page Link', // title
            array($this, 'enable_external_status_page_callback'), // callback
            'premblpa-settings-admin', // page
            'premblpa_payment_section' // section
        );

		add_settings_section(
			'premblpa_apisetting_section', // id
			'<span class="dashicons dashicons-admin-network"></span> API Settings', // title
			array( $this, 'api_section_callback' ), // callback
			'premblpa-settings-admin' // page
		);

		add_settings_field(
			'public_key', // id
			'Public Key', // title
			array( $this, 'public_key_callback' ), // callback
			'premblpa-settings-admin', // page
			'premblpa_apisetting_section' // section
		);

		add_settings_field(
			'private_key', // id
			'Private Key', // title
			array( $this, 'private_key_callback' ), // callback
			'premblpa-settings-admin', // page
			'premblpa_apisetting_section' // section
		);

		add_settings_section(
			'premblpa_currency_section', // id
			'<span class="dashicons dashicons-money-alt"></span> Currency Settings', // title
			array( $this, 'currency_section_callback' ), // callback
			'premblpa-settings-admin' // page
		);

		
		add_settings_field(
			'currencies', // id
			'Available Currencies', // title
			array( $this, 'currencies_callback' ), // callback
			'premblpa-settings-admin', // page
			'premblpa_currency_section' // section
		);

        add_settings_section(
            'premblpa_development_section', // id
            '<span class="dashicons dashicons-code-standards"></span> Development Settings', // title
            array( $this, 'development_section_callback' ), // callback
            'premblpa-settings-admin' // page
        );

        add_settings_field(
            'debug', // id
            'Debug Mode', // title
            array($this, 'debug_mode_callback'), // callback
            'premblpa-settings-admin', // page
            'premblpa_development_section' // section
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
		<label for="enabled_0-0"><input type="radio" name="premblpa_settings[enabled]" id="enabled_0-0" value="yes" <?php echo esc_html($checked); ?>><span>Yes</span></label>
		<?php $checked = (isset($this->premium_black_settings_options['enabled']) && $this->premium_black_settings_options['enabled'] === 'no') ? 'checked' : ''; ?>
		<label for="enabled_0-1"><input type="radio" name="premblpa_settings[enabled]" id="enabled_0-1" value="no" <?php echo esc_html($checked); ?>><span>No</span></label>
		</div> <?php
	}

    public function title_callback()
    {
        printf(
            '<input class="regular-text" type="text" name="premblpa_settings[title]" id="title" value="%s">',
            isset($this->premium_black_settings_options['title']) ? esc_attr($this->premium_black_settings_options['title']) : ''
        );
    }

    public function description_callback()
    {
        printf(
            '<input class="regular-text" type="text" name="premblpa_settings[description]" id="description" value="%s">',
            isset($this->premium_black_settings_options['description']) ? esc_attr($this->premium_black_settings_options['description']) : ''
        );
    }

    public function instructions_callback()
    {
        printf(
            '<input class="regular-text" type="text" name="premblpa_settings[instructions]" id="instructions" value="%s">',
            isset($this->premium_black_settings_options['instructions']) ? esc_attr($this->premium_black_settings_options['instructions']) : ''
        );
    }

	public function enable_external_status_page_callback()
	{
		?> <div class="pb-toggle">
		<?php $checked = (isset($this->premium_black_settings_options['enable_external_status_page']) && $this->premium_black_settings_options['enable_external_status_page'] === 'yes') ? 'checked' : ''; ?>
		<label for="enable_external_status_page_0-0"><input type="radio" name="premblpa_settings[enable_external_status_page]" id="enable_external_status_page_0-0" value="yes" <?php echo esc_html($checked); ?>><span>Yes</span></label>
		<?php $checked = (isset($this->premium_black_settings_options['enable_external_status_page']) && $this->premium_black_settings_options['enable_external_status_page'] === 'no') ? 'checked' : ''; ?>
		<label for="enable_external_status_page_0-1"><input type="radio" name="premblpa_settings[enable_external_status_page]" id="enable_external_status_page_0-1" value="no" <?php echo esc_html($checked); ?>><span>No</span></label>
		</div> <?php
	}

    public function public_key_callback()
    {
        printf(
            '<input class="regular-text" type="text" name="premblpa_settings[public_key]" id="public_key" value="%s">',
            isset($this->premium_black_settings_options['public_key']) ? esc_attr($this->premium_black_settings_options['public_key']) : ''
        );
    }

	public function private_key_callback() {
		printf(
			'<input class="regular-text" type="password" name="premblpa_settings[private_key]" id="private_key" value="%s">',
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
		$api = new Premblpa_Pay_API($debug);
		$api->setPublicKey($public);
		$api->setPrivateKey($private);
	
		$cc = new Premblpa_GetConfigurationsRequest();

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

        $options = get_option('premblpa_settings');

        $options['all_currencies'] = $response->Currencies;
        $options['blockchains'] = $response->Blockchains;

        update_option('premblpa_settings', $options, false);

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
				printf('<div class="pb-currency-item"><input type="checkbox" name="premblpa_settings[currencies][' . esc_html($currency->CodeChain) . ']" id="currencies_' . esc_html($currency->CodeChain) . '" value="' . esc_html($currency->CodeChain) . '" %s> <label for="currencies_' . esc_html($currency->CodeChain) . '">' . esc_html($currency->Name) . ' (' . esc_html(strtoupper( $currency->Symbol)) .  ')</label></div>',
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
		<label for="debug_mode_0-0"><input type="radio" name="premblpa_settings[debug]" id="debug_mode_0-0" value="yes" <?php echo esc_html($checked); ?>><span>Yes</span></label>
		<?php $checked = (isset($this->premium_black_settings_options['debug']) && $this->premium_black_settings_options['debug'] === 'no') ? 'checked' : ''; ?>
		<label for="debug_mode_0-1"><input type="radio" name="premblpa_settings[debug]" id="debug_mode_0-1" value="no" <?php echo esc_html($checked); ?>><span>No</span></label>
		</div> <?php
	}

    function premium_black_enqueue_admin_assets($hook_suffix)
    {
        // Nur auf der eigenen Settings-Seite laden
        if ($hook_suffix !== 'settings_page_premblpa_settings') {
            return;
        }

        wp_enqueue_style(
            'premblpa-admin-css',
            plugin_dir_url(__FILE__) . 'assets/admin.css',
            array(),
            '1.1.4'
        );
    }

}
if ( is_admin() )
	$premblpa_settings = new Premblpa_Settings();

