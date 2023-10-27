<?php
/**
 * Plugin Name: AesirX Analytics
 * Plugin URI: https://analytics.aesirx.io?utm_source=wpplugin&utm_medium=web&utm_campaign=wordpress&utm_id=aesirx&utm_term=wordpress&utm_content=analytics
 * Description: Aesirx analytics plugin. When you join forces with AesirX, you're not just becoming a Partner - you're also becoming a freedom fighter in the battle for privacy! Earn 25% Affiliate Commission <a href="https://aesirx.io/seed-round?utm_source=wpplugin&utm_medium=web&utm_campaign=wordpress&utm_id=aesirx&utm_term=wordpress&utm_content=analytics">[Click to Join]</a>
 * Version: 2.2.0
 * Author: aesirx.io
 * Author URI: https://aesirx.io/
 * Domain Path: /languages
 * Text Domain: aesirx-analytics
 * Requires PHP: 7.2
 **/

use AesirxAnalytics\CliFactory;
use AesirxAnalytics\Track\ApiTracker;
use AesirxAnalytics\Track\CliTracker;
use AesirxAnalyticsLib\Exception\ExceptionWithResponseCode;
use AesirxAnalytics\Route\Middleware\IsBackendMiddleware;
use AesirxAnalyticsLib\Exception\ExceptionWithErrorType;
use AesirxAnalyticsLib\RouterFactory;
use Pecee\Http\Request;
use Pecee\SimpleRouter\Exceptions\NotFoundHttpException;
use Pecee\SimpleRouter\Route\RouteUrl;

require_once WP_PLUGIN_DIR . '/aesirx-analytics/vendor/autoload.php';
require_once 'includes/settings.php';
require_once 'class-tgm-plugin-activation.php';

function analytics_config_is_ok(string $isStorage = null): bool {
    $options = get_option('aesirx_analytics_plugin_options');
    $res = (!empty($options['storage'])
        && (
            ($options['storage'] == 'internal' && !empty($options['license']) && CliFactory::getCli()->analyticsCliExists())
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
        wp_register_script('aesirx-analytics', plugins_url('assets/js/analytics.js', __FILE__), [], true, true);
        wp_enqueue_script('aesirx-analytics');

        $options = get_option('aesirx_analytics_plugin_options');

        $domain =
            ($options['storage'] ?? 'internal') == 'internal'
                ? get_bloginfo('url')
                : rtrim($options['domain'] ?? '', '/');

        $consent =
            ($options['consent'] ?? 'false') == 'true'
                ? 'false'
                : 'true';

        $trackEcommerce = ($options['track_ecommerce'] ?? 'true') == 'true' ? 'true': 'false';

        $clientId = $options['clientid'] ?? '';
        $secret = $options['secret'] ?? '';

        wp_add_inline_script('aesirx-analytics', 'window.aesirx1stparty="' . $domain . '";window.disableAnalyticsConsent="' . $consent . '";window.aesirxClientID="' . $clientId . '";window.aesirxClientSecret="' . $secret . '";window.aesirxTrackEcommerce="' . $trackEcommerce . '";', 'before');
    });

    // Track e-commerce
    add_action( 'init', function (): void {
        $options = get_option('aesirx_analytics_plugin_options');

        if (is_admin()
            || ($options['track_ecommerce'] ?? 'true') != 'true')
        {
            return;
        }

        if (!session_id()) {
            session_start();
        }

        $flowUuid = $_SESSION['analytics_flow_uuid'] ?? null;

        if (is_null($flowUuid))
        {
            return;
        }

        if (analytics_config_is_ok('internal'))
        {
            $tracker = new CliTracker(CliFactory::getCli());
        }
        else
        {
            $tracker = new ApiTracker(rtrim($options['domain'] ?? '', '/'));
        }

        (new \AesirxAnalytics\Integration\Woocommerce($tracker, $flowUuid))
            ->registerHooks();
    } );
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
        CliFactory::getCli()->processAnalytics(['job', 'geo']);
    }
});

if (!wp_next_scheduled('analytics_cron_geo')) {
  wp_schedule_event(time(), 'hourly', 'analytics_cron_geo');
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
  $url = esc_url(add_query_arg('page', 'aesirx-analytics-plugin', get_admin_url() . 'admin.php'));
  array_push($links, "<a href='$url'>" . __('Settings') . '</a>');
  return $links;
});

if (CliFactory::getCli()->analyticsCliExists()) {
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
    $callCommand = function (array $command): string {
        try
        {
            $process = CliFactory::getCli()->processAnalytics($command);
        }
        catch (Throwable $e)
        {
            $code = 500;

            if ($e instanceof ExceptionWithErrorType)
            {
                switch ($e->getErrorType())
                {
                    case "NotFoundError":
                        $code = 404;
                        break;
                    case "ValidationError":
                        $code = 400;
                        break;
                    case "Rejected":
                        $code = 406;
                        break;
                }
            }

            throw new ExceptionWithResponseCode($e->getMessage(), $code, $e->getCode(), $e);
        }

        if (!headers_sent()) {
            header( 'Content-Type: application/json; charset=utf-8' );
        }
        return $process->getOutput();
    };

  try {
      $router = (new RouterFactory(
          $callCommand,
          new IsBackendMiddleware(),
          null,
          site_url( '', 'relative' ))
      )
          ->getSimpleRouter();

      $router->addRoute(
          (new RouteUrl('/remember_flow/{flow}', static function (string $flow): string {
              if (!session_id()) {
                  session_start();
              }

              $_SESSION['analytics_flow_uuid'] = $flow;

              return json_encode(true);
          }))
              ->setWhere(['flow' => '[0-9a-f]{8}-(?:[0-9a-f]{4}-){3}[0-9a-f]{12}'])
              ->setRequestMethods([Request::REQUEST_TYPE_POST])
      );

      echo $router->start();
  } catch (Throwable $e) {
    if ($e instanceof NotFoundHttpException) {
      return;
    }

    if ($e instanceof ExceptionWithResponseCode) {
        $code = $e->getResponseCode();
    } else {
        $code = 500;
    }

      if (!headers_sent()) {
          header( 'Content-Type: application/json; charset=utf-8' );
      }
    http_response_code($code);
    echo json_encode([
        'error' => $e->getMessage(),
    ]);
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
            CliFactory::getCli()->downloadAnalyticsCli();
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
            CliFactory::getCli()->downloadAnalyticsCli();

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