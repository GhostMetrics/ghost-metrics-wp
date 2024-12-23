<?php
/**
 * Class to handle the Ghost Metrics settings page.
 *
 * @package GhostMetricsWP
 */

namespace GhostMetricsWP\Inc;

class AdminSettings {

	/**
	 * Initialize hooks for settings page and options registration.
	 */
	public function initialize() {
		add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	/**
	 * Add the Ghost Metrics settings page to the WordPress admin menu.
	 */
	public function add_settings_page() {
		add_options_page(
			__( 'Ghost Metrics Settings', 'ghost-metrics-wp' ),
			__( 'Ghost Metrics', 'ghost-metrics-wp' ),
			'manage_options',
			'ghost-metrics-settings',
			[ $this, 'render_settings_page' ]
		);
	}

    /**
     * Register settings for the Ghost Metrics settings page.
     */
	public function register_settings() {
		// Standard settings with prefix
		register_setting( 'ghost_metrics_settings_group', 'ghost-metrics-wp_embed_mode', [
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'regular',
		] );

		register_setting( 'ghost_metrics_settings_group', 'ghost-metrics-wp_selected_site_id', [
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		] );

		register_setting( 'ghost_metrics_settings_group', 'ghost-metrics-wp_selected_container_id', [
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		] );

		register_setting( 'ghost_metrics_settings_group', 'ghost-metrics-wp_ghost_metrics_url', [
			'type'              => 'string',
			'sanitize_callback' => [ $this, 'validate_url' ],
			'default'           => '',
		] );

		register_setting( 'ghost_metrics_settings_group', 'ghost-metrics-wp_auth_token', [
			'type'              => 'string',
			'sanitize_callback' => [ $this, 'validate_auth_token' ],
			'default'           => '',
		] );

		register_setting('ghost_metrics_settings_group', 'ghost-metrics-wp_auth_token', [
			'type'              => 'string',
			'sanitize_callback' => function($token) {
				if (empty($token)) {
					// Keep the existing token if nothing is entered
					return get_option('ghost-metrics-wp_auth_token');
				}
				return sanitize_text_field($token);
			},
			'default'           => '',
		]);

		// Dynamically register advanced options for the selected site
		$selected_site_id = get_option( 'ghost-metrics-wp_selected_site_id', '' );
		if ( ! empty( $selected_site_id ) ) {
			$advanced_options = [
				"ghost-metrics-wp_track_subdomains_{$selected_site_id}",
				"ghost-metrics-wp_prepend_domain_to_title_{$selected_site_id}",
				"ghost-metrics-wp_hide_alias_urls_{$selected_site_id}",
				"ghost-metrics-wp_track_js_disabled_{$selected_site_id}",
				"ghost-metrics-wp_cross_domain_linking_{$selected_site_id}",
				"ghost-metrics-wp_client_side_dnt_{$selected_site_id}",
				"ghost-metrics-wp_disable_tracking_cookies_{$selected_site_id}",
				"ghost-metrics-wp_disable_campaign_parameters_{$selected_site_id}",
			];

			foreach ( $advanced_options as $option_name ) {
				register_setting( 'ghost_metrics_settings_group', $option_name, [
					'type'              => 'boolean',
					'sanitize_callback' => 'rest_sanitize_boolean',
					'default'           => false,
				] );
			}
		}
	}

	/**
	 * Validate the URL to ensure it matches *.ghostmetrics.cloud.
	 *
	 * @param string $url The URL to validate.
	 * @return string The validated and sanitized URL with trailing slash.
	 */
	public function validate_url($url) {
		if (empty($url)) {
			add_settings_error(
				'ghost_metrics_settings_group',
				'ghost_metrics_url_error',
				__('URL cannot be empty.', 'ghost-metrics-wp'),
				'error'
			);
			return '';
		}

		// Ensure URL without trailing slash is validated
		$url_without_slash = rtrim($url, '/');

		// Validate domain pattern (https://*.ghostmetrics.cloud)
		if (!preg_match('/^https:\/\/[a-zA-Z0-9.-]+\.ghostmetrics\.cloud$/', $url_without_slash)) {
			add_settings_error(
				'ghost_metrics_settings_group',
				'ghost_metrics_url_error',
				__('Invalid URL. Only subdomains of ghostmetrics.cloud are allowed.', 'ghost-metrics-wp'),
				'error'
			);
			return get_option('ghost-metrics-wp_ghost_metrics_url', '');
		}

		return trailingslashit(esc_url_raw($url_without_slash));
	}

	/**
	 * Validate the Auth Token by attempting an API request.
	 */
	public function validate_auth_token($token) {
		$url = get_option('ghost-metrics-wp_ghost_metrics_url');
		if (empty($url) || empty($token)) {
			return '';
		}

		$response = wp_remote_get("$url/index.php?module=API&method=SitesManager.getSitesWithAdminAccess&format=json&token_auth=$token");

		if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
			add_settings_error(
				'ghost_metrics_settings_group',
				'ghost_metrics_auth_error',
				__('Failed to authenticate with the provided token.', 'ghost-metrics-wp'),
				'error'
			);
			return get_option('ghost-metrics-wp_auth_token', '');
		}

		return sanitize_text_field($token);
	}

