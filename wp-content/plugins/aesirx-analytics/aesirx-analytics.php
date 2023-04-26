<?php
/**
 * Plugin Name: aesirx-analytics
 * Plugin URI: https://analytics.aesirx.io/
 * Description: Aesirx analytics plugin.
 * Version: 1.0.1-alpha.1
 * Author: aesirx.io
 * Author URI: https://aesirx.io/
 * Domain Path: /languages
 * Text Domain: aesirx-analytics
 * Requires PHP: 7.2
 **/

use AesirxAnalytics\Route\Middleware\IsBackendMiddlware;
use Pecee\SimpleRouter\Exceptions\NotFoundHttpException;
use Symfony\Component\Process\Process;
use Pecee\SimpleRouter\SimpleRouter;

require_once WP_PLUGIN_DIR . '/aesirx-analytics/vendor/autoload.php';
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
	process_analytics(['migrate']);
} );

add_action( 'analytics_cron_geo', function() {
	process_analytics(['job', 'geo']);
} );

if ( ! wp_next_scheduled( 'analytics_cron_geo' ) ) {
	wp_schedule_event( time(), 'hourly', 'analytics_cron_geo' );
}

function process_analytics(array $command, bool $makeExecutable = true): Process {
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

	// Plugin probably updated, we need to make sure it's executable and database is up-to-date
	if ($makeExecutable
	    && !is_executable($file))
	{
		chmod($file,'0755');

		if ($command != ['migrate'])
		{
			process_analytics(['migrate'], false);
		}
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

function apply_if_not_empty(array $request = null, array $fields): array
{
	$command = [];

	if (!empty($request))
	{
		foreach ($fields as $from => $to)
		{
			if (array_key_exists($from, $request))
			{
				foreach ((array) $request[$from] as $one)
				{
					$command[] = '--' . $to;
					$command[] = $one;
				}
			}
		}
	}

	return $command;
}

function apply_list_params(): array
{
	$command = [];
	$params  = SimpleRouter::request()->getUrl()->getParams();

	foreach ($params as $key => $values)
	{
		$converterKey = str_replace('_', '-', $key);

		switch ($key)
		{
			case 'page':
			case 'page_size':
				$command[] = '--' . $converterKey;
				$command[] = $values;
				break;
			case 'sort':
			case 'with':
			case 'sort_direction':
				foreach ($values as $value)
				{
					$command[] = '--' . $converterKey;
					$command[] = $value;
				}
				break;
			case 'filter':
				foreach ($values as $keyValue => $value)
				{
					if (is_iterable($value))
					{
						foreach ($value as $v)
						{
							$command[] = '--filter';
							$command[] = $keyValue . '[]=' . $v;
						}
					}
					else
					{
						$command[] = '--filter';
						$command[] = $keyValue . '=' . $value;
					}
				}

				break;
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
	$options = get_option('aesirx_analytics_plugin_options');

	if (($options['storage'] ?? 'internal') != 'internal')
	{
		return;
	}

//	define( 'WP_DEBUG', true );
//	define( 'WP_DEBUG_DISPLAY', true );
//	@ini_set( 'display_errors', 1 );

	$request          = SimpleRouter::request();
	$requestBody      = json_decode(file_get_contents('php://input'), true);
	$requestUrlParams = $request->getUrl()->getParams();
	$command          = null;

	SimpleRouter::group(['prefix' => '/visitor/v1'], function () use (&$command, $requestBody, $request) {
		SimpleRouter::post( '/init', function () use (&$command, $requestBody, $request) {
			$command = [
				'visitor', 'init' , 'v1',
				'--ip', empty($requestBody['ip']) ? $request->getIp() : $requestBody['ip'],
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
		});

		SimpleRouter::post('/start', function () use (&$command, $requestBody) {
			$command = [ 'visitor', 'start' , 'v1', ];

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
		});

		SimpleRouter::post('/end', function () use (&$command, $requestBody) {
			$command = [
				'visitor', 'end' , 'v1',
			];
			$fields = [
				'visitor_uuid' => 'visitor-uuid',
				'event_uuid' => 'event-uuid',
			];
			$command = array_merge($command, apply_if_not_empty($requestBody, $fields));
		});
	});

	SimpleRouter::group(['middleware' => IsBackendMiddlware::class], function () use (&$command, $requestUrlParams) {
		SimpleRouter::get('/flow/v1/{flow_uuid}', function (string $flowUuid) use (&$command, $requestUrlParams) {
			$command = [
				'get', 'flow', 'v1', $flowUuid,
			];
			$command = array_merge($command, apply_if_not_empty($requestUrlParams, ['with' => 'with']));
		});
		SimpleRouter::get('/flow/v1/{start_date}/{end_date}', function (string $start, string $end) use (&$command, $requestUrlParams) {
			$command = array_merge([ 'get', 'flows', 'v1', '--start', $start, '--end', $end, ], apply_list_params());
		});

		SimpleRouter::get('/visitor/v1/{start_date}/{end_date}', function (string $start, string $end) use (&$command, $requestUrlParams) {
			$command = array_merge([ 'ge' , 'events', 'v1', '--start', $start, '--end', $end, ], apply_list_params());
		});

		foreach (['visits', 'domains', 'metrics', 'pages', 'visitors', 'browsers', 'browserversions', 'languages', 'devices', 'countries', 'cities', 'isps', 'attribute'] as $statistic)
		{
			SimpleRouter::get('/' . $statistic . '/v1/{start_date}/{end_date}', function (string $start, string $end) use (&$command, $statistic) {
				$path    = $statistic == 'attribute' ? 'attributes' : $statistic;
				$command = array_merge([ 'statistics', $path, 'v1', '--start', $start, '--end', $end, ], apply_list_params());
			});
		}
	});

	try
	{
		SimpleRouter::start();
	}
	catch (Throwable $e)
	{
		if ($e instanceof NotFoundHttpException)
		{
			return;
		}

		echo json_encode(['error' => $e->getMessage()]);
		die;
	}

	if (is_null($command))
	{
		return;
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
