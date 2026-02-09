<?php
/**
 * Plugin Name: AesirX Analytics
 * Plugin URI: https://analytics.aesirx.io?utm_source=wpplugin&utm_medium=web&utm_campaign=wordpress&utm_id=aesirx&utm_term=wordpress&utm_content=analytics
 * Description: Aesirx analytics plugin. When you join forces with AesirX, you're not just becoming a Partner - you're also becoming a freedom fighter in the battle for privacy! Earn 25% Affiliate Commission <a href="https://aesirx.io/partner?utm_source=wpplugin&utm_medium=web&utm_campaign=wordpress&utm_id=aesirx&utm_term=wordpress&utm_content=analytics">[Click to Join]</a>
 * Version: 5.0.1
 * Author: aesirx.io
 * Author URI: https://aesirx.io/
 * Domain Path: /languages
 * Text Domain: aesirx-analytics
 * Requires PHP: 7.4
 * License: GPL v3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * 
 **/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use AesirxAnalytics\CliFactory;
use AesirxAnalytics\Track\ApiTracker;
use AesirxAnalytics\Track\CliTracker;
use AesirxAnalyticsLib\Exception\ExceptionWithResponseCode;
use AesirxAnalytics\Route\Middleware\IsBackendMiddleware;
use AesirxAnalyticsLib\RouterFactory;
use Pecee\Http\Request;
use Pecee\SimpleRouter\Exceptions\NotFoundHttpException;
use Pecee\SimpleRouter\Route\RouteUrl;
use AesirxAnalytics\Migrator\MigratorMysql;

require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings.php';

include_once(ABSPATH . 'wp-admin/includes/plugin.php');

function aesirx_analytics_freemium_plugin_check_consent_active(): bool {
    return is_plugin_active('aesirx-consent/aesirx-consent.php');
}

function aesirx_analytics_freemium_config_is_ok(string $isStorage = null): bool {
    return true;
}
if (aesirx_analytics_freemium_config_is_ok()) {
    add_action('wp_enqueue_scripts', function (): void {
        if (!aesirx_analytics_freemium_plugin_check_consent_active()) {
            wp_register_script('aesirx-analytics', plugins_url('assets/vendor/statistic.js', __FILE__), [], false,  array(
                'in_footer' => false,
            ));
        }
        wp_enqueue_script('aesirx-analytics');
        $origin = wp_parse_url( home_url(), PHP_URL_SCHEME ) . '://' . wp_parse_url( home_url(), PHP_URL_HOST );
        $domain = $origin;

        wp_add_inline_script(
            'aesirx-analytics',
            'window.aesirx1stparty="' . esc_attr($domain) . '";',
            'before');
    });
}

if (is_plugin_active('wp-crontrol/wp-crontrol.php')) {
    add_action('analytics_cron_geo', function () {
        if (aesirx_analytics_freemium_config_is_ok('internal')) {
            CliFactory::getCli()->processAnalytics(['job', 'geo']);
        }
    });
    
    if (!wp_next_scheduled('analytics_cron_geo')) {
      wp_schedule_event(time(), 'hourly', 'analytics_cron_geo');
    }    
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
    $nonce = wp_create_nonce('aesirx_analytics_submenu');
    $stats_url = add_query_arg(
            [
                'page'                    => 'aesirx-bi-dashboard',
                'aesirx_analytics_nonce'  => $nonce,
            ],
            admin_url('admin.php')
        );
    $links[] = sprintf(
        '<a href="%s">%s</a>',
        esc_url($stats_url),
        esc_html__('Statistics', 'aesirx-analytics')
    );


    $pro_url = 'https://aesirx.io/solutions/analytics';

    $links[] = sprintf(
        '<a href="%s" target="_blank" style="color:#d63638;font-weight:600;">
            %s
        </a>',
        esc_url($pro_url),
        esc_html__('Upgrade to Pro', 'aesirx-analytics')
    );
  return $links;
});

add_action( 'parse_request', 'aesirx_analytics_freemium_url_handler' );


function aesirx_analytics_freemium_url_handler()
{
    $options = get_option('aesirx_analytics_freemium_plugin_options');

    if (($options['storage'] ?? 'internal') !== 'internal') {
        return;
    }

    $callCommand = function (array $command): string {
        try
        {
            $data = CliFactory::getCli()->processAnalytics($command);
        }
        catch (Exception $e)
        {
            $data = wp_json_encode([
                'error' => $e->getMessage()
            ]);
        }

        if (!headers_sent()) {
            header( 'Content-Type: application/json; charset=utf-8' );
        }
        return $data;
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

                set_transient('analytics_flow_uuid', $flow, HOUR_IN_SECONDS);

                return wp_json_encode(true);
            }))
                ->setWhere(['flow' => '[0-9a-f]{8}-(?:[0-9a-f]{4}-){3}[0-9a-f]{12}'])
                ->setRequestMethods([Request::REQUEST_TYPE_POST])
        );

        echo wp_kses_post($router->start());
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
        echo wp_json_encode([
            'error' => $e->getMessage(),
        ]);
    }

    die();
}

