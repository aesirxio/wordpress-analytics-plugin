<?php
/**
 * Plugin Name: aesirx-analytics
 * Description: Aesirx analytics plugin.
 * Version: 0.1
 * Author: aesirx.io
 * Author URI: https://aesirx.io/
 * Domain Path: /languages
 * Text Domain: aesirx-analytics
 * Requires PHP: 7.2
 **/

require_once 'includes/settings.php';

add_action(
	'wp_enqueue_scripts',
	function (): void {
		wp_register_script(
			'aesirx-analytics',
			plugins_url('assets/js/analytics.js', __FILE__)
		);
		wp_enqueue_script('aesirx-analytics');

		$options = get_option('aesirx_analytics_plugin_options');

		wp_add_inline_script(
			'aesirx-analytics',
			'window.aesirx1stparty="' . ($options['domain'] ?? '') . '";',
			'before'
		);
	}
);

add_action('plugins_loaded', function () {
	load_plugin_textdomain('aesirx-analytics', false, dirname(plugin_basename(__FILE__)) . '/languages/');
});

add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
	$url = esc_url(add_query_arg(
		'page',
		'aesirx-analytics-plugin',
		get_admin_url() . 'admin.php'
	));
	array_push(
		$links,
		"<a href='$url'>" . __('Settings') . '</a>'
	);
	return $links;
});
