<?php
/*
Plugin Name: Versionmate
Plugin URI: http://wordpress.org/plugins/versionmate
Description: Versionmate gives you insight in your WordPress websites. Every website is provided with a risk factor based on the updates and vulnerabilities.
Version: 0.0.5
Author: Versionmate
Author URI: https://versionmate.com/
*/

if ( ! function_exists( 'add_filter' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

// Check if get_plugins() function exists. This is required on the front end of the
// site, since it is in a file that is normally only loaded in the admin.
if ( ! function_exists( 'get_plugins' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}

if ( ! function_exists( 'wp_get_themes' ) ) {
	require_once( ABSPATH . WPINC . '/theme.php' );
}

// Include everything we need
include( dirname( __FILE__ ) . '/utilities.php' );
include( dirname( __FILE__ ) . '/scheduler.php' );
include( dirname( __FILE__ ) . '/notification.php' );
include( dirname( __FILE__ ) . '/options.php' );

// Load textdomain, for translations
add_action( 'plugins_loaded', 'versionmate_load_textdomain' );
function versionmate_load_textdomain() {
	load_plugin_textdomain( 'versionmate', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}


class Versionmate {
	/**
	 * Tag identifier used by file includes and selector attributes.
	 * @var string
	 */
	protected $tag = 'versionmate';

	/**
	 * User friendly name used to identify the plugin.
	 * @var string
	 */
	protected $name = 'Versionmate';

	/**
	 * Current version of the plugin.
	 * @var string
	 */
	protected $version = '0.0.5';

	public function __construct() {
		// Add cron interval
		add_filter( 'cron_schedules', array( 'Versionmate_Scheduler', 'add_cron_interval' ) );

		// Add setting link to Versionmate plugin, in plugin list
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array(
			$this,
			'action_links'
		) );

		// Add action to call from options page
		add_action( 'versionmate_trigger', array( 'Versionmate_Scheduler', 'schedule_trigger' ) );

		// Add scheduler
		// @todo this should be the ping to version mate (to check if the instance is still alive)
		add_action( 'versionmate_scheduler', array( $this, 'trigger' ) );
		add_action( 'versionmate_single_cron', array( $this, 'trigger' ) );

		Versionmate_Scheduler::schedule_cron();
		Versionmate_Utils::check_api_key();

		$this->check_plugin_hash();
		$this->check_version();
	}

	/**
	 * Check hash against previous
	 */
	function check_plugin_hash() {
		$changed      = false;
		$current_hash = $this->plugin_hash();

		$options = get_option( 'versionmate', array() );

		if ( isset( $options['plugin_hash'] ) ) {

			// compare
			if ( $options['plugin_hash'] != $current_hash ) {
				$changed = true;
			}

		} else {
			$changed = true;
		}

		if ( $changed ) {
			$options['plugin_hash'] = $current_hash;
			update_option( 'versionmate', $options );
			Versionmate_Scheduler::schedule_trigger();
		}
	}

	/**
	 * Generate hash of the current state
	 */
	function plugin_hash() {
		// Make sure we create a checksum for WordPress itself, plugins and themes
		$pluginsAndThemes = array_merge( array( $this->get_wp_version() ), $this->get_plugins(), $this->get_themes() );

		if ( function_exists( 'json_encode' ) ) {
			return md5( json_encode( $pluginsAndThemes ) );
		} else {
			return md5( serialize( $pluginsAndThemes ) );
		}
	}

	/**
	 * Get the WordPress version
	 *
	 * @return string
	 */
	private function get_wp_version() {
		// include an unmodified $wp_version
		global $wp_version;
		include( ABSPATH . WPINC . '/version.php' );

		return $wp_version;
	}

	/**
	 * List all the plugins in the installation
	 */
	function get_plugins() {
		$installed_plugins = get_plugins();
		$plugins           = array();

		foreach ( $installed_plugins as $pluginFile => $plugin ) {
			$plugins[] = array(
				'name'     => $plugin['Title'],
				'basename' => $pluginFile,
				'version'  => $plugin['Version'],
				'active'   => is_plugin_active( $pluginFile )
			);
		}

		return $plugins;
	}

	/**
	 * List all the themes in the installation
	 */
	function get_themes() {
		$installed_themes = wp_get_themes();
		$themes           = array();

		foreach ( $installed_themes as $theme_slug => $theme ) {
			$themes[] = array(
				'name'     => $theme->name,
				'basename' => $theme->stylesheet,
				'version'  => $theme->version,
				'parent'   => $theme->parent() ? $theme->parent()->stylesheet : null,
				'active'   => $theme->stylesheet == wp_get_theme()->stylesheet
			);
		}

		return $themes;
	}

	/**
	 * Run on startup, versioncheck for plugin
	 */
	private function check_version() {
		if ( ! defined( 'VERSIONMATE_VERSION_KEY' ) ) {
			define( 'VERSIONMATE_VERSION_KEY', 'versionmate_verison' );
		}

		if ( ! defined( 'VERSIONMATE_VERSION_NUM' ) ) {
			define( 'VERSIONMATE_VERSION_NUM', $this->version );
		}

		update_option( VERSIONMATE_VERSION_KEY, VERSIONMATE_VERSION_NUM );
	}

	/**
	 * Hook runs when plugin is activated, adds cron scheduler
	 */
	public static function run_on_activate() {
		Versionmate_Scheduler::run_on_activate();
	}

	/**
	 * Hook runs when plugin is de-activated, removes cron scheduler
	 */
	public static function run_on_deactivate() {
		Versionmate_Scheduler::run_on_deactivate();
	}

	/**
	 * Adds the settings link to the plugins page
	 *
	 * @param $links
	 *
	 * @return array
	 */
	function action_links( $links ) {
		$links[] = '<a href="' . esc_url( get_admin_url( null, 'options-general.php?page=versionmate-settings' ) ) . '">' . __( 'Settings', 'versionmate' ) . '</a>';

		return $links;
	}

	/**
	 * Get plugin list and send it to Versionmate
	 */
	function trigger() {
		$installation = $this->get_installation();
		$this->notify_versionmate( $installation );
	}

	/**
	 *
	 */
	private function get_installation() {
		$domain  = get_bloginfo( 'url' );
		$options = get_option( 'versionmate', array() );

		$list                    = array();
		$list['core']            = array();
		$list['core']['type']    = 'WordPress';
		$list['core']['version'] = $this->get_wp_version();
		$list['plugins']         = $this->get_plugins();
		$list['themes']          = $this->get_themes();

		$list['client_domain'] = $domain;
		$list['api_key']       = $options['api_key'];

		return $list;
	}

	/**
	 * Send plugin list to Versionmate
	 *
	 * @param $request
	 */
	private function notify_versionmate( $request ) {

		$url = $http_url = 'http://api.versionmate.com/v1/instance/update';

		if ( wp_http_supports( array( 'ssl' ) ) ) {
			$url = set_url_scheme( $url, 'https' );
		}

		$options = get_option( 'versionmate' );

		if ( ! isset( $options['api_key'] ) || empty( $options['api_key'] ) ) {
			return;
		}

		$response = $this->do_post( $request, $url, $options );

		if ( is_wp_error( $response ) ) {
			// Looks like the request has failed
			// Retry without SSL
			$this->do_post( $request, $http_url, $options );
		}
	}

	/**
	 * Actually post to the Versionmate API
	 *
	 * @param $request
	 * @param $url
	 * @param $options
	 *
	 * @return array|WP_Error
	 */
	private function do_post( $request, $url, $options ) {
		return wp_remote_post( $url, array(
				'method'      => 'POST',
				'timeout'     => 45,
				'redirection' => 5,
				'httpversion' => '1.0',
				'blocking'    => true,
				'headers'     => array(
					'instance-token' => $options['api_key']
				),
				'body'        => $request,
				'cookies'     => array()
			)
		);
	}
}

new Versionmate();

// Register activation and deactivation hooks
register_activation_hook( __FILE__, array( 'Versionmate', 'run_on_activate' ) );
register_deactivation_hook( __FILE__, array( 'Versionmate', 'run_on_deactivate' ) );