register_activation_hook(__FILE__, 'aesirx_analytics_freemium_initialize_function');
function aesirx_analytics_freemium_initialize_function() {
    global $wpdb;

    //Add migration table
    MigratorMysql::aesirx_analytics_create_migrator_table_query();
    $migration_list = array_column(MigratorMysql::aesirx_analytics_fetch_rows(), 'name');

    $files = glob(plugin_dir_path( __FILE__ ) . 'src/Migration/*.php');
    foreach ($files as $file) {
        $realpath = realpath($file);
        if ($realpath && strpos($realpath, plugin_dir_path(__FILE__) . 'src/Migration/') === 0) {
            include_once $realpath; // Safe inclusion
            $file_name = basename($realpath, ".php");
            if (!in_array($file_name, $migration_list, true)) {
                MigratorMysql::aesirx_analytics_add_migration_query($file_name);
                $sql = $aesirx_analytics_freemium_sql ?? []; // Ensure $sql is an array
                foreach ($sql as $each_query) {
                    // Used placeholders and $wpdb->prepare() in variable $each_query
                    // Need $wpdb->query() for ALTER TABLE
                    $wpdb->query($each_query); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                }
            }
        }
    }
    add_option('aesirx_analytics_freemium_do_activation_redirect', true);
}

function aesirx_analytics_freemium_display_update_notice(  ) {
    $notice = get_transient( 'aesirx_analytics_update_notice' );
    if( $notice ) {

        $notice = json_decode($notice, true);

        if ($notice instanceof Throwable)
        {
            /* translators: %s: error message */
            // using custom function to escape HTML in error message
            echo wp_kses('<div class="notice notice-error"><p>' . esc_html__('Problem with Aesirx Analytics plugin install', 'aesirx-analytics') . '</p></div>', array(
                'p' => array(
                    'class' => array(),
                    'span' => array(
                    'class' => array(),
                ),
                'div' => array(
                    'id' => array(),
                    'class' => array(),
                ),
            )));
        }

        delete_transient( 'aesirx_analytics_update_notice' );
    }
}

add_action( 'admin_notices', 'aesirx_analytics_freemium_display_update_notice' );

register_activation_hook(__FILE__, function () {
    if (!defined('AESIRX_ANALYTICS_PRO')) {
        set_transient(
            'aesirx_analytics_pro_upsell_notice',
            true,
            DAY_IN_SECONDS * 7 // show for 7 days max
        );
    }
});

function aesirx_analytics_display_pro_upsell_notice() {

    if (!current_user_can('manage_options')) {
        return;
    }

    if (defined('AESIRX_ANALYTICS_PRO')) {
        return;
    }

    if (!get_transient('aesirx_analytics_pro_upsell_notice')) {
        return;
    }

    $pro_url = 'https://aesirx.io/solutions/analytics';
    ?>
    <div class="notice notice-info is-dismissible aesirx-pro-upsell">
        <p>
            <strong><?php esc_html_e('Unlock AesirX Analytics Pro 🚀', 'aesirx-analytics'); ?></strong><br>
            <?php esc_html_e(
                'Get real-time visitors, UTM & Tag Value Mapping, advanced Analytics dashboards, and priority support.',
                'aesirx-analytics'
            ); ?>
        </p>
        <p>
            <a href="<?php echo esc_url($pro_url); ?>"
               target="_blank"
               class="button button-primary">
                <?php esc_html_e('Upgrade to Pro', 'aesirx-analytics'); ?>
            </a>
        </p>
    </div>
    <?php
}
add_action('admin_notices', 'aesirx_analytics_display_pro_upsell_notice');

add_action('admin_enqueue_scripts', function () {
    wp_enqueue_script('jquery');

    wp_add_inline_script(
        'jquery',
        "
        jQuery(document).on('click', '.aesirx-pro-upsell .notice-dismiss', function () {
            jQuery.post(ajaxurl, {
                action: 'aesirx_dismiss_pro_upsell'
            });
        });
        "
    );
});

add_action('wp_ajax_aesirx_dismiss_pro_upsell', function () {
    delete_transient('aesirx_analytics_pro_upsell_notice');
    wp_die();
});

add_action('admin_init', function () {
    if (get_option('aesirx_analytics_freemium_do_activation_redirect', false)) {

        delete_option('aesirx_analytics_freemium_do_activation_redirect');
    }
});
