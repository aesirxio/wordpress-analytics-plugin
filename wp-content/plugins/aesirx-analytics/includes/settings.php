<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_action('admin_init', function () {
  register_setting('aesirx_analytics_plugin_options', 'aesirx_analytics_plugin_options', function (
    $value
  ) {
    $valid = true;
    $input = (array) $value;

    if ($input['storage'] == 'internal') {
      if (empty($input['license'])) {
        add_settings_error(
          'aesirx_analytics_plugin_options',
          'license',
          esc_html__('Please register your license at Signup.aesirx.io to enable the external first-party server.', 'aesirx-analytics'),
          'warning'
        );
      }
    } elseif ($input['storage'] == 'external') {
      if (empty($input['domain'])) {
        $valid = false;
        add_settings_error(
          'aesirx_analytics_plugin_options',
          'domain',
          esc_html__('Domain is empty.', 'aesirx-analytics')
        );
      } elseif (filter_var($input['domain'], FILTER_VALIDATE_URL) === false) {
        $valid = false;
        add_settings_error(
          'aesirx_analytics_plugin_options',
          'domain',
          esc_html__('Invalid domain format.', 'aesirx-analytics')
        );
      }
    }

    // Ignore the user's changes and use the old database value.
    if (!$valid) {
      $value = get_option('aesirx_analytics_plugin_options');
    }

    return $value;
  });
  add_settings_section(
    'aesirx_analytics_settings',
    'Aesirx Analytics',
    function () {
      echo wp_kses_post(
        /* translators: %s: URL to aesir.io read mor details */
        sprintf('<p>'. esc_html__('Read more detail at', 'aesirx-analytics') .' <a target="_blank" href="%s">%s</a></p><p class= "description">
        <p>'. esc_html__('Note: Please set Permalink structure is NOT plain.', 'aesirx-analytics') .'</p></p>', 'https://github.com/aesirxio/analytics#in-ssr-site', 'https://github.com/aesirxio/analytics#in-ssr-site')
      );
    },
    'aesirx_analytics_plugin'
  );

  function aesirx_analytics_warning_missing_license() {
    $options = get_option('aesirx_analytics_plugin_options');

    if (!$options || (empty($options['license']) && $options['storage'] === "internal")) {
      ?>
        <div class="notice-warning notice notice-bi" style="display: none;">
            <p><?php echo esc_html__( 'Please register your license at signup.aesirx.io to enable decentralized consent functionality.', 'aesirx-analytics' ); ?></p>
        </div>
      <?php
    }
  }
  add_action( 'admin_notices', 'aesirx_analytics_warning_missing_license' );

  function aesirx_analytics_warning_missing_crontrol() {

    if (!is_plugin_active('wp-crontrol/wp-crontrol.php')) {
      add_settings_error(
        'aesirx_analytics_plugin_options',
        'crontrol',
        esc_html__('Crontrol plugin is not active. Please install and activate it to use geo tracking.', 'aesirx-analytics'),
        'warning'
      );

      ?>
        <div class="notice-warning notice notice-bi" style="display: none;">
            <p><?php echo esc_html__( 'Crontrol plugin is not active. Please install and activate it to use geo tracking.', 'aesirx-analytics' ); ?></p>
        </div>
      <?php
    }
  }
  add_action( 'admin_notices', 'aesirx_analytics_warning_missing_crontrol' );

  add_settings_field(
    'aesirx_analytics_storage',
    esc_html__('1st party server', 'aesirx-analytics'),
    function () {
      $options = get_option('aesirx_analytics_plugin_options', []);
      $checked = 'checked="checked"';
      $storage = $options['storage'] ?? 'internal';
      // using custom function to escape HTML in label
      echo aesirx_analytics_escape_html('
    <label>' . esc_html__('Internal', 'aesirx-analytics') . ' <input type="radio" class="analytic-storage-class" name="aesirx_analytics_plugin_options[storage]" ' .
        ($storage == 'internal' ? $checked : '') .
        ' value="internal"  /></label>
    <label>' . esc_html__('External', 'aesirx-analytics') . ' <input type="radio" class="analytic-storage-class" name="aesirx_analytics_plugin_options[storage]" ' .
        ($storage == 'external' ? $checked : '') .
        ' value="external" /></label>');

        echo '<script>
        jQuery(document).ready(function() {
      function switch_radio(test) {
        var donwload = jQuery("#aesirx_analytics_download");
        if (test === "internal") {
          jQuery("#aesirx_analytics_domain").parents("tr").hide();
          jQuery("#aesirx_analytics_clientid").parents("tr").hide();
          jQuery("#aesirx_analytics_secret").parents("tr").hide();
          jQuery("#aesirx_analytics_license").parents("tr").show();
          jQuery("#aesirx_analytics_geo_cron_time").parents("tr").show();
          jQuery("#aesirx_analytics-enable_cronjob").parents("tr").show();
          donwload.parents("tr").show();
        } else {
          jQuery("#aesirx_analytics_domain").parents("tr").show();
          jQuery("#aesirx_analytics_license").parents("tr").hide();
          jQuery("#aesirx_analytics_clientid").parents("tr").show();
          jQuery("#aesirx_analytics_secret").parents("tr").show();
          jQuery("#aesirx_analytics_geo_cron_time").parents("tr").hide();
          jQuery("#aesirx_analytics-enable_cronjob").parents("tr").hide();
          donwload.parents("tr").hide();
        }
      }
        jQuery("input.analytic-storage-class").click(function() {
        switch_radio(jQuery(this).val())
        });
      switch_radio("' . esc_html($storage) . '");
    });
    </script>';

      $manifest = json_decode(
        file_get_contents(plugin_dir_path(__DIR__) . 'assets-manifest.json', true)
      );

      if ($manifest->entrypoints->plugin->assets) {
        foreach ($manifest->entrypoints->plugin->assets->js as $js) {
          wp_enqueue_script('aesrix_bi' . md5($js), plugins_url($js, __DIR__), false, '1.0', true);
        }
      }
    },
    'aesirx_analytics_plugin',
    'aesirx_analytics_settings'
  );

  add_settings_field(
    'aesirx_analytics_domain',
    __('domain <i>(Use next format: http://example.com:1000/)</i>', 'aesirx-analytics'),
    function () {
      $options = get_option('aesirx_analytics_plugin_options', []);
      // using custom function to escape HTML
      echo aesirx_analytics_escape_html("<input id='aesirx_analytics_domain' name='aesirx_analytics_plugin_options[domain]' type='text' value='" .
        esc_attr($options['domain'] ?? '') .
        "' />"
           /* translators: %s: URL to aesir.io */
           /* translators: %s: URL to aesir.io */
           . sprintf(__("<p class= 'description'>
		    You can setup 1st party server at <a target='_blank' href='%1\$s'>%2\$s</a>.</p>", 'aesirx-analytics'), 'https://github.com/aesirxio/analytics-1stparty', 'https://github.com/aesirxio/analytics-1stparty')
      );
    },
    'aesirx_analytics_plugin',
    'aesirx_analytics_settings'
  );

  add_settings_field(
    'aesirx_analytics_consent',
    __('Consent', 'aesirx-analytics'),
    function () {
      $options = get_option('aesirx_analytics_plugin_options', []);
      $checked = 'checked="checked"';
      $storage = $options['consent'] ?? 'true';
      // using custom function to escape HTML
      echo aesirx_analytics_escape_html('
        <label>' . esc_html__('Yes', 'aesirx-analytics') . ' <input type="radio" class="analytic-consent-class" name="aesirx_analytics_plugin_options[consent]" ' .
            ($storage == 'true' ? $checked : '') .
            ' value="true"  /></label>
        <label>' . esc_html__('No', 'aesirx-analytics') . ' <input type="radio" class="analytic-consent-class" name="aesirx_analytics_plugin_options[consent]" ' .
            ($storage == 'false' ? $checked : '') .
            ' value="false" /></label>');
    }, 
    'aesirx_analytics_plugin',
    'aesirx_analytics_settings'
  );

  add_settings_field(
    'aesirx_analytics_clientid',
    esc_html__('Client ID', 'aesirx-analytics'),
    function () {
      $options = get_option('aesirx_analytics_plugin_options', []);
      echo aesirx_analytics_escape_html("<input id='aesirx_analytics_clientid' name='aesirx_analytics_plugin_options[clientid]' type='text' value='" .
        esc_attr($options['clientid'] ?? '') .
        "' />");
    },
    'aesirx_analytics_plugin',
    'aesirx_analytics_settings'
  );

  add_settings_field(
    'aesirx_analytics_secret',
    esc_html__('Client secret', 'aesirx-analytics'),
    function () {
      $options = get_option('aesirx_analytics_plugin_options', []);
      // using custom function to escape HTML
      echo aesirx_analytics_escape_html("<input id='aesirx_analytics_secret' name='aesirx_analytics_plugin_options[secret]' type='text' value='" .
        esc_attr($options['secret'] ?? '') .
        "' />");
    },
    'aesirx_analytics_plugin',
    'aesirx_analytics_settings'
  );

  add_settings_field(
    'aesirx_analytics_license',
    esc_html__('License', 'aesirx-analytics'),
    function () {
      $options = get_option('aesirx_analytics_plugin_options', []);
      // using custom function to escape HTML
      echo aesirx_analytics_escape_html("<input id='aesirx_analytics_license' name='aesirx_analytics_plugin_options[license]' type='text' value='" .
        esc_attr($options['license'] ?? '') .
        "' /> <p class= 'description'>
        Register to AesirX and get your client id, client secret and license here: <a target='_blank' href='https://web3id.aesirx.io'>https://web3id.aesirx.io</a>.</p>");
    },
    'aesirx_analytics_plugin',
    'aesirx_analytics_settings'
  );
  
  if (is_plugin_active('wp-crontrol/wp-crontrol.php')) {
    add_settings_field(
      'aesirx_analytics_enable_cronjob',
      esc_html__('Enable cronjob', 'aesirx-analytics'),
      function () {
  
          $options = get_option('aesirx_analytics_plugin_options', []);
          $checked = 'checked="checked"';
          $storage = $options['enable_cronjob'] ?? 'true';
          // using custom function to escape HTML
          echo aesirx_analytics_escape_html('
          <label>' . esc_html__('Yes', 'aesirx-analytics') . ' <input type="radio" id="aesirx_analytics-enable_cronjob" name="aesirx_analytics_plugin_options[enable_cronjob]" ' .
               ($storage == 'true' ? $checked : '') .
               ' value="true"  /></label>
          <label>' . esc_html__('No', 'aesirx-analytics') . ' <input type="radio" id="aesirx_analytics-enable_cronjob" name="aesirx_analytics_plugin_options[enable_cronjob]" ' .
               ($storage == 'false' ? $checked : '') .
               ' value="false" /></label>');
      },
      'aesirx_analytics_plugin',
      'aesirx_analytics_settings'
    );

    add_settings_field(
      'aesirx_analytics_geo_cron_time',
      esc_html__('Geo cron time', 'aesirx-analytics'),
      function () {
        $options = get_option('aesirx_analytics_plugin_options', []);
        // using custom function to escape HTML
        echo aesirx_analytics_escape_html("<input id='aesirx_analytics_geo_cron_time' name='aesirx_analytics_plugin_options[geo_cron_time]' type='text' value='" .
          esc_attr($options['geo_cron_time'] ?? '') .
          "' />");
      },
      'aesirx_analytics_plugin',
      'aesirx_analytics_settings'
    );
  }

  add_settings_field(
    'aesirx_analytics_track_ecommerce',
    esc_html__('Track ecommerce', 'aesirx-analytics'),
    function () {

        $options = get_option('aesirx_analytics_plugin_options', []);
        $checked = 'checked="checked"';
        $storage = $options['track_ecommerce'] ?? 'true';
        // using custom function to escape HTML
        echo aesirx_analytics_escape_html('
        <label>' . esc_html__('Yes', 'aesirx-analytics') . ' <input type="radio" class="analytic-track_ecommerce-class" name="aesirx_analytics_plugin_options[track_ecommerce]" ' .
             ($storage == 'true' ? $checked : '') .
             ' value="true"  /></label>
        <label>' . esc_html__('No', 'aesirx-analytics') . ' <input type="radio" class="analytic-track_ecommerce-class" name="aesirx_analytics_plugin_options[track_ecommerce]" ' .
             ($storage == 'false' ? $checked : '') .
             ' value="false" /></label>');
    },
    'aesirx_analytics_plugin',
    'aesirx_analytics_settings'
  );

  add_settings_field(
    'aesirx_analytics_cache_query',
    esc_html__('Cache query', 'aesirx-analytics'),
    function () {
      $options = get_option('aesirx_analytics_plugin_options', []);
      // using custom function to escape HTML
      echo aesirx_analytics_escape_html("<input id='aesirx_analytics_cache_query' name='aesirx_analytics_plugin_options[cache_query]' type='number' value='" .
        esc_attr($options['cache_query'] ?? '') .
        "' /> <p class= 'description'>Set cache time in seconds</p>");
    },
    'aesirx_analytics_plugin',
    'aesirx_analytics_settings'
  );

  add_settings_section(
    'aesirx_analytics_info',
    '',
    function () {
      // using custom function to escape HTML
      echo aesirx_analytics_escape_html('<div class="aesirx_analytics_info"><div class="wrap">Sign up for a
      <h3>FREE License</h3><p>at the AesirX Shield of Privacy dApp</p><div>
      <a target="_blank" href="https://dapp.shield.aesirx.io?utm_source=wpplugin&utm_medium=web&utm_campaign=wordpress&utm_id=aesirx&utm_term=wordpress&utm_content=analytics">Get Free License</a></div>');
    },
    'aesirx_analytics_info'
  );

});

add_action('admin_menu', function () {
  add_options_page(
    esc_html__('Aesirx Analytics', 'aesirx-analytics'),
    esc_html__('Aesirx Analytics', 'aesirx-analytics'),
    'manage_options',
    'aesirx-analytics-plugin',
    function () {
      ?>
			<form action="options.php" method="post">
				<?php
          settings_fields('aesirx_analytics_plugin_options');
          do_settings_sections('aesirx_analytics_plugin');
          wp_nonce_field('aesirx_analytics_settings_save', 'aesirx_analytics_settings_nonce');
        ?>
				<input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e('Save', 'aesirx-analytics'); ?>"/>
			</form>
			<?php
        do_settings_sections('aesirx_analytics_info');
    }
  );

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

      $checked_page = array('aesirx-bi-dashboard', 'aesirx-bi-visitors', 'aesirx-bi-behavior', 'aesirx-bi-utm-tracking', 'aesirx-bi-woocommerce', 'aesirx-bi-consents');
    
      if (in_array($query_params['page'], $checked_page) && !aesirx_analytics_config_is_ok()) {
    
        wp_redirect('/wp-admin/options-general.php?page=aesirx-analytics-plugin');
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
      $hook === 'toplevel_page_aesirx-bi-consents' || 
      $hook === 'toplevel_page_aesirx-bi-woocommerce' || 
      $hook === 'toplevel_page_aesirx-bi-acquisition' || 
      $hook === 'aesirx-bi_page_aesirx-bi-visitors' ||
      $hook === 'aesirx-bi_page_aesirx-bi-flow-list' ||
      $hook === 'admin_page_aesirx-bi-visitors-locations' || 
      $hook === 'admin_page_aesirx-bi-visitors-flow' || 
      $hook === 'admin_page_aesirx-bi-visitors-platforms' || 
      $hook === 'admin_page_aesirx-bi-flow' || 
      $hook === 'aesirx-bi_page_aesirx-bi-behavior' ||
      $hook === 'admin_page_aesirx-bi-behavior-events' ||
      $hook === 'admin_page_aesirx-bi-behavior-events-generator' ||
      $hook === 'admin_page_aesirx-bi-behavior-outlinks' ||
      $hook === 'admin_page_aesirx-bi-behavior-users-flow' ||
      $hook === 'aesirx-bi_page_aesirx-bi-utm-tracking' ||
      $hook === 'admin_page_aesirx-bi-utm-tracking-generator' ||
      $hook === 'aesirx-bi_page_aesirx-bi-consents' ||
      $hook === 'admin_page_aesirx-bi-consents-template' ||
      $hook === 'aesirx-bi_page_aesirx-bi-acquisition' ||
      $hook === 'admin_page_aesirx-bi-acquisition-search-engines' ||
      $hook === 'admin_page_aesirx-bi-acquisition-campaigns' ||
      $hook === 'aesirx-bi_page_aesirx-bi-woocommerce' ||
      $hook === 'admin_page_aesirx-bi-woocommerce-product') {

    $options = get_option('aesirx_analytics_plugin_options');

    $protocols = ['http://', 'https://'];
    $domain = str_replace($protocols, '', site_url());
    $streams = [['name' => get_bloginfo('name'), 'domain' => $domain]];
    $endpoint =
      ($options['storage'] ?? 'internal') == 'internal'
        ? get_bloginfo('url')
        : rtrim($options['domain'] ?? '', '/');

    $manifest = json_decode(
      file_get_contents(plugin_dir_path(__DIR__) . 'assets-manifest.json', true)
    );

    if ($manifest->entrypoints->bi->assets) {
      foreach ($manifest->entrypoints->bi->assets->js as $js) {
        wp_enqueue_script('aesrix_bi' . md5($js), plugins_url($js, __DIR__), false, '1.0', true);
      }
    }

    $clientId = $options['clientid'];
    $clientSecret = $options['secret'];

    $jwt = $options['storage'] === "external" ? 'window.env.REACT_APP_HEADER_JWT="true"' : '';

    wp_register_script( 'aesrix_bi_window', '', array(), '1.0', false );

    wp_enqueue_script('aesrix_bi_window');

    wp_add_inline_script(
      'aesrix_bi_window',
      'window.env = {};
		  window.aesirxClientID = "' . esc_html($clientId) . '";
		  window.aesirxClientSecret = "' . esc_html($clientSecret) . '";
      window.env.REACT_APP_BI_ENDPOINT_URL = "' . esc_url($endpoint) . '";
		  window.env.REACT_APP_ENDPOINT_URL = "' . esc_url($endpoint) . '";
		  window.env.REACT_APP_DATA_STREAM = JSON.stringify(' . wp_json_encode($streams) . ');
		  window.env.PUBLIC_URL= "' . esc_url(plugin_dir_url(__DIR__)) . '";
      window.env.STORAGE= "' . esc_html($options['storage']) . '";
      window.env.REACT_APP_WOOCOMMERCE_MENU= "' . esc_html($options['track_ecommerce']) . '";
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
function aesirx_analytics_escape_html($string) {
  $allowed_html = array(
    'input' => array(
        'type'  => array(),
        'id'    => array(),
        'name'  => array(),
        'value' => array(),
        'class' => array(),
        'checked' => array(),
     ),
     'a' => array(),
     'p' => array(),
     'h3' => array(),
     'div' => array(
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

  return wp_kses($string, $allowed_html);
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