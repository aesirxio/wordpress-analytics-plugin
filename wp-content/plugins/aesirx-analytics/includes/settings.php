<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_action('admin_init', function () {
  register_setting('aesirx_analytics_freemium_plugin_options', 'aesirx_analytics_freemium_plugin_options', function (
    $value
  ) {
    $valid = true;
    $input = (array) $value;

    // Ignore the user's changes and use the old database value.
    if (!$valid) {
      $value = get_option('aesirx_analytics_freemium_plugin_options');
    }

    return $value;
  });
  add_settings_section(
    'aesirx_analytics_settings',
    'AesirX Analytics & CMP',
    function () {
      echo wp_kses_post(
        /* translators: %s: URL to aesir.io read mor details */
        sprintf('<p class= "description"><strong>'. esc_html__('Note: ', 'aesirx-analytics') . '</strong>' . esc_html__('Please set Permalink Settings in WP so it is NOT set as plain.', 'aesirx-analytics') .'</p>')
      );
    },
    'aesirx_analytics_freemium_plugin'
  );

  add_settings_field(
    'aesirx_analytics_storage',
    esc_html__('AesirX First-Party Server', 'aesirx-analytics'),
    function () {
      $manifest = json_decode(
        file_get_contents(plugin_dir_path(__DIR__) . 'assets-manifest.json', true)
      );

      if ($manifest->entrypoints->plugin->assets) {
        foreach ($manifest->entrypoints->plugin->assets->js as $js) {
          wp_enqueue_script('aesrix_bi' . md5($js), plugins_url($js, __DIR__), false, '1.0', true);
        }
      }
    },
    'aesirx_analytics_freemium_plugin',
    'aesirx_analytics_settings'
  );
});

add_action('admin_menu', function () {
  add_menu_page(
    'AesirX BI Dashboard',
    'AesirX BI',
    'manage_options',
    'aesirx-bi-dashboard',
    function () {
      ?><div id="biapp" class="aesirxui"></div><?php
    },
    plugins_url( 'aesirx-analytics/assets/images-plugin/AesirX_BI_icon.png'),
    3
  );
  add_submenu_page(
    'aesirx-bi-dashboard',
    'AesirX BI Dashboard',
    'Dashboard',
    'manage_options',
    'aesirx-bi-dashboard',
    function () {
      ?><div id="biapp" class="aesirxui"></div><?php
    },
    3);
  add_submenu_page(
    'aesirx-bi-dashboard',
    'AesirX BI Acquisition',
    'Acquisition',
    'manage_options',
    'aesirx-bi-acquisition',
    function () {
      ?><div id="biapp" class="aesirxui"></div><?php
    },
    3);
  add_submenu_page(
    'aesirx-bi-acquisition',
    'AesirX BI Acquisition Search Engine',
    'Acquisition Search Engine',
    'manage_options',
    'aesirx-bi-acquisition-search-engines',
    function () {
      ?><div id="biapp" class="aesirxui"></div><?php
    },
    3);
  add_submenu_page(
    'aesirx-bi-acquisition',
    'AesirX BI Acquisition Campaigns',
    'Acquisition Campaigns',
    'manage_options',
    'aesirx-bi-acquisition-campaigns',
    function () {
      ?><div id="biapp" class="aesirxui"></div><?php
    },
    3);
  add_submenu_page(
    'aesirx-bi-dashboard',
    'AesirX BI Behavior',
    'Behavior',
    'manage_options',
    'aesirx-bi-behavior',
    function () {
      ?><div id="biapp" class="aesirxui"></div><?php
    },
    3);
  add_submenu_page(
    'aesirx-bi-behavior',
    'AesirX BI Behavior Events',
    'Behavior Events',
    'manage_options',
    'aesirx-bi-behavior-events',
    function () {
      ?><div id="biapp" class="aesirxui"></div><?php
    },
    3);
  add_submenu_page(
    'aesirx-bi-behavior',
    'AesirX BI Behavior Events Generator',
    'Behavior Events Generator',
    'manage_options',
    'aesirx-bi-behavior-events-generator',
    function () {
      ?><div id="biapp" class="aesirxui"></div><?php
    },
    3);
  add_submenu_page(
    'aesirx-bi-behavior',
    'AesirX BI Behavior Outlinks',
    'Behavior Outlinks',
    'manage_options',
    'aesirx-bi-behavior-outlinks',
    function () {
      ?><div id="biapp" class="aesirxui"></div><?php
    },
    3);
  add_submenu_page(
    'aesirx-bi-behavior',
    'AesirX BI Behavior User Flow',
    'Behavior User Flow',
    'manage_options',
    'aesirx-bi-behavior-users-flow',
    function () {
      ?><div id="biapp" class="aesirxui"></div><?php
    },
    3);
  add_submenu_page(
    'aesirx-bi-dashboard',
    'AesirX BI UTM Tracking',
    'Tracking',
    'manage_options',
    'aesirx-bi-utm-tracking',
    function () {
      ?><div id="biapp" class="aesirxui"></div><?php
    },
    5);
  add_submenu_page(
    'aesirx-bi-utm-tracking',
    'AesirX BI UTM Tracking Generator',
    'UTM Tracking Generator',
    'manage_options',
    'aesirx-bi-utm-tracking-generator',
    function () {
      ?><div id="biapp" class="aesirxui"></div><?php
    },
    5);
  add_submenu_page(
    'aesirx-bi-dashboard',
    'AesirX BI Visitors',
    'Visitors',
    'manage_options',
    'aesirx-bi-visitors',
    function () {
      ?><div id="biapp" class="aesirxui"></div><?php
    },
    6);
  add_submenu_page(
    'aesirx-bi-visitors',
    'AesirX BI Visitors Locations',
    'Locations',
    'manage_options',
    'aesirx-bi-visitors-locations',
    function () {
      ?><div id="biapp" class="aesirxui"></div><?php
    },
    6);
  add_submenu_page(
    'aesirx-bi-visitors',
    'AesirX BI Visitors Flow',
    'Flow',
    'manage_options',
    'aesirx-bi-visitors-flow',
    function () {
      ?><div id="biapp" class="aesirxui"></div><?php
    },
    6);

  add_submenu_page(
    'aesirx-bi-visitors',
    'AesirX BI Visitors Platforms',
    'Platforms',
    'manage_options',
    'aesirx-bi-visitors-platforms',
    function () {
      ?><div id="biapp" class="aesirxui"></div><?php
    },
    6);
});

