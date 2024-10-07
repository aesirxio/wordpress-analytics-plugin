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
    'AesirX Analytics & CMP',
    function () {
      echo wp_kses_post(
        /* translators: %s: URL to aesir.io read mor details */
        sprintf('<p class= "description"><strong>'. esc_html__('Note: ', 'aesirx-analytics') . '</strong>' . esc_html__('Please set Permalink Settings in WP so it is NOT set as plain.', 'aesirx-analytics') .'</p>')
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
    esc_html__('AesirX First-Party Server', 'aesirx-analytics'),
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
        echo aesirx_analytics_escape_html('<p class="description"><strong>'.esc_html__('Internal Storage', 'aesirx-analytics').': </strong>'.esc_html__('Stores analytics data directly within the WordPress database (WP DB). This option does not offer additional control over the data, as it is part of the core website infrastructure. It may be less secure since it shares space with other WordPress data and could impact performance, especially with high traffic or large datasets.', 'aesirx-analytics').'</p>');
        echo aesirx_analytics_escape_html('<p class="description"><strong>'.esc_html__('External Storage (First-Party Server)', 'aesirx-analytics').': </strong>'.esc_html__('Stores analytics data on a dedicated first-party server, isolating the data from the WordPress database. This improves security and performance by keeping analytics data separate, reducing the load on the WordPress site. It also supports enhanced Web3 functionality, making it a more secure and efficient solution for handling data.', 'aesirx-analytics').'</p>');
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
    __('Domain <i>(Use next format: http://example.com:1000/)</i>', 'aesirx-analytics'),
    function () {
      $options = get_option('aesirx_analytics_plugin_options', []);
      // using custom function to escape HTML
      echo aesirx_analytics_escape_html("<input id='aesirx_analytics_domain' name='aesirx_analytics_plugin_options[domain]' type='text' value='" .
        esc_attr($options['domain'] ?? '') .
        "' />"
           /* translators: %s: URL to aesir.io */
           /* translators: %s: URL to aesir.io */
           . sprintf(__("<p class= 'description'>
		    You can setup 1st party server at <a target='_blank' href='%1\$s'>%2\$s</a>.</p>", 'aesirx-analytics'), 'https://aesirx.io/documentation/first-party-server/install-guide/1st-party', 'https://aesirx.io/documentation/first-party-server/install-guide/1st-party')
      );
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
        echo aesirx_analytics_escape_html("<p class='description'><strong>".esc_html__('Description', 'aesirx-analytics').": </strong>".sprintf(__("<p class= 'description'>
		    Provided SSO CLIENT ID from <a href='%1\$s' target='_blank'>%1\$s</a>.</p>", 'aesirx-analytics'), 'https://dapp.shield.aesirx.io/licenses')."</p>");
    },
    'aesirx_analytics_plugin',
    'aesirx_analytics_settings'
  );

  add_settings_field(
    'aesirx_analytics_secret',
    esc_html__('Client Secret', 'aesirx-analytics'),
    function () {
      $options = get_option('aesirx_analytics_plugin_options', []);
      // using custom function to escape HTML
      echo aesirx_analytics_escape_html("<input id='aesirx_analytics_secret' name='aesirx_analytics_plugin_options[secret]' type='text' value='" .
        esc_attr($options['secret'] ?? '') .
        "' />");
        echo aesirx_analytics_escape_html("<p class='description'><strong>".esc_html__('Description', 'aesirx-analytics').": </strong>".sprintf(__("<p class= 'description'>
		    Provided SSO Client Secret from <a href='%1\$s' target='_blank'>%1\$s</a>.</p>", 'aesirx-analytics'), 'https://dapp.shield.aesirx.io/licenses')."</p>");
    },
    'aesirx_analytics_plugin',
    'aesirx_analytics_settings'
  );

  add_settings_field(
    'aesirx_analytics_consent',
    __('Consent Management', 'aesirx-analytics'),
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
      echo aesirx_analytics_escape_html("<p class='description'><strong>".esc_html__('Description', 'aesirx-analytics').": </strong>".esc_html__("This option lets website owners enable or disable the consent popup. If enabled, visitors must give consent for data collection and third-party services. If disabled, tracking and analytics won't run without consent, and the popup won't be displayed.", 'aesirx-analytics')."</p>");
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
        "' /> <p class= 'description'><strong>".esc_html__('Description', 'aesirx-analytics').": </strong>
        ".sprintf(__("<p class= 'description'>
		    Sign up on the AesirX platform to obtain your Shield of Privacy ID and free license, and activate support for decentralized consent at <a href='%1\$s' target='_blank'>%1\$s</a>.</p>", 'aesirx-analytics'), 'https://signup.aesirx.io')."</p>");
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
          echo aesirx_analytics_escape_html('<p class="description"><strong>'.esc_html__('Description', 'aesirx-analytics').': </strong>'.esc_html__('This setting allows you to capture the geographic location of users when tracking their activity. The location data can be used to improve personalized experiences or for location-based analytics.', 'aesirx-analytics').'</p>');
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
        echo aesirx_analytics_escape_html('<p class="description"><strong>'.esc_html__('Description', 'aesirx-analytics').': </strong>'.esc_html__('This function runs a cron job at set intervals ("X" time) to refresh and update the user’s location data. This ensures that location tracking remains accurate over time without requiring manual intervention.', 'aesirx-analytics').'</p>');
      },
      'aesirx_analytics_plugin',
      'aesirx_analytics_settings'
    );
  }

  add_settings_field(
    'aesirx_analytics_track_ecommerce',
    esc_html__('Track Ecommerce', 'aesirx-analytics'),
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
        echo aesirx_analytics_escape_html('<p class="description"><strong>'.esc_html__('Description', 'aesirx-analytics').': </strong>'.esc_html__('If enabled, this feature will track key Woo events, including Add to Cart, Checkout, and Search Product. This allows website owners to gather data on shopping behaviors and optimize the eCommerce experience.', 'aesirx-analytics').'</p>');
    },
    'aesirx_analytics_plugin',
    'aesirx_analytics_settings'
  );

  add_settings_field(
    'aesirx_analytics_cache_query',
    esc_html__('Cache Query', 'aesirx-analytics'),
    function () {
      $options = get_option('aesirx_analytics_plugin_options', []);
      // using custom function to escape HTML
      echo aesirx_analytics_escape_html("<input id='aesirx_analytics_cache_query' name='aesirx_analytics_plugin_options[cache_query]' type='number' value='" .
        esc_attr($options['cache_query'] ?? '') .
        "' /> <p class= 'description'><strong>".esc_html__('Description', 'aesirx-analytics').": </strong>".esc_html__("This option allows you to cache tracking data for a specified amount of time ('X' time). Caching improves the speed of data retrieval and reduces the load on your server by temporarily storing data before it is refreshed.", 'aesirx-analytics')."</p>");
    },
    'aesirx_analytics_plugin',
    'aesirx_analytics_settings'
  );
  
  add_settings_field(
    'aesirx_analytics_blocking_cookies_plugins',
    esc_html__('AesirX Consent Shield for Third-Party Plugins ', 'aesirx-analytics'),
    function () {
      $options = get_option('aesirx_analytics_plugin_options', []);
      $installed_plugins = get_plugins();
      $active_plugins = get_option('active_plugins');
      echo '<table class="aesirx-analytics-cookie-plugin">';
      foreach ($installed_plugins as $path => $plugin) {

        if ($plugin['TextDomain'] === 'aesirx-analytics' || $plugin['TextDomain'] === '' || !in_array($path, $active_plugins)) {
          continue;
        }
        echo '<tr class="aesirx-analytics-cookie-plugin-item">';
        echo '<td>';
        echo '<label for="aesirx_analytics_blocking_cookies_plugins'.esc_attr($plugin['TextDomain']).'">' . esc_html($plugin['Name']) . '</label>';
        echo '</td>';
        echo '<td>';
        echo aesirx_analytics_escape_html(
          "<input id='aesirx_analytics_blocking_cookies_plugins".esc_attr($plugin['TextDomain'])."' name='aesirx_analytics_plugin_options[blocking_cookies_plugins][]' 
          value='" . esc_attr($plugin['TextDomain']) . "' type='checkbox'" 
          . (isset($options['blocking_cookies_plugins']) && in_array($plugin['TextDomain'], $options['blocking_cookies_plugins']) ? ' checked="checked"' : '') . "/>"
        );
        echo '</td>';
        echo '</tr>';
      }
      echo '</table>';
      echo aesirx_analytics_escape_html('<p class="description"><strong>'.esc_html__('Description', 'aesirx-analytics').': </strong>'.esc_html__('Blocks selected third-party plugins from loading until user consent is given.', 'aesirx-analytics').'</p>');
      echo aesirx_analytics_escape_html('<ul class="description"><li>'.esc_html__('Completely prevents the loading and execution of chosen third-party plugins before consent.', 'aesirx-analytics').'</li><li>'.esc_html__('No network requests are made to third-party servers, enabling maximum compliance with privacy regulations like GDPR and the ePrivacy Directive.', 'aesirx-analytics').'</li></ul>');
      echo aesirx_analytics_escape_html('<p class="description">'.sprintf(__("<p class= 'description'>
      For detailed guides, how-to videos, and API documentation, visit our Documentation Hub:  <a href='%1\$s' target='_blank'>%1\$s</a>.</p>", 'aesirx-analytics'), 'https://aesirx.io/documentation').'</p>');
    },
    'aesirx_analytics_plugin',
    'aesirx_analytics_settings'
  );

  add_settings_field(
    'aesirx_analytics_blocking_cookies',
    esc_html__('AesirX Consent Shield for Domain/Path-Based Blocking', 'aesirx-analytics'),
    function () {
      $options = get_option('aesirx_analytics_plugin_options', []);
      echo '<table id="aesirx-analytics-blocking-cookies">';
      if (isset($options['blocking_cookies'])) {
          foreach ($options['blocking_cookies'] as $field) {
              echo '<tr class="aesirx-analytics-cookie-row">';
              echo '<td>' . aesirx_analytics_escape_html('<input type="text" name="aesirx_analytics_plugin_options[blocking_cookies][]" placeholder="'.esc_html__('Enter domain or path', 'aesirx-analytics').'" value="'.esc_attr($field).'">') . '</td>';
              echo '<td>' . aesirx_analytics_escape_html('<button class="aesirx-analytics-remove-cookies-row">'.esc_html__('Remove', 'aesirx-analytics').'</button>') . '</td>';
              echo '</tr>';
          }
      } else {
          echo '<tr class="aesirx-analytics-cookie-row">';
          echo '<td>' . aesirx_analytics_escape_html('<input type="text" name="aesirx_analytics_plugin_options[blocking_cookies][]" placeholder="'.esc_html__('Enter domain or path', 'aesirx-analytics').'">') . '</td>';
          echo '<td>' . aesirx_analytics_escape_html('<button class="aesirx-analytics-remove-cookies-row">'.esc_html__('Remove', 'aesirx-analytics').'</button>') . '</td>';
          echo '</tr>';
      }
      echo '</table>';
      echo aesirx_analytics_escape_html('<button id="aesirx-analytics-add-cookies-row">'.esc_html__('Add', 'aesirx-analytics').'</button>');
      echo aesirx_analytics_escape_html('<p class="description"><strong>'.esc_html__('Description', 'aesirx-analytics').': </strong>'.esc_html__('Removes scripts matching specified domains or paths from the browser until user consent is given.', 'aesirx-analytics').'</p>');
      echo aesirx_analytics_escape_html("<ul class='description'><li>".esc_html__("Blocks or removes scripts from running in the user's browser before consent is given.", 'aesirx-analytics')."</li><li>".esc_html__("While it prevents scripts from executing, initial network requests may still occur, so it enhances privacy compliance under GDPR but may not fully meet the ePrivacy Directive requirements.", 'aesirx-analytics')."</li></ul>");
      echo aesirx_analytics_escape_html('<p class="description"><strong>'.esc_html__('Disclaimer', 'aesirx-analytics').': </strong>'.esc_html__('The AesirX Consent Shield has only just been released and still being adopted based on feedback and inputs from agencies, developers and users, if you experience any issues please contact our support.', 'aesirx-analytics').'</p>');
    },
    'aesirx_analytics_plugin',
    'aesirx_analytics_settings'
  );

  add_settings_field(
    'aesirx_analytics_blocking_cookies_mode',
    esc_html__('Script Blocking Options', 'aesirx-analytics'),
    function () {
        $options = get_option('aesirx_analytics_plugin_options', []);
        $checked = 'checked="checked"';
        $mode = $options['blocking_cookies_mode'] ?? '3rd_party';
        // using custom function to escape HTML
        echo aesirx_analytics_escape_html('<div class="description">
        <label><input type="radio" class="analytic-blocking_cookies_mode-class" name="aesirx_analytics_plugin_options[blocking_cookies_mode]" ' .
        ($mode == '3rd_party' ? $checked : '') .
        ' value="3rd_party"  />' . esc_html__('Only Third-Party Hosts (default)', 'aesirx-analytics') . '</label></div>');
        echo aesirx_analytics_escape_html('<p class="description"><strong>'.esc_html__('Description', 'aesirx-analytics').': </strong>'.esc_html__('Blocks JavaScript from third-party domains, allowing first-party scripts to run normally and keep essential site functions intact.', 'aesirx-analytics').'</p>');
        echo aesirx_analytics_escape_html('<p class="description"></p>');
        echo aesirx_analytics_escape_html('<div class="description"><label><input type="radio" class="analytic-blocking_cookies_mode-class" name="aesirx_analytics_plugin_options[blocking_cookies_mode]" ' .
            ($mode == 'both' ? $checked : '') .
            ' value="both" />' . esc_html__('Both First and Third-Party Hosts', 'aesirx-analytics') . '</label></div>');
        echo aesirx_analytics_escape_html('<p class="description"><strong>'.esc_html__('Description', 'aesirx-analytics').': </strong>'.esc_html__('Blocks JavaScript from both first-party and third-party domains for comprehensive script control, giving you the ability to block any JavaScript from internal or external sources based on user consent.', 'aesirx-analytics').'</p>');
    },
    'aesirx_analytics_plugin',
    'aesirx_analytics_settings'
  );

  add_settings_section(
    'aesirx_analytics_info',
    '',
    function () {
      // using custom function to escape HTML
      echo aesirx_analytics_escape_html("<div class='aesirx_analytics_info'><div class='wrap'>".esc_html__("Need Help? Access Our Comprehensive Documentation Hub", 'aesirx-analytics')."
      <p class='banner-description'>".sprintf(__("Explore How-To Guides, instructions, and tutorials to get the most from AesirX Consent Shield. Whether you're a </br> developer or admin, find all you need to configure and optimize your privacy setup.", 'aesirx-analytics'))."</p>
      <p class='banner-description-bold'>".esc_html__("Ready to take the next step? Discover the latest features and best practices.", 'aesirx-analytics')."</p><div>
      <a target='_blank' href='https://aesirx.io/documentation'><img src='". plugins_url( 'aesirx-analytics/assets/images-plugin/icon_button.svg')."' />".esc_html__('ACCESS THE DOCUMENTATION HUB', 'aesirx-analytics')."</a></div>");
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
    'AesirX BI Consents',
    'Consent',
    'manage_options',
    'aesirx-bi-consents',
    function () {
      ?><div id="biapp" class="aesirxui"></div><?php
    },
    4); 
  add_submenu_page(
    'aesirx-bi-consents',
    'AesirX BI Consents Template',
    'Consents Template',
    'manage_options',
    'aesirx-bi-consents-template',
    function () {
      ?><div id="biapp" class="aesirxui"></div><?php
    },
    4);
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
  add_submenu_page(
    'aesirx-bi-visitors',
    'AesirX BI Visitors Flow Detail',
    'Flow',
    'manage_options',
    'aesirx-bi-flow',
    function () {
      ?><div id="biapp" class="aesirxui"></div><?php
    },
    6);
  add_submenu_page(
    'aesirx-bi-dashboard',
    'AesirX BI User Experience',
    'User Experience',
    'manage_options',
    'aesirx-bi-flow-list',
    function () {
      ?><div id="biapp" class="aesirxui"></div><?php
    },
    7);
  add_submenu_page(
    'aesirx-bi-dashboard',
    'AesirX BI Woocommerce',
    'Woo',
    'manage_options',
    'aesirx-bi-woocommerce',
    function () {
      ?><div id="biapp" class="aesirxui"></div><?php
    },
    8);
  add_submenu_page(
    'aesirx-bi-woocommerce',
    'AesirX BI Woocommerce Product',
    'Woo Product',
    'manage_options',
    'aesirx-bi-woocommerce-product',
    function () {
      ?><div id="biapp" class="aesirxui"></div><?php
    },
    8);
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
  if ($hook === 'settings_page_aesirx-analytics-plugin') {
    wp_enqueue_script('aesirx_analytics_repeatable_fields', plugins_url('assets/vendor/aesirx-analytics-repeatable-fields.js', __DIR__), array('jquery'), '1.0.0', true);
  }

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
        'placeholder' => array(),
        'checked' => array(),
     ),
     'strong' => array(),
     'a' => array(
      'href'  => array(),
      'target'    => array(),
     ),
     'p' => array(
      'class' => array(),
     ),
     'h3' => array(),
     'ul' => array(
      'class' => array(),
     ),
     'li' => array(),
     'br' => array(),
     'img' => array(
      'src'  => array(),
     ),
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