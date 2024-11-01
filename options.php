<?php
if ( ! function_exists( 'add_filter' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

class Versionmate_Options {
	/**
	 * Holds the values to be used in the fields callbacks
	 */
	private $options;

	/**
	 * Start up
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'page_init' ) );
	}

	/**
	 * Calls the trigger when the settings change
	 */
	function settings_saved() {
		if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ) {
			do_action( 'versionmate_trigger' );
		}
	}

	/**
	 * Add options page
	 */
	public function add_plugin_page() {
		// This page will be under "Settings"
		$menu = add_options_page(
			'Versionmate Settings',
			'Versionmate',
			'manage_options',
			'versionmate-settings',
			array( $this, 'create_admin_page' )
		);

		add_action( 'load-' . $menu, array( $this, 'settings_saved' ) );
	}

	/**
	 * Options page callback
	 */
	public function create_admin_page() {
		// Set class property
		$this->options = get_option( 'versionmate' );
		?>
		<div class="wrap">
			<h2><?php _e('Versionmate settings', 'versionmate'); ?></h2>
			<form method="post" action="options.php">
				<?php
				// This prints out all hidden setting fields
				settings_fields( 'versionmate' );
				do_settings_sections( 'versionmate-settings' );
				submit_button();
				?>
			</form>
			<div class="notice">
				<?php _e( 'By entering your API key, you accept that Versionmate collects data about your WordPress version, plugins and themes.', 'versionmate' ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Register and add settings
	 */
	public function page_init() {
		register_setting(
			'versionmate', // Option group
			'versionmate', // Option name
			array( 'Versionmate_Utils', 'sanitize_options' ) // Sanitize
		);

		add_settings_section(
			'setting_section_id', // ID
			'', // Title
			array( $this, 'print_section_info' ), // Callback
			'versionmate-settings' // Page
		);

		add_settings_field(
			'api_key', // ID
			'API key', // Title
			array( $this, 'api_key_callback' ), // Callback
			'versionmate-settings', // Page
			'setting_section_id' // Section
		);
	}

	/**
	 * Print the Section text
	 */
	public function print_section_info() {
		_e( 'Enter your settings below:', 'versionmate' );
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function api_key_callback() {
		printf(
			'<input type="text" id="api_key" name="versionmate[api_key]" value="%s" class="regular-text" />',
			isset( $this->options['api_key'] ) ? esc_attr( $this->options['api_key'] ) : ''
		);
	}
}

if ( is_admin() ) {
	new Versionmate_Options();
}