add_action('admin_init', 'aesirx_analytics_redirect_config', 1);
function aesirx_analytics_redirect_config() {
  $current_url = home_url(add_query_arg(null, null));
  $parsed_url = wp_parse_url($current_url);
  
  if (isset($parsed_url['query'])) {
    $query_params = wp_parse_args($parsed_url['query']);

    $query_params = array_map('sanitize_text_field', $query_params);

    if (isset($query_params['page']) && strpos($query_params['page'], 'aesirx-bi') !== false) {
      if (!isset($query_params['aesirx_analytics_nonce']) || !wp_verify_nonce($query_params['aesirx_analytics_nonce'], 'aesirx_analytics_submenu')) {
        wp_die('Nonce verification failed');
      }

      $checked_page = array('aesirx-bi-dashboard', 'aesirx-bi-visitors', 'aesirx-bi-behavior', 'aesirx-bi-utm-tracking');
    
      if (in_array($query_params['page'], $checked_page, true) && !aesirx_analytics_freemium_config_is_ok()) {
    
        wp_redirect('/wp-admin/plugins.php');
        die;
      }
    }
  }
}

add_action('admin_enqueue_scripts', function ($hook) {
  if ($hook === 'toplevel_page_aesirx-bi-dashboard' || 
      $hook === 'toplevel_page_aesirx-bi-visitors' || 
      $hook === 'toplevel_page_aesirx-bi-behavior' || 
      $hook === 'toplevel_page_aesirx-bi-utm-tracking' || 
      $hook === 'toplevel_page_aesirx-bi-acquisition' || 
      $hook === 'aesirx-bi_page_aesirx-bi-visitors' ||
      $hook === 'admin_page_aesirx-bi-visitors-locations' || 
      $hook === 'admin_page_aesirx-bi-visitors-flow' || 
      $hook === 'admin_page_aesirx-bi-visitors-platforms' || 
      $hook === 'aesirx-bi_page_aesirx-bi-behavior' ||
      $hook === 'admin_page_aesirx-bi-behavior-events' ||
      $hook === 'admin_page_aesirx-bi-behavior-events-generator' ||
      $hook === 'admin_page_aesirx-bi-behavior-outlinks' ||
      $hook === 'admin_page_aesirx-bi-behavior-users-flow' ||
      $hook === 'aesirx-bi_page_aesirx-bi-utm-tracking' ||
      $hook === 'admin_page_aesirx-bi-utm-tracking-generator' ||
      $hook === 'aesirx-bi_page_aesirx-bi-acquisition' ||
      $hook === 'admin_page_aesirx-bi-acquisition-search-engines' ||
      $hook === 'admin_page_aesirx-bi-acquisition-campaigns') {
    wp_enqueue_script('aesirx-analytics-notice', plugins_url('assets/vendor/aesirx-analytics-notice.js', __DIR__), array('jquery'), false, true);
    $options = get_option('aesirx_analytics_freemium_plugin_options');

    $protocols = ['http://', 'https://'];
    $domain = str_replace($protocols, '', site_url());
    $streams = [['name' => get_bloginfo('name'), 'domain' => $domain]];
    $endpoint = get_bloginfo('url');

    $manifest = json_decode(
      file_get_contents(plugin_dir_path(__DIR__) . 'assets-manifest.json', true)
    );

    if ($manifest->entrypoints->bi->assets) {
      foreach ($manifest->entrypoints->bi->assets->js as $js) {
        wp_enqueue_script('aesrix_bi' . md5($js), plugins_url($js, __DIR__), false, '1.0', true);
      }
    }

    $jwt = '';
    $cmp_link = aesirx_analytics_freemium_plugin_check_consent_active()
      ? admin_url( 'admin.php?page=aesirx-consent-management-plugin' )
      : 'https://wordpress.org/plugins/aesirx-cmp/';
    wp_register_script( 'aesrix_bi_window', '', array(), '1.0', false );

    wp_enqueue_script('aesrix_bi_window');

    wp_add_inline_script(
      'aesrix_bi_window',
      'window.env = {};
      window.env.REACT_APP_BI_ENDPOINT_URL = "' . esc_url($endpoint) . '";
		  window.env.REACT_APP_DATA_STREAM = JSON.stringify(' . wp_json_encode($streams) . ');
      window.env.REACT_APP_CMP_LINK = "' . esc_url( $cmp_link ) . '";
		  window.env.PUBLIC_URL= "' . esc_url(plugin_dir_url(__DIR__)) . '";
      ' . htmlspecialchars($jwt, ENT_NOQUOTES),
    );
  }
});

