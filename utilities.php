<?php
if ( ! function_exists( 'add_filter' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

class Versionmate_Utils {

	/**
	 * Sanitize options input
	 *
	 * @param array $input Contains all settings fields as array keys
	 *
	 * @return array
	 */
	public static function sanitize_options( $input ) {
		$new_input = array();
		if ( isset( $input['api_key'] ) ) {
			$new_input['api_key'] = sanitize_text_field( $input['api_key'] );
		}

		return $new_input;
	}

	/**
	 * Check if API key is set, if not show notification
	 */
	public static function check_api_key() {
		$options = get_option( 'versionmate' );

		if ( $options === false || empty( $options['api_key'] ) ) {
			Versionmate_Utils::show_error_no_api_key();
		}
	}

	/**
	 * Show error when API key is not set
	 */
	public static function show_error_no_api_key() {
		$notification = new Versionmate_Notification();
		$notification->setType( 'error' );

		$no_api_key           = __( 'No Versionmate API key specified!', 'versionmate' );
		$versionmate_settings = __( 'Go to Versionmate Settings and fill in your api key!', 'versionmate' );

		$notification->setText(
			'<strong> ' . $no_api_key . ' <a href="' . esc_url( get_admin_url( null, 'options-general.php?page=versionmate-settings' ) ) . '">' . $versionmate_settings . '</a></strong>'
		);


		$notification->show();
	}

	/**
	 * Converts a plugin basename back into a friendly slug.
	 *
	 * @param $basename
	 *
	 * @return string
	 */
	public static function get_plugin_name( $basename ) {
		if ( false === strpos( $basename, '/' ) ) {
			$name = basename( $basename, '.php' );
		} else {
			$name = dirname( $basename );
		}

		return $name;
	}


}