<?php
/**
 * Plugin Name: AesirX Analytics
 * Plugin URI: https://analytics.aesirx.io?utm_source=wpplugin&utm_medium=web&utm_campaign=wordpress&utm_id=aesirx&utm_term=wordpress&utm_content=analytics
 * Description: Aesirx analytics plugin. When you join forces with AesirX, you're not just becoming a Partner - you're also becoming a freedom fighter in the battle for privacy! Earn 25% Affiliate Commission <a href="https://aesirx.io/partner?utm_source=wpplugin&utm_medium=web&utm_campaign=wordpress&utm_id=aesirx&utm_term=wordpress&utm_content=analytics">[Click to Join]</a>
 * Version: 4.2.8
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
        $translation_array = array(
            'txt_shield_of_privacy' => __( 'Shield of Privacy', 'aesirx-analytics' ),
            'txt_you_can_revoke' => __( 'Revoke your consent for data use whenever you wish.', 'aesirx-analytics' ),
            'txt_manage_consent' => __( 'Manage Decentralized Consent', 'aesirx-analytics' ),
            'txt_revoke_consent' => __( 'Revoke Consent', 'aesirx-analytics' ),
            'txt_yes_i_consent' => __( 'Consent', 'aesirx-analytics' ),
            'txt_reject_consent' => __( 'Reject', 'aesirx-analytics' ),
            'txt_please_connect' => __( 'Please connect your Web3 wallet', 'aesirx-analytics' ),
            'txt_please_sign' => __( 'Please sign the message on your wallet twice and wait for it to be saved.', 'aesirx-analytics' ),
            'txt_saving' => __( 'Saving...', 'aesirx-analytics' ),
            'txt_please_connect_your_wallet' => __( 'Please connect to your wallet', 'aesirx-analytics' ),
            'txt_connecting' => __( 'Connecting', 'aesirx-analytics' ),
            'txt_tracking_data_privacy' => __( 'TRACKING AND DATA PRIVACY PREFERENCES', 'aesirx-analytics' ),
            'txt_about' => __( 'About', 'aesirx-analytics' ),
            'txt_detail' => __( 'Details', 'aesirx-analytics' ),
            'txt_change_consent' => __( 'Decentralized Consent', 'aesirx-analytics' ),
            'txt_manage_your_consent' => __( 'Manage Your Consent Preferences', 'aesirx-analytics' ),
            'txt_choose_how_we_use' => __( 'Choose how we use your data: "Reject" data collection, allow tracking ["Consent"], or use "Decentralized Consent" for more control over your personal data & rewards.', 'aesirx-analytics' ),
            'txt_choose_how_we_use_simple' => __( 'Choose how we use your data: "Reject" data collection, allow tracking ["Consent"].', 'aesirx-analytics' ),
            'txt_by_consenting' => __( 'By consenting, you allow us to collect & use your data for:', 'aesirx-analytics' ),
            'txt_analytics_behavioral' => __( 'Analytics & Behavioral Data: To improve our services & personalize your experience.', 'aesirx-analytics' ),
            'txt_form_data' => __( 'Form Data: When you contact us.', 'aesirx-analytics' ),
            'txt_please_note' => __( 'Please note', 'aesirx-analytics' ),
            'txt_we_do_not_share' => __( 'We do not share your data with third parties without your explicit consent.', 'aesirx-analytics' ),
            'txt_you_can_opt_in' => __( 'You can opt-in later for specific features without giving blanket consent.', 'aesirx-analytics' ),
            'txt_for_more_details' => __( "For more details, refer to our <a class='text-success fw-semibold text-decoration-underline' href='https://aesirx.io/privacy-policy' target='_blank'>privacy policy.</a>", 'aesirx-analytics' ),
            'txt_benefit' => __( 'Benefits', 'aesirx-analytics' ),
            'txt_control_your_data' => __( "<span class='fw-semibold text-primary'>Control your data:</span> Choose your preferred level of data collection & tracking.", 'aesirx-analytics' ),
            'txt_earn_rewards' => __( "<span class='fw-semibold text-primary'>Earn rewards:</span> Participate in decentralized consent for privacy & rewards.", 'aesirx-analytics' ),
            'txt_transparent_data' => __( "<span class='fw-semibold text-primary'>Transparent data collection practices:</span> Understand how your data is collected & used.", 'aesirx-analytics' ),
            'txt_understanding_your_privacy' => __( "Understanding Your Privacy Choices", 'aesirx-analytics' ),
            'txt_reject_no_data' => __( "<span class='fw-semibold text-primary'>Reject:</span> No data will be collected or loaded except for anonymized page views & rejections. Some personalization features may be disabled.", 'aesirx-analytics' ),
            'txt_consent_first_third_party' => __( "<span class='fw-semibold text-primary'>Consent:</span> First & third-party tracking data will be collected to enhance your experience.", 'aesirx-analytics' ),
            'txt_decentralizered_consent_choose' => __( "<span class='fw-semibold text-primary'>Decentralized Consent:</span> Choose Decentralized Wallets or Decentralized Wallet + Shield of Privacy. Both options let you manage & revoke consent on-site or through AesirX dApp, plus earn rewards from digital marketing activities.", 'aesirx-analytics' ),
            'txt_our_commitment_in_action' => __( "Our Commitment in Action", 'aesirx-analytics' ),
            'txt_private_protection' => __( "<span class='fw-semibold text-primary'>Privacy Protection:</span> Users have full control over their data, ensuring maximum privacy.", 'aesirx-analytics' ),
            'txt_enables_compliance' => __( "<span class='fw-semibold text-primary'>Enables Compliance:</span> Using Shield of Privacy (SoP) ensures compliance with GDPR, CCPA, ePrivacy Directive, & other data protection regulations.", 'aesirx-analytics' ),
            'txt_proactive_protection' => __( "<span class='fw-semibold text-primary'>Proactive Protection:</span> We enhance privacy measures to safeguard your data integrity.", 'aesirx-analytics' ),
            'txt_flexible_consent' => __( "<span class='fw-semibold text-primary'>Flexible Consent:</span> You can withdraw your consent anytime on-site or via our <a class='text-success fw-semibold text-decoration-underline' href='https://dapp.shield.aesirx.io' target='_blank'>dApp</a> (Decentralized Application).", 'aesirx-analytics' ),
            'txt_learn_more' => __( "<span class='fw-semibold text-primary'>Learn More:</span> Discover our approach to data processing in our <a class='text-success fw-semibold text-decoration-underline' href='https://aesirx.io/privacy-policy' target='_blank'>Privacy Policy</a>.", 'aesirx-analytics' ),
            'txt_for_business' => __( "<span class='fw-semibold text-primary'>For Businesses:</span> Enhance trust, secure user identities, & prevent breaches.", 'aesirx-analytics' ),
            'txt_more_info_at' => __( "More info at <a class='text-success fw-semibold text-decoration-underline' href='https://shield.aesirx.io' target='_blank'>https://shield.aesirx.io</a>.", 'aesirx-analytics' ),
            'txt_select_your_preferred' => __( "Select your preferred decentralized consent option:", 'aesirx-analytics' ),
            'txt_decentralized_wallet' => __( "Decentralized Consent", 'aesirx-analytics' ),
            'txt_decentralized_wallet_will_be_loaded' => __( "Decentralized consent will be loaded", 'aesirx-analytics' ),
            'txt_both_first_party_third_party' => __( "Both first-party & third-party tracking data will be activated.", 'aesirx-analytics' ),
            'txt_all_consented_data_will_be_collected' => __( "All consented data will be collected.", 'aesirx-analytics' ),
            'txt_users_can_revoke' => __( "Users can revoke consent on-site at any time.", 'aesirx-analytics' ),
            'txt_decentralized_wallet_shield' => __( "Decentralized Consent + Shield of Privacy", 'aesirx-analytics' ),
            'txt_users_can_revoke_dapp' => __( "Users can revoke consent on-site or from the AesirX dApp at any time.", 'aesirx-analytics' ),
            'txt_users_can_earn' => __( "Users can earn rewards from digital marketing activities.", 'aesirx-analytics' ),
            'txt_continue' => __( "Continue", 'aesirx-analytics' ),
            'txt_back' => __( "Back", 'aesirx-analytics' ),
            'txt_you_have_chosen' => __( "You've chosen to reject data collection:", 'aesirx-analytics' ),
            'txt_only_anonymized' => __( "Only anonymized page views & limited features will be available. To access all website features, including personalized content & enhanced functionality, please choose an option:", 'aesirx-analytics' ),
            'txt_consent_allow_data' => __( "<span class='fw-semibold text-primary'>Consent:</span> Allow data collection for analytics, form data (when you contact us), & behavioral & event tracking, with the option to opt-in for specific features.", 'aesirx-analytics' ),
            'txt_decentralized_consent_allow_data' => __( "<span class='fw-semibold text-primary'>Decentralized Consent:</span> Allow data collection for analytics, form data (when you contact us), & behavioral & event tracking, with the option to revoke consent, opt-in for specific features, & earn rewards from digital marketing activities.", 'aesirx-analytics' ),
            'txt_you_can_revoke_on_the_site' => __( "You can revoke consent on the site or any explicit opt-in consent, such as payment processing, at any time", 'aesirx-analytics' ),
            'txt_revoke_opt_in' => __( "Revoke Opt-In Consent", 'aesirx-analytics' ),
            'txt_revoke_opt_in_payment' => __( "Revoke Opt-In Consent for Payment Processing", 'aesirx-analytics' ),
            'txt_revoke_opt_in_advisor' => __( "Revoke Opt-In Consent for AesirX Privacy Advisor AI", 'aesirx-analytics' ),
            'txt_revoke_consent_for_the_site' => __( "Revoke Consent for the site", 'aesirx-analytics' ),
            'txt_consent_nanagement' => __( "Consent Management", 'aesirx-analytics' ),
            'txt_details' => __( "Details", 'aesirx-analytics' )
        );
        wp_localize_script( 'aesirx-analytics', 'aesirx_analytics_translate', $translation_array );
        wp_enqueue_script('aesirx-analytics');

        $options = get_option('aesirx_analytics_freemium_plugin_options');

        $domain = get_bloginfo('url');

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
  $url = add_query_arg(
        [
            'page'                    => 'aesirx-bi-dashboard',
            'aesirx_analytics_nonce'  => $nonce,
        ],
        admin_url('admin.php')
    );
  array_push($links, "<a href='$url'>" . esc_html__('Statistics', 'aesirx-analytics') . '</a>');
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

add_action('admin_init', function () {
    if (get_option('aesirx_analytics_freemium_do_activation_redirect', false)) {

        delete_option('aesirx_analytics_freemium_do_activation_redirect');
    }
});