/**
 * Custom escape function for Aesirx Analytics.
 * Escapes HTML attributes in a string using a specified list of allowed HTML elements and attributes.
 *
 * @param string $string The input string to escape HTML attributes from.
 * @return string The escaped HTML string.
 */

 function aesirx_analytics_freemium_escape_html() {
  $allowed_html = array(
    'input' => array(
        'type'  => array(),
        'id'    => array(),
        'name'  => array(),
        'value' => array(),
        'class' => array(),
        'checked' => array(),
        'placeholder' => array(),
     ),
     'strong' => array(),
     'a' => array(
      'href'  => array(),
      'target'    => array(),
      'class'    => array(),
      'style'    => array(),
     ),
     'p' => array(
      'class' => array(),
      'span' => array(
        'class' => array(),
      ),
     ),
     'span' => array(
      'class' => array(),
     ),
     'h3' => array(),
     'ul' => array(
      'class' => array(),
     ),
     'li' => array(),
     'br' => array(),
     'label' => array(
      'for'  => array(),
      'class'  => array(),
     ),
     'img' => array(
      'src'  => array(),
      'class'  => array(),
      'width'  => array(),
      'height'  => array(),
     ),
     'iframe' => array(
      'src'  => array(),
     ),
     'div' => array(
        'id' => array(),
        'class' => array(),
     ),
     'button' => array(
        'type'  => array(),
        'id'    => array(),
        'name'  => array(),
        'value' => array(),
        'class' => array(),
    ),
  );

  return $allowed_html;
}

function aesirx_analytics_add_nonce_menu_item() {
  ?>
  <script type="text/javascript">
  jQuery(document).ready(function($) {
    $('#adminmenu .toplevel_page_aesirx-bi-dashboard > a').attr('href', function() {
      return aesirx_analytics_add_nonce_url($(this));
    });

    $('#adminmenu .toplevel_page_aesirx-bi-dashboard ul li').each(function() {
      const link = $(this).find('a');
      if (link.length) {
        link.attr('href', aesirx_analytics_add_nonce_url(link));
      }
    });
    $('#adminmenu #toplevel_page_aesirx-bi-dashboard .wp-submenu').css('display', 'none');
    function aesirx_analytics_add_nonce_url(url) {
      const originalHref = url.attr('href');
      const page = originalHref.match(/[?&]page=([^&]*)/);
      var nonce = '<?php echo esc_html(wp_create_nonce("aesirx_analytics_submenu")); ?>';
      return originalHref + '&aesirx_analytics_nonce=' + nonce;
    }
  });
  </script>
  <?php
}
add_action('admin_footer', 'aesirx_analytics_add_nonce_menu_item');

