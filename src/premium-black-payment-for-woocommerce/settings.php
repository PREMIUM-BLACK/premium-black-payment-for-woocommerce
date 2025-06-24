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

		<div class="wrap">
			<h2>Premium Black Settings</h2>
			<p></p>
			<?php settings_errors(); ?>

			<form method="post" action="options.php">
				<?php
					settings_fields( 'premium_black_settings_option_group' );
					do_settings_sections( 'premium-black-settings-admin' );
					submit_button();
				?>
			</form>
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
            'Payment Settings', // title
            null, // callback
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
			'API Settings', // title
			null, // callback
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
			'Currency Settings', // title
			null, // callback
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
            'Development Settings', // title
            null, // callback
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
        ?> <fieldset><?php $checked = (isset($this->premium_black_settings_options['enabled']) && $this->premium_black_settings_options['enabled'] === 'yes') ? 'checked' : ''; ?>
		<label for="enabled_0-0"><input type="radio" name="woocommerce_premium_black_settings[enabled]" id="enabled_0-0" value="yes" <?php echo esc_html($checked); ?>> Yes</label><br>
		<?php $checked = (isset($this->premium_black_settings_options['enabled']) && $this->premium_black_settings_options['enabled'] === 'no') ? 'checked' : ''; ?>
		<label for="enabled_0-1"><input type="radio" name="woocommerce_premium_black_settings[enabled]" id="enabled_0-1" value="no" <?php echo esc_html($checked); ?>> No</label></fieldset> <?php
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
        ?> <fieldset><?php $checked = (isset($this->premium_black_settings_options['enable_external_status_page']) && $this->premium_black_settings_options['enable_external_status_page'] === 'yes') ? 'checked' : ''; ?>
		<label for="enable_external_status_page_0-0"><input type="radio" name="woocommerce_premium_black_settings[enable_external_status_page]" id="enable_external_status_page_0-0" value="yes" <?php echo esc_html($checked); ?>> Yes</label><br>
		<?php $checked = (isset($this->premium_black_settings_options['enable_external_status_page']) && $this->premium_black_settings_options['enable_external_status_page'] === 'no') ? 'checked' : ''; ?>
		<label for="enable_external_status_page_0-1"><input type="radio" name="woocommerce_premium_black_settings[enable_external_status_page]" id="enable_external_status_page_0-1" value="no" <?php echo esc_html($checked); ?>> No</label></fieldset> <?php
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
			'<input class="regular-text" type="text" name="woocommerce_premium_black_settings[private_key]" id="private_key" value="%s">',
			isset( $this->premium_black_settings_options['private_key'] ) ? esc_attr( $this->premium_black_settings_options['private_key']) : ''
		);
	}


	public function currencies_callback() {

		$public = isset($this->premium_black_settings_options['public_key']) ? $this->premium_black_settings_options['public_key'] : '';
		$private = isset($this->premium_black_settings_options['private_key']) ? $this->premium_black_settings_options['private_key'] : '';

		if(empty($public) | empty($private))
        {
            echo "Please set api credentials to continue.";
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

            echo "Error during currency retrieval<br />";

            if ($response->Error != null)
                echo 'Error:' . esc_html( $response->Error );
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

        printf('<h4>' . count($response->Currencies) . ' Currencies available</h4>');

		foreach($response->Blockchains as $blockchain) {
			
			printf('<h3>' . esc_html($blockchain->Name) . '</h3>');

			foreach($response->Currencies as $currency) {
				if($currency->Blockchain != $blockchain->Code) {
					continue;
				}

				printf('<input type="checkbox" name="woocommerce_premium_black_settings[currencies][' . esc_html($currency->CodeChain) . ']" id="currencies_' . esc_html($currency->CodeChain) . '" value="' . esc_html($currency->CodeChain) . '" %s> <label for="currencies_' . esc_html($currency->CodeChain) . '">' . esc_html($currency->Name) . ' (' . esc_html(strtoupper( $currency->Symbol)) .  ')</label><br />',
				( isset( $selected_currencies ) && in_array($currency->CodeChain, $selected_currencies)) ? 'checked' : ''
				);
			}
			
		}


	}

    public function debug_mode_callback()
    {
        ?> <fieldset><?php $checked = (isset($this->premium_black_settings_options['debug']) && $this->premium_black_settings_options['debug'] === 'yes') ? 'checked' : ''; ?>
		<label for="debug_mode_0-0"><input type="radio" name="woocommerce_premium_black_settings[debug]" id="debug_mode_0-0" value="yes" <?php echo esc_html($checked); ?>> Yes</label><br>
		<?php $checked = (isset($this->premium_black_settings_options['debug']) && $this->premium_black_settings_options['debug'] === 'no') ? 'checked' : ''; ?>
		<label for="debug_mode_0-1"><input type="radio" name="woocommerce_premium_black_settings[debug]" id="debug_mode_0-1" value="no" <?php echo esc_html($checked); ?>> No</label></fieldset> <?php
    }

}
if ( is_admin() )
	$premium_black_settings = new PremiumBlackSettings();
