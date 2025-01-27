<?php
/**
 * Plugin Name:       Ghost Metrics WP
 * Plugin URI:        https://ghostmetrics.io/
 * Description:       Add Ghost Metrics to your WordPress site.
 * Version:           1.0.0
 * Requires at least: 6.4.0
 * Requires PHP:      7.4
 * Author:            Ghost Metrics
 * Author URI:        https://ghostmetrics.io/
 * License:           GPLv3 or later
 * License URI:       https://www.gnu.org/licenses/gpl.html
 * Text Domain:       ghost-metrics-wp
 * Domain Path:       /languages
 * Update URI:        
 *
 * @package GhostMetricsWP
 */

/**
 * Exit is accessed directly.
 */
defined( 'ABSPATH' ) || exit;


/**
 * Define essential constants.
 */
define( 'GHOST_METRICS_WP_VERSION', '1.0.0' );
define( 'GHOST_METRICS_WP_PHP_MINIMUM', '7.4.0' );
define( 'GHOST_METRICS_WP_WP_MINIMUM', '6.4.0' );
define( 'GHOST_METRICS_WP_DIR', plugin_dir_path( __FILE__ ) );
define( 'GHOST_METRICS_WP_URL', plugin_dir_url( __FILE__ ) );

/**
 * Composer Autoload
 */
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}


/**
 * Bootstraps the plugin
 */
use GhostMetricsWP\Inc\Init;

$init = new Init();
$init->register_classes_list();
