<?php
/**
 * Main plugin class
 *
 * @package GhostMetricsWP
 */

namespace GhostMetricsWP\Inc;

/**
 * Main class
 */
class Plugin {

	/**
	 * Custom constructor for handling WordPress Hooks
	 */
	public static function initialize() {
		$self = new self();

		// Activation and deactivation hooks
		register_activation_hook( __FILE__, [ $self, 'ghost_metrics_wp_activate' ] );
		register_deactivation_hook( __FILE__, [ $self, 'ghost_metrics_wp_deactivate' ] );

		// Add tracking code to the site header
		add_action( 'wp_head', [ $self, 'add_tracking_code' ] );

		// Enqueue admin assets for the settings page
		add_action( 'admin_enqueue_scripts', [ $self, 'enqueue_admin_assets' ] );

		// AJAX handler for saving settings
		add_action( 'wp_ajax_save_ghost_metrics', [ $self, 'save_ghost_metrics_callback' ] );
	}

	/**
	 * Performs actions when the plugin is activated.
	 *
	 * @return void
	 */
	public function ghost_metrics_wp_activate() {
		// End process if PHP version does not meet requirements.
		if ( version_compare( PHP_VERSION, GHOST_METRICS_WP_PHP_MINIMUM, '<' ) ) {
			wp_die(
				esc_html( sprintf( __( 'Ghost Metrics WP requires PHP version %s or higher', 'ghost-metrics-wp' ), GHOST_METRICS_WP_PHP_MINIMUM ) ),
				esc_html( __( 'Error Activating', 'ghost-metrics-wp' ) )
			);
		}

		// End process if WordPress version does not meet requirements.
		if ( version_compare( get_bloginfo( 'version' ), GHOST_METRICS_WP_WP_MINIMUM, '<' ) ) {
			wp_die(
				esc_html( sprintf( __( 'Ghost Metrics WP requires WordPress version %s or higher', 'ghost-metrics-wp' ), GHOST_METRICS_WP_WP_MINIMUM ) ),
				esc_html( __( 'Error Activating', 'ghost-metrics-wp' ) )
			);
		}
	}

	/**
	 * Performs actions when the plugin is deactivated.
	 *
	 * @return void
	 */
	public function ghost_metrics_wp_deactivate() {
		if ( version_compare( PHP_VERSION, GHOST_METRICS_WP_PHP_MINIMUM, '<' ) ) {
			return;
		}
	}

	/**
	 * Adds the Ghost Metrics tracking code to the site header.
	 *
	 * @return void
	 */
	public function add_tracking_code() {
		$tracking_code = TrackingCode::get_tracking_code();
		if ( ! empty( $tracking_code ) ) {
			echo $tracking_code;
		}
	}

	/**
	 * Enqueue admin CSS and JS for the settings page.
	 *
	 * @param string $hook The current admin page.
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( 'settings_page_ghost-metrics-settings' !== $hook ) {
			return;
		}

		// Enqueue CSS
		wp_enqueue_style(
			'ghost-metrics-admin-css',
			GHOST_METRICS_WP_URL . 'build/css/main.css',
			[],
			filemtime( GHOST_METRICS_WP_DIR . 'build/css/main.css' )
		);

		// Enqueue JS
		wp_enqueue_script(
			'ghost-metrics-admin-js',
			GHOST_METRICS_WP_URL . 'build/js/main.js',
			[ 'jquery' ],
			filemtime( GHOST_METRICS_WP_DIR . 'build/js/main.js' ),
			true
		);

		// Pass AJAX URL and nonce to JS
		wp_localize_script(
			'ghost-metrics-admin-js',
			'ghostMetricsData',
			[
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'ghost_metrics_nonce' )
			]
		);
	}

	/**
	 * AJAX callback to save Ghost Metrics settings.
	 */
	public function save_ghost_metrics_callback() {
		check_ajax_referer( 'ghost_metrics_nonce' );

		$url     = sanitize_text_field( $_POST['ghost_metrics_url'] ?? '' );
		$token   = sanitize_text_field( $_POST['auth_token'] ?? '' );
		$mode    = sanitize_text_field( $_POST['embed_mode'] ?? 'regular' );
		$site_id = sanitize_text_field( $_POST['selected_site_id'] ?? '' );

		$response = update_option( 'ghost_metrics_url', $url )
		            && update_option( 'auth_token', $token )
		            && update_option( 'embed_mode', $mode )
		            && update_option( 'selected_site_id', $site_id );

		wp_send_json_success( [
			'success' => $response,
			'message' => __( 'Settings saved successfully.', 'ghost-metrics-wp' ),
		] );
	}
}