    /**
     * Render the Ghost Metrics settings page.
     */
	public function render_settings_page() {
		$ghost_metrics_url     = get_option( 'ghost-metrics-wp_ghost_metrics_url', '' );
		$auth_token            = get_option( 'ghost-metrics-wp_auth_token', '' );
		$embed_mode            = get_option( 'ghost-metrics-wp_embed_mode', 'regular' );
		$selected_site_id      = get_option( 'ghost-metrics-wp_selected_site_id', '' );
		$selected_container_id = get_option( 'ghost-metrics-wp_selected_container_id', '' );

		$sites      = $this->fetch_sites( $ghost_metrics_url, $auth_token );
		$containers = [];

		if ( ! empty( $selected_site_id ) ) {
			$containers = $this->fetch_containers( $ghost_metrics_url, $auth_token, $selected_site_id );
		}

		// Advanced options
		$track_subdomains            = get_option( "ghost-metrics-wp_track_subdomains_{$selected_site_id}", false );
		$prepend_domain_to_title     = get_option( "ghost-metrics-wp_prepend_domain_to_title_{$selected_site_id}", false );
		$hide_alias_urls             = get_option( "ghost-metrics-wp_hide_alias_urls_{$selected_site_id}", false );
		$track_js_disabled           = get_option( "ghost-metrics-wp_track_js_disabled_{$selected_site_id}", false );
		$cross_domain_linking        = get_option( "ghost-metrics-wp_cross_domain_linking_{$selected_site_id}", false );
		$client_side_dnt             = get_option( "ghost-metrics-wp_client_side_dnt_{$selected_site_id}", false );
		$disable_tracking_cookies    = get_option( "ghost-metrics-wp_disable_tracking_cookies_{$selected_site_id}", false );
		$disable_campaign_parameters = get_option( "ghost-metrics-wp_disable_campaign_parameters_{$selected_site_id}", false );

		?>
        <div class="wrap ghost-metrics-admin">
            <h1><?php esc_html_e( 'Ghost Metrics Settings', 'ghost-metrics-wp' ); ?></h1>
            <p class="description"><?php esc_html_e( 'Configure your Ghost Metrics integration.', 'ghost-metrics-wp' ); ?></p>

            <form method="post" action="options.php">
				<?php
				settings_fields( 'ghost_metrics_settings_group' );
				do_settings_sections( 'ghost-metrics-settings' );
				?>

                <!-- Authentication Section -->
                <div class="settings-section">
                    <h2><?php esc_html_e( 'Authentication', 'ghost-metrics-wp' ); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e( 'Ghost Metrics URL', 'ghost-metrics-wp' ); ?></th>
                            <td>
                                <input type="text" name="ghost-metrics-wp_ghost_metrics_url"
                                       value="<?php echo esc_attr( $ghost_metrics_url ); ?>"/>
                                <p class="description"><?php esc_html_e('Enter the URL for your Ghost Metrics instance. Only subdomains of ghostmetrics.cloud are allowed.', 'ghost-metrics-wp'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Auth Token', 'ghost-metrics-wp'); ?></th>
                            <td>
                                <input type="password" name="ghost-metrics-wp_auth_token" value="" placeholder="<?php echo !empty($auth_token) ? esc_attr__('Token is already set', 'ghost-metrics-wp') : ''; ?>"
				                    <?php echo !empty($auth_token) ? 'disabled' : ''; ?>
                                />
                                <p class="description"><?php esc_html_e('Enter your Ghost Metrics auth token. Leave blank to keep the current token.', 'ghost-metrics-wp'); ?></p>

			                    <?php if (!empty($auth_token)) : ?>
                                    <button type="button" id="enable-auth-token" class="button">
					                    <?php esc_html_e('Edit Token', 'ghost-metrics-wp'); ?>
                                    </button>
			                    <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>

				<?php if ( ! empty( $ghost_metrics_url ) && ! empty( $auth_token ) ) : ?>
                    <!-- Site Selection -->
                    <div class="settings-section">
                        <h2><?php esc_html_e( 'Site Configuration', 'ghost-metrics-wp' ); ?></h2>
                        <select name="ghost-metrics-wp_selected_site_id" id="selected_site_id">
                            <option value=""><?php esc_html_e( 'Select a site', 'ghost-metrics-wp' ); ?></option>
							<?php foreach ( $sites as $site ) : ?>
                                <option value="<?php echo esc_attr( $site['idsite'] ); ?>" <?php selected( $selected_site_id, $site['idsite'] ); ?>>
									<?php echo esc_html( $site['name'] ); ?>
                                </option>
							<?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Embed Mode -->
                    <div class="settings-section">
                        <h2><?php esc_html_e( 'Embed Mode', 'ghost-metrics-wp' ); ?></h2>
                        <select name="ghost-metrics-wp_embed_mode" id="embed_mode">
                            <option value="regular" <?php selected( $embed_mode, 'regular' ); ?>>Standard Embed</option>
                            <option value="tag_manager" <?php selected( $embed_mode, 'tag_manager' ); ?>>Tag Manager
                                Embed
                            </option>
                        </select>
                    </div>

                    <!-- Tag Manager Container (Only Visible for Tag Manager Embed) -->
                    <div class="tag-manager-container" <?php echo $embed_mode === 'tag_manager' ? '' : 'style="display:none;"'; ?>>
                        <h2><?php esc_html_e( 'Tag Manager Configuration', 'ghost-metrics-wp' ); ?></h2>
                        <table class="form-table">
                            <tr>
                                <th><?php esc_html_e( 'Select Container', 'ghost-metrics-wp' ); ?></th>
                                <td>
                                    <select name="ghost-metrics-wp_selected_container_id" id="selected_container_id">
										<?php if ( ! empty( $containers ) ) : ?>
											<?php foreach ( $containers as $container ) : ?>
                                                <option value="<?php echo esc_attr( $container['idcontainer'] ); ?>"
													<?php selected( $selected_container_id, $container['idcontainer'] ); ?>>
													<?php echo esc_html( $container['name'] ) . ' (' . esc_attr( $container['idcontainer'] ) . ')'; ?>
                                                </option>
											<?php endforeach; ?>
										<?php else : ?>
                                            <option value=""><?php esc_html_e( 'No containers available.', 'ghost-metrics-wp' ); ?></option>
										<?php endif; ?>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Advanced Options (Standard Embed) -->
                    <div class="advanced-options" <?php echo $embed_mode === 'regular' ? '' : 'style="display:none;"'; ?>>
                        <h2><?php esc_html_e( 'Advanced Options', 'ghost-metrics-wp' ); ?></h2>
                        <p class="description"><?php esc_html_e( 'Enable advanced tracking features for the selected site.', 'ghost-metrics-wp' ); ?></p>

                        <label>
                            <input type="checkbox" name="track_subdomains_<?php echo esc_attr( $selected_site_id ); ?>"
                                   value="1" <?php checked( $track_subdomains, true ); ?> />
							<?php esc_html_e( 'Track Subdomains', 'ghost-metrics-wp' ); ?>
                        </label>
                        <p class="description"><?php esc_html_e( 'Counts visitors across subdomains as unique visitors.', 'ghost-metrics-wp' ); ?></p>

                        <label>
                            <input type="checkbox"
                                   name="prepend_domain_to_title_<?php echo esc_attr( $selected_site_id ); ?>"
                                   value="1" <?php checked( $prepend_domain_to_title, true ); ?> />
							<?php esc_html_e( 'Prepend Domain to Title', 'ghost-metrics-wp' ); ?>
                        </label>
                        <p class="description"><?php esc_html_e( 'Adds the domain name to the beginning of the page title.', 'ghost-metrics-wp' ); ?></p>

                        <label>
                            <input type="checkbox" name="hide_alias_urls_<?php echo esc_attr( $selected_site_id ); ?>"
                                   value="1" <?php checked( $hide_alias_urls, true ); ?> />
							<?php esc_html_e( 'Hide Alias URLs', 'ghost-metrics-wp' ); ?>
                        </label>
                        <p class="description"><?php esc_html_e( 'Exclude alias URLs from the outlinks report.', 'ghost-metrics-wp' ); ?></p>

                        <label>
                            <input type="checkbox" name="track_js_disabled_<?php echo esc_attr( $selected_site_id ); ?>"
                                   value="1" <?php checked( $track_js_disabled, true ); ?> />
							<?php esc_html_e( 'Track JavaScript Disabled Users', 'ghost-metrics-wp' ); ?>
                        </label>
                        <p class="description"><?php esc_html_e( 'Track users even if they have JavaScript disabled.', 'ghost-metrics-wp' ); ?></p>

                        <label>
                            <input type="checkbox"
                                   name="cross_domain_linking_<?php echo esc_attr( $selected_site_id ); ?>"
                                   value="1" <?php checked( $cross_domain_linking, true ); ?> />
							<?php esc_html_e( 'Enable Cross-Domain Linking', 'ghost-metrics-wp' ); ?>
                        </label>
                        <p class="description"><?php esc_html_e( 'Track visitors across different domains as a single visitor.', 'ghost-metrics-wp' ); ?></p>

                        <label>
                            <input type="checkbox" name="client_side_dnt_<?php echo esc_attr( $selected_site_id ); ?>"
                                   value="1" <?php checked( $client_side_dnt, true ); ?> />
							<?php esc_html_e( 'Enable Client-Side DoNotTrack', 'ghost-metrics-wp' ); ?>
                        </label>
                        <p class="description"><?php esc_html_e( 'Respect the DoNotTrack settings in users\' browsers.', 'ghost-metrics-wp' ); ?></p>

                        <label>
                            <input type="checkbox"
                                   name="disable_tracking_cookies_<?php echo esc_attr( $selected_site_id ); ?>"
                                   value="1" <?php checked( $disable_tracking_cookies, true ); ?> />
							<?php esc_html_e( 'Disable Tracking Cookies', 'ghost-metrics-wp' ); ?>
                        </label>
                        <p class="description"><?php esc_html_e( 'Disables all tracking cookies, including first-party cookies.', 'ghost-metrics-wp' ); ?></p>

                        <label>
                            <input type="checkbox"
                                   name="disable_campaign_parameters_<?php echo esc_attr( $selected_site_id ); ?>"
                                   value="1" <?php checked( $disable_campaign_parameters, true ); ?> />
							<?php esc_html_e( 'Disable Campaign Parameters', 'ghost-metrics-wp' ); ?>
                        </label>
                        <p class="description"><?php esc_html_e( 'Removes UTM and campaign parameters from tracked URLs.', 'ghost-metrics-wp' ); ?></p>
                    </div>
				<?php endif; ?>
	            <?php submit_button( __( 'Save Settings', 'ghost-metrics-wp' ), 'primary', 'ghost-metrics-save' ); ?>
            </form>
        </div>
		<?php
	}

	/**
	 * Fetch available sites from the Ghost Metrics API.
	 *
	 * @param string $url   The Ghost Metrics instance URL.
	 * @param string $token The authentication token.
	 * @return array        List of sites.
	 */
	private function fetch_sites( $url, $token ) {
		if ( empty( $url ) || empty( $token ) ) {
			return [];
		}

		$sites_api_url = trailingslashit( $url ) . 'index.php?module=API&method=SitesManager.getSitesWithAdminAccess&format=json&token_auth=' . $token;

		$sites_response = wp_remote_get( $sites_api_url );

		if ( is_wp_error( $sites_response ) ) {
			error_log( 'Sites API Error: ' . $sites_response->get_error_message() );

			return [];
		}

		$sites_body = wp_remote_retrieve_body( $sites_response );
		$sites      = json_decode( $sites_body, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			error_log( 'JSON Decode Error (Sites): ' . json_last_error_msg() );

			return [];
		}

		return $sites;
	}

	/**
	 * Fetch available containers for a site from the Ghost Metrics API.
	 *
	 * @param string $url   The Ghost Metrics instance URL.
	 * @param string $token The authentication token.
	 * @param string $id_site The site ID.
	 * @return array        List of containers.
	 */
	private function fetch_containers( $url, $token, $id_site ) {
		if ( empty( $url ) || empty( $token ) || empty( $id_site ) ) {
			return [];
		}

		$containers_api_url = trailingslashit( $url ) . 'index.php?module=API&method=TagManager.getContainers&format=json&idSite=' . $id_site . '&token_auth=' . $token;

		$containers_response = wp_remote_get( $containers_api_url );

		if ( is_wp_error( $containers_response ) ) {
			error_log( 'Containers API Error: ' . $containers_response->get_error_message() );

			return [];
		}

		$containers_body = wp_remote_retrieve_body( $containers_response );
		$containers      = json_decode( $containers_body, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			error_log( 'JSON Decode Error (Containers): ' . json_last_error_msg() );

			return [];
		}

		return $containers;
	}
}