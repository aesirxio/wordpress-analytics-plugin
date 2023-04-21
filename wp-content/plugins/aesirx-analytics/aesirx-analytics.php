<?php
/**
 * Plugin Name: aesirx-analytics
 * Description: Aesirx analytics plugin.
 * Version: 1.0.1-alpha.1
 * Author: aesirx.io
 * Author URI: https://aesirx.io/
 * Domain Path: /languages
 * Text Domain: aesirx-analytics
 * Requires PHP: 7.2
 **/

use Symfony\Component\Process\Process;

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

		$domain = ($options['storage'] ?? 'internal') == 'internal' ? get_bloginfo('url') : ($options['domain'] ?? '');

		wp_add_inline_script(
			'aesirx-analytics',
			'window.aesirx1stparty="' . $domain . '";',
			'before'
		);
	}
);

add_action('plugins_loaded', function () {
	load_plugin_textdomain('aesirx-analytics', false, dirname(plugin_basename(__FILE__)) . '/languages/');
});

register_activation_hook( __FILE__, function () {
	$file = WP_PLUGIN_DIR . '/aesirx-analytics/assets/analytics-cli';

	chmod($file,'0755');

	process_analytics(['migrate']);
} );

add_action( 'analytics_cron_geo', function() {
	process_analytics(['job', 'geo']);
} );

if ( ! wp_next_scheduled( 'analytics_cron_geo' ) ) {
	wp_schedule_event( time(), 'hourly', 'analytics_cron_geo' );
}

function process_analytics(array $command): Process {
	require_once WP_PLUGIN_DIR . '/aesirx-analytics/vendor/autoload.php';
	$file = WP_PLUGIN_DIR . '/aesirx-analytics/assets/analytics-cli';
	$options = get_option('aesirx_analytics_plugin_options');

	$env = [
		'DBUSER' => DB_USER,
		'DBPASS' => DB_PASSWORD,
		'DBNAME' => DB_NAME,
		'DBTYPE' => 'mysql',
		'LICENSE' => $options['license'] ?? '',
	];

	$dbHost = explode(':', DB_HOST);

	$env['DBHOST'] = $dbHost[0];

	if (count($dbHost) > 1)
	{
		$env['DBPORT'] = $dbHost[1];
	}

	$process = (new Process(array_merge([$file], $command), null, $env));
	$process->run();

	return $process;
}

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

function get_the_user_ip(): string {
	if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
		//check ip from share internet

	} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		//to check ip is pass from proxy
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	} else {
		$ip = $_SERVER['REMOTE_ADDR'];
	}

	return $ip;
}

function match_uri(string $path): bool
{
	return substr($_SERVER["REQUEST_URI"], 0, strlen($path)) === $path;
}

function apply_if_not_empty(array $request, array $fields): array
{
	$command = [];

	foreach ($fields as $from => $to)
	{
		if (array_key_exists($from, $request))
		{
			$command[] = '--' . $to;
			$command[] = $request[$from];
		}
	}

	return $command;
}

function apply_attributes(array $request): array
{
	$command = [];

	if (!empty($request['attributes'] ?? []))
	{
		foreach ($request['attributes'] as $name => $value)
		{
			$command[] = '--attributes';
			$command[] = $name . '=' . $value;
		}
	}

	return $command;
}

add_action('parse_request', 'my_custom_url_handler');

function my_custom_url_handler() {

	$path = '/visitor/v1/';

	$options = get_option('aesirx_analytics_plugin_options');

	if (substr($_SERVER["REQUEST_URI"], 0, strlen($path)) !== $path
	    || $_SERVER['REQUEST_METHOD'] !== 'POST'
	    || ($options['storage'] ?? 'internal') != 'internal')
	{
		return;
	}

	define( 'WP_DEBUG', true );
	define( 'WP_DEBUG_DISPLAY', true );
	@ini_set( 'display_errors', 1 );

	$requestBody = json_decode(file_get_contents('php://input'), true);

	switch (true)
	{
		case match_uri('/visitor/v1/init'):
			$command = [
				'visitor', 'init' , 'v1',
				'--ip', empty($requestBody['ip']) ? get_the_user_ip() : $requestBody['ip'],
			];
			$fields = [
				'user_agent' => 'user-agent',
				'device' => 'device',
				'browser_name' => 'browser-name',
				'browser_version' => 'browser-version',
				'lang' => 'lang',
				'url' => 'url',
				'referer' => 'referer',
				'event_name' => 'event-name',
				'event_type' => 'event-type',
			];
			$command = array_merge($command, apply_if_not_empty($requestBody, $fields));
			$command = array_merge($command, apply_attributes($requestBody));
			break;
		case match_uri('/visitor/v1/start'):
			$command = [
				'visitor', 'start' , 'v1',
			];

			$fields = [
				'visitor_uuid' => 'visitor-uuid',
				'url' => 'url',
				'referer' => 'referer',
				'event_name' => 'event-name',
				'event_type' => 'event-type',
				'event_uuid' => 'event-uuid',
			];
			$command = array_merge($command, apply_if_not_empty($requestBody, $fields));
			$command = array_merge($command, apply_attributes($requestBody));
			break;
		case match_uri('/visitor/v1/end'):
			$command = [
				'visitor', 'end' , 'v1',
			];
			$fields = [
				'visitor_uuid' => 'visitor-uuid',
				'event_uuid' => 'event-uuid',
			];
			$command = array_merge($command, apply_if_not_empty($requestBody, $fields));
			break;
		default:
			die;
	}

	$process = process_analytics($command);

	if ($process->isSuccessful())
	{
		echo $process->getOutput();
	}
	else
	{
		$err = $process->getErrorOutput();

		json_decode($err);

		if (json_last_error() === JSON_ERROR_NONE)
		{
			echo $err;
		}
		else
		{
			echo json_encode($err);
		}
	}

	die;
}
