<?php
/**
 * Plugin Name: AesirX Analytics
 * Plugin URI: https://analytics.aesirx.io?utm_source=wpplugin&utm_medium=web&utm_campaign=wordpress&utm_id=aesirx&utm_term=wordpress&utm_content=analytics
 * Description: Aesirx analytics plugin. When you join forces with AesirX, you're not just becoming a Partner - you're also becoming a freedom fighter in the battle for privacy! Earn 25% Affiliate Commission <a href="https://aesirx.io/seed-round?utm_source=wpplugin&utm_medium=web&utm_campaign=wordpress&utm_id=aesirx&utm_term=wordpress&utm_content=analytics">[Click to Join]</a>
 * Version: 2.1.0
 * Author: aesirx.io
 * Author URI: https://aesirx.io/
 * Domain Path: /languages
 * Text Domain: aesirx-analytics
 * Requires PHP: 7.2
 **/

use AesirxAnalytics\Route\Middleware\IsBackendMiddleware;
use Pecee\SimpleRouter\Exceptions\NotFoundHttpException;
use Symfony\Component\Process\Process;
use Pecee\SimpleRouter\SimpleRouter;

require_once WP_PLUGIN_DIR . '/aesirx-analytics/vendor/autoload.php';
require_once 'includes/settings.php';
require_once 'class-tgm-plugin-activation.php';

function analytics_cli_exists(): bool
{
    return file_exists(WP_PLUGIN_DIR . '/aesirx-analytics/assets/analytics-cli');
}

function analytics_config_is_ok(string $isStorage = null): bool {
    $options = get_option('aesirx_analytics_plugin_options');
    $res = (!empty($options['storage'])
        && (
            ($options['storage'] == 'internal' && !empty($options['license']) && analytics_cli_exists())
            || ($options['storage'] == 'external' && !empty($options['domain']))
        ));

    if ($res
        && !is_null($isStorage))
    {
        $res = $options['storage'] == $isStorage;
    }

    return $res;
}

if (analytics_config_is_ok()) {
    add_action('wp_enqueue_scripts', function (): void {
        wp_register_script('aesirx-analytics', plugins_url('assets/js/analytics.js', __FILE__));
        wp_enqueue_script('aesirx-analytics');

        $options = get_option('aesirx_analytics_plugin_options');

        $domain =
            ($options['storage'] ?? 'internal') == 'internal'
                ? get_bloginfo('url')
                : $options['domain'] ?? '';

        wp_add_inline_script('aesirx-analytics', 'window.aesirx1stparty="' . $domain . '";', 'before');
    });
}

add_action('plugins_loaded', function () {
  load_plugin_textdomain(
    'aesirx-analytics',
    false,
    dirname(plugin_basename(__FILE__)) . '/languages/'
  );
});

add_action('analytics_cron_geo', function () {
    if (analytics_config_is_ok('internal')) {
        process_analytics(['job', 'geo']);
    }
});

if (!wp_next_scheduled('analytics_cron_geo')) {
  wp_schedule_event(time(), 'hourly', 'analytics_cron_geo');
}

/**
 * @param array $command
 * @param bool  $makeExecutable
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @return Process
 */
function process_analytics(array $command, bool $makeExecutable = true): Process
{
	global $wpdb, $table_prefix;

  $file = WP_PLUGIN_DIR . '/aesirx-analytics/assets/analytics-cli';
  $options = get_option('aesirx_analytics_plugin_options');

  $env = [
    'DBUSER' => DB_USER,
    'DBPASS' => urlencode(DB_PASSWORD),
    'DBNAME' => DB_NAME,
    'DBTYPE' => 'mysql',
    'LICENSE' => $options['license'] ?? '',
    'DBPREFIX' => $table_prefix,
  ];

	$env['DBHOST'] = DB_HOST;
	$hostData = $wpdb->parse_db_host(DB_HOST);

	if ($hostData) {
		list($env['DBHOST'], $dbPort) = $hostData;

        if (!is_null($dbPort))
        {
            $env['DBPORT'] = $dbPort;
        }
	}

	// Plugin probably updated, we need to make sure it's executable and database is up-to-date
	if ($makeExecutable && 0755 !== (fileperms($file) & 0777))
	{
		chmod($file,0755);

    if ($command != ['migrate']) {
      process_analytics(['migrate'], false);
    }
  }

  $process = new Process(array_merge([$file], $command), null, $env);
  $process->run();

  return $process;
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
  $url = esc_url(add_query_arg('page', 'aesirx-analytics-plugin', get_admin_url() . 'admin.php'));
  array_push($links, "<a href='$url'>" . __('Settings') . '</a>');
  return $links;
});

