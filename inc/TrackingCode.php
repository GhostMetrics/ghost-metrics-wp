<?php
/**
 * Class to handle the tracking code.
 *
 * @package GhostMetricsWP
 */

namespace GhostMetricsWP\Inc;

class TrackingCode {

	/**
	 * Builds the tracking code based on settings.
	 *
	 * @return string
	 */
	public static function get_tracking_code() {
		$ghost_metrics_url = get_option('ghost-metrics-wp_ghost_metrics_url', '');
		$embed_mode = get_option('ghost-metrics-wp_embed_mode', 'regular');
		$selected_site_id = get_option('ghost-metrics-wp_selected_site_id', '');
		$selected_container_id = get_option('ghost-metrics-wp_selected_container_id', '');

		if (empty($ghost_metrics_url) || empty($selected_site_id)) {
			return ''; // Return empty if essential settings are missing
		}

		if ($embed_mode === 'tag_manager' && !empty($selected_container_id)) {
			return self::build_tag_manager_code($ghost_metrics_url, $selected_container_id);
		} elseif ($embed_mode === 'regular') {
			return self::build_standard_embed_code($ghost_metrics_url, $selected_site_id);
		}

		return ''; // Fallback in case of unexpected condition
	}

	/**
	 * Builds the Tag Manager embed code.
	 *
	 * @param string $url
	 * @param string $container_id
	 * @return string
	 */
	private static function build_tag_manager_code($url, $container_id) {
		$lines = [];
		$lines[] = "<!-- Ghost Metrics Tag Manager -->";
		$lines[] = "<script>";
		$lines[] = "var _mtm = window._mtm = window._mtm || [];";
		$lines[] = "_mtm.push({'mtm.startTime': (new Date().getTime()), 'event': 'mtm.Start'});";
		$lines[] = "(function() {";
		$lines[] = "    var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];";
		$lines[] = "    g.async=true; g.src='".esc_url(trailingslashit($url) . "js/container_" . esc_js($container_id) . ".js")."';";
		$lines[] = "    s.parentNode.insertBefore(g,s);";
		$lines[] = "})();";
		$lines[] = "</script>";
		$lines[] = "<!-- End Ghost Metrics Tag Manager -->";
		$lines[] = "";

		return implode("\n", $lines);
	}

	/**
	 * Builds the Standard Embed code with advanced options.
	 *
	 * @param string $url
	 * @param string $site_id
	 * @return string
	 */
	private static function build_standard_embed_code($url, $site_id) {
		$track_subdomains = get_option("ghost-metrics-wp_track_subdomains_$site_id", false);
		$prepend_domain_to_title = get_option("ghost-metrics-wp_prepend_domain_to_title_$site_id", false);
		$hide_alias_urls = get_option("ghost-metrics-wp_hide_alias_urls_$site_id", false);
		$track_js_disabled = get_option("ghost-metrics-wp_track_js_disabled_$site_id", false);
		$cross_domain_linking = get_option("ghost-metrics-wp_cross_domain_linking_$site_id", false);
		$client_side_dnt = get_option("ghost-metrics-wp_client_side_dnt_$site_id", false);
		$disable_tracking_cookies = get_option("ghost-metrics-wp_disable_tracking_cookies_$site_id", false);
		$disable_campaign_parameters = get_option("ghost-metrics-wp_disable_campaign_parameters_$site_id", false);

		$lines = [];
		$lines[] = "<!-- Ghost Metrics -->";
		$lines[] = "<script>";
		$lines[] = "var _paq = window._paq = window._paq || [];";

		if ($prepend_domain_to_title) {
			$lines[] = '_paq.push(["setDocumentTitle", document.domain + "/" + document.title]);';
		}

		if ($track_subdomains) {
			$lines[] = '_paq.push(["setCookieDomain", "*.'.esc_js(parse_url($url, PHP_URL_HOST)).'"]);';
			$lines[] = '_paq.push(["setDomains", ["*.'.esc_js(parse_url($url, PHP_URL_HOST)).'"]]);';
		}

		if ($disable_campaign_parameters) {
			$lines[] = '_paq.push(["disableCampaignParameters"]);';
		}

		if ($client_side_dnt) {
			$lines[] = '_paq.push(["setDoNotTrack", true]);';
		}

		if ($disable_tracking_cookies) {
			$lines[] = '_paq.push(["disableCookies"]);';
		}

		$lines[] = "_paq.push(['trackPageView']);";
		$lines[] = "_paq.push(['enableLinkTracking']);";
		$lines[] = "(function() {";
		$lines[] = "    var u=\"".esc_url(trailingslashit($url))."\";";
		$lines[] = "    _paq.push(['setTrackerUrl', u+'js/tracker.php']);";
		$lines[] = "    _paq.push(['setSiteId', '".esc_js($site_id)."']);";
		$lines[] = "    var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];";
		$lines[] = "    g.async=true; g.src=u+'js/tracker.php'; s.parentNode.insertBefore(g,s);";
		$lines[] = "})();";
		$lines[] = "</script>";

		if ($track_js_disabled) {
			$lines[] = '<noscript><p><img referrerpolicy="no-referrer-when-downgrade" src="'.esc_url(trailingslashit($url).'js/tracker.php?idsite='.esc_js($site_id).'&rec=1').'" style="border:0;" alt="" /></p></noscript>';
		}

		$lines[] = "<!-- End Ghost Metrics Code -->";
		$lines[] = "";

		return implode("\n", $lines);
	}
}