function apply_if_not_empty(array $request = null, array $fields): array
{
  $command = [];

  if (!empty($request)) {
    foreach ($fields as $from => $to) {
      if (array_key_exists($from, $request)) {
        foreach ((array) $request[$from] as $one) {
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
  $params = SimpleRouter::request()
    ->getUrl()
    ->getParams();

  foreach ($params as $key => $values) {
    $converterKey = str_replace('_', '-', $key);

    switch ($key) {
      case 'page':
      case 'page_size':
        $command[] = '--' . $converterKey;
        $command[] = $values;
        break;
      case 'sort':
      case 'with':
      case 'sort_direction':
        foreach ($values as $value) {
          $command[] = '--' . $converterKey;
          $command[] = $value;
        }
        break;
      case 'filter':
      case 'filter_not':
        foreach ($values as $keyValue => $value) {
          if (is_iterable($value)) {
            foreach ($value as $v) {
              $command[] = '--' . $converterKey;
              $command[] = $keyValue . '[]=' . $v;
            }
          } else {
            $command[] = '--' . $converterKey;
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

  if (!empty($request['attributes'] ?? [])) {
    foreach ($request['attributes'] as $name => $value) {
      $command[] = '--attributes';
      $command[] = $name . '=' . $value;
    }
  }

  return $command;
}

if (analytics_cli_exists()) {
    add_action( 'parse_request', 'analytics_url_handler' );
}

function analytics_url_handler()
{
  $options = get_option('aesirx_analytics_plugin_options');

  if (($options['storage'] ?? 'internal') != 'internal') {
    return;
  }

  //	define( 'WP_DEBUG', true );
  //	define( 'WP_DEBUG_DISPLAY', true );
  //	@ini_set( 'display_errors', 1 );
  $prefix  = site_url('', 'relative');
  $request = SimpleRouter::request();
  $requestBody = json_decode(file_get_contents('php://input'), true);
  $requestUrlParams = $request->getUrl()->getParams();
  $command = null;

  SimpleRouter::group(['prefix' => $prefix . '/visitor/v1'], function () use (
    &$command,
    $requestBody,
    $request
  ) {
    SimpleRouter::post('/init', function () use (&$command, $requestBody, $request) {
      $command = [
        'visitor',
        'init',
        'v1',
        '--ip',
        empty($requestBody['ip']) ? $request->getIp() : $requestBody['ip'],
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
      $command = ['visitor', 'start', 'v1'];

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
      $command = ['visitor', 'end', 'v1'];
      $fields = [
        'visitor_uuid' => 'visitor-uuid',
        'event_uuid' => 'event-uuid',
      ];
      $command = array_merge($command, apply_if_not_empty($requestBody, $fields));
    });
  });

  SimpleRouter::group(['middleware' => IsBackendMiddleware::class], function () use (
    &$command,
    $requestUrlParams,
      $prefix
  ) {
    SimpleRouter::get($prefix . '/flow/v1/{flow_uuid}', function (string $flowUuid) use (
      &$command,
      $requestUrlParams
    ) {
      $command = ['get', 'flow', 'v1', $flowUuid];
      $command = array_merge($command, apply_if_not_empty($requestUrlParams, ['with' => 'with']));
    });
    SimpleRouter::get($prefix . '/flow/v1/{start_date}/{end_date}', function (
      string $start,
      string $end
    ) use (&$command, $requestUrlParams) {
      $command = array_merge(
        ['get', 'flows', 'v1', '--start', $start, '--end', $end],
        apply_list_params()
      );
    });

    SimpleRouter::get($prefix . '/visitor/v1/{start_date}/{end_date}', function (
      string $start,
      string $end
    ) use (&$command, $requestUrlParams) {
      $command = array_merge(
        ['get', 'events', 'v1', '--start', $start, '--end', $end],
        apply_list_params()
      );
    });

    foreach (
      [
        'visits',
        'domains',
        'metrics',
        'pages',
        'visitors',
        'browsers',
        'browserversions',
        'languages',
        'devices',
        'countries',
        'cities',
        'isps',
        'attribute',
        'events',
        'events-name-type',
        'attribute-date',
      ]
      as $statistic
    ) {
      SimpleRouter::get($prefix . '/' . str_replace('-', '_', $statistic) . '/v1/{start_date}/{end_date}', function (
        string $start,
        string $end
      ) use (&$command, $statistic) {
        $path = $statistic == 'attribute' ? 'attributes' : $statistic;
        $command = array_merge(
          ['statistics', $path, 'v1', '--start', $start, '--end', $end],
          apply_list_params()
        );
      });
    }
  });

  try {
    SimpleRouter::start();

      if (is_null($command)) {
          return;
      }

      $process = process_analytics($command);

      if ($process->isSuccessful()) {
          echo $process->getOutput();
      } else {
          $err = $process->getErrorOutput();

          $encoded = json_decode($err);

          if (json_last_error() === JSON_ERROR_NONE) {
              throw new Exception($err);
          } else {
              throw new Exception($encoded);
          }
      }
  } catch (Throwable $e) {
    if ($e instanceof NotFoundHttpException) {
      return;
    }

    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
  }

  die();
}

register_activation_hook(__FILE__, 'initialize_aesirx_analytics_function');
function initialize_aesirx_analytics_function() {
    add_option('aesirx_do_activation_redirect', true);
}

function analytics_update_plugins(WP_Upgrader $upgrader_object, array $options ): void {
    $current_plugin_path_name = plugin_basename( __FILE__ );
    $download = false;

    if (in_array($options['action'], ['update', 'install'])
        && $options['type'] == 'plugin' ) {
        if ($options['bulk'] ?? false) {
            foreach($options['plugins'] as $each_plugin) {
                if ($each_plugin == $current_plugin_path_name) {
                    $download = true;
                    break;
                }
            }
        } elseif (property_exists($upgrader_object, 'new_plugin_data')
                && !empty($upgrader_object->new_plugin_data['Name'])
                && $upgrader_object->new_plugin_data['Name'] == 'aesirx-analytics'
                && property_exists($upgrader_object->skin, 'overwrite')
                && $upgrader_object->skin->overwrite == 'update-plugin') {
            $download = true;
        }
    }

    $options = get_option('aesirx_analytics_plugin_options');

    if ($download
        && ($options['storage'] ?? null) == 'internal') {
        try {
            download_analytics_cli();
        } catch (Throwable $e) {
            set_transient( 'analytics_update_notice', serialize($e) );
        }
    }
}

function analytics_display_update_notice(  ) {
    $notice = get_transient( 'analytics_update_notice' );
    if( $notice ) {

        $notice = unserialize($notice);

        if ($notice instanceof Throwable)
        {
            /* translators: %s: error message */
            echo '<div class="notice notice-error"><p>' . sprintf(__('Problem with Aesirx Analytics plugin install: %s', 'aesirx-analytics'), $notice->getMessage()) . '</p></div>';
        }

        delete_transient( 'analytics_update_notice' );
    }
}

add_action( 'admin_notices', 'analytics_display_update_notice' );
add_action( 'upgrader_process_complete', 'analytics_update_plugins', 10, 2);

function get_supported_arch(): string {
    $arch = null;

    if (PHP_OS === 'Linux') {
        $uname = php_uname('m');
        if (strpos($uname, 'aarch64') !== false) {
            $arch = 'aarch64';
        } else if (strpos($uname, 'x86_64') !== false) {
            $arch = 'x86_64';
        }
    }

    if (is_null($arch)) {
        throw new \DomainException("Unsupported architecture " . PHP_OS . " " . PHP_INT_SIZE);
    }

    return $arch;
}

function download_analytics_cli(): void {
    $arch = get_supported_arch();
    $file = WP_PLUGIN_DIR . '/aesirx-analytics/assets/analytics-cli';
    file_put_contents($file, fopen("https://github.com/aesirxio/analytics/releases/download/1.1.3/analytics-cli-linux-" . $arch, 'r'));
    chmod($file,0755);

    process_analytics( [ 'migrate' ] );
}

add_action('admin_init', function () {
    if (get_option('aesirx_do_activation_redirect', false)) {

        delete_option('aesirx_do_activation_redirect');

        if (wp_safe_redirect("options-general.php?page=aesirx-analytics-plugin")) {
            exit();
        }
    }

    add_action('load-options.php', function () {
        if (!array_key_exists('submit', $_REQUEST)
            || $_REQUEST['submit'] !== 'download_analytics_cli'
            || !array_key_exists('option_page', $_REQUEST)
            || $_REQUEST['option_page'] !== 'aesirx_analytics_plugin_options') {
            return;
        }

        try {
            download_analytics_cli();

            add_settings_error(
                'aesirx_analytics_plugin_options',
                'download',
                __('Library successfully downloaded.', 'aesirx-analytics'),
                'info'
            );
        }
        catch (Throwable $e)
        {
            add_settings_error(
                'aesirx_analytics_plugin_options',
                'download',
                /* translators: %s: error message */
                sprintf(__('Error: %s', 'aesirx-analytics'), $e->getMessage())
            );
        }
    });
});