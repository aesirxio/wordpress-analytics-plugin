<?php

use AesirxAnalytics\CliFactory;

add_action('admin_init', function () {
  register_setting('aesirx_analytics_plugin_options', 'aesirx_analytics_plugin_options', function (
    $value
  ) {
    $valid = true;
    $input = (array) $value;

    if ($input['storage'] == 'internal') {
      if (empty($input['license'])) {
        $valid = false;
        add_settings_error(
          'aesirx_analytics_plugin_options',
          'license',
          __('License is empty.', 'aesirx-analytics')
        );
      }
    } elseif ($input['storage'] == 'external') {
      if (empty($input['domain'])) {
        $valid = false;
        add_settings_error(
          'aesirx_analytics_plugin_options',
          'domain',
          __('Domain is empty.', 'aesirx-analytics')
        );
      } elseif (filter_var($input['domain'], FILTER_VALIDATE_URL) === false) {
        $valid = false;
        add_settings_error(
          'aesirx_analytics_plugin_options',
          'domain',
          __('Invalid domain format.', 'aesirx-analytics')
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
      echo

           /* translators: %s: URL to aesir.io read mor details */
                   sprintf(__('<p>Read more detail at <a target="_blank" href="%s">%s</a></p><p class= "description">
        <p>Note: Please set Permalink structure is NOT plain.</p></p>', 'aesirx-analytics'), 'https://github.com/aesirxio/analytics#in-ssr-site', 'https://github.com/aesirxio/analytics#in-ssr-site');
    },
    'aesirx_analytics_plugin'
  );

  add_settings_field(
    'aesirx_analytics_storage',
    __('1st party server', 'aesirx-analytics'),
    function () {
      $options = get_option('aesirx_analytics_plugin_options', []);
      $checked = 'checked="checked"';
      $storage = $options['storage'] ?? 'internal';
      echo '
    <label>' . __('Internal', 'aesirx-analytics') . ' <input type="radio" class="analytic-storage-class" name="aesirx_analytics_plugin_options[storage]" ' .
        ($storage == 'internal' ? $checked : '') .
        ' value="internal"  /></label>
    <label>' . __('External', 'aesirx-analytics') . ' <input type="radio" class="analytic-storage-class" name="aesirx_analytics_plugin_options[storage]" ' .
        ($storage == 'external' ? $checked : '') .
        ' value="external" /></label>

    <script>
    jQuery(document).ready(function() {
	function switch_radio(test) {
		var donwload = jQuery("#aesirx_analytics_download");
		if (test === "internal") {
			jQuery("#aesirx_analytics_domain").parents("tr").hide();
			jQuery("#aesirx_analytics_license").parents("tr").show();
			donwload.parents("tr").show();
		} else {
			jQuery("#aesirx_analytics_domain").parents("tr").show();
			jQuery("#aesirx_analytics_license").parents("tr").hide();
			donwload.parents("tr").hide();
		}
	}
    jQuery("input.analytic-storage-class").click(function() {
		switch_radio(jQuery(this).val())
    });
	switch_radio("' .
        $storage .
        '");
});
</script>';

      $manifest = json_decode(
        file_get_contents(plugin_dir_path(__DIR__) . 'assets-manifest.json', true)
      );

      if ($manifest->entrypoints->plugin->assets) {
        foreach ($manifest->entrypoints->plugin->assets->js as $js) {
          wp_enqueue_script('aesrix_bi' . md5($js), plugins_url($js, __DIR__), false, null, true);
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
      echo "<input id='aesirx_analytics_domain' name='aesirx_analytics_plugin_options[domain]' type='text' value='" .
        esc_attr($options['domain'] ?? '') .
        "' />"
           /* translators: %s: URL to aesir.io */
           /* translators: %s: URL to aesir.io */
           . sprintf(__("<p class= 'description'>
		You can setup 1st party server at <a target='_blank' href='%s'>%s</a>.</p>", 'aesirx-analytics'), 'https://github.com/aesirxio/analytics-1stparty', 'https://github.com/aesirxio/analytics-1stparty');
    },
    'aesirx_analytics_plugin',
    'aesirx_analytics_settings'
  );

  if (!CliFactory::getCli()->analyticsCliExists()) {
    add_settings_field(
        'aesirx_analytics_download',
        __( 'Download', 'aesirx-analytics' ),
        function () {
          try {
              CliFactory::getCli()->getSupportedArch();

            echo '<button name="submit" id="aesirx_analytics_download" class="button button-primary" type="submit" value="download_analytics_cli">' . __(
                    'Click to download CLI library! This plugin can\'t work without the library!', 'aesirx-analytics'
                ) . '</button>';
          }
          catch ( Throwable $e ) {
            echo '<strong style="color: red">' . __( 'You can\'t use internal server. Error: ' . $e->getMessage(), 'aesirx-analytics' ) . '</strong>';
          }
        },
        'aesirx_analytics_plugin',
        'aesirx_analytics_settings'
    );
  } else {
      add_settings_field(
          'aesirx_analytics_download',
          __( 'CLI library check', 'aesirx-analytics' ),
          function () {
              try {
                  CliFactory::getCli()->processAnalytics(['--version']);
				  echo '<strong style="color: green" id="aesirx_analytics_download">' . __( 'Passed', 'aesirx-analytics' ) . '</strong>';
              } catch (Throwable $e) {
                  echo '<strong style="color: red" id="aesirx_analytics_download">' . __( 'You can\'t use internal server. Error: ' . $e->getMessage(), 'aesirx-analytics' ) . '</strong>';
			  }
          },
          'aesirx_analytics_plugin',
          'aesirx_analytics_settings'
      );
  }

  add_settings_field(
    'aesirx_analytics_consent',
    __('Consent', 'aesirx-analytics'),
    function () {
      $options = get_option('aesirx_analytics_plugin_options', []);
      $checked = 'checked="checked"';
      $storage = $options['consent'] ?? 'true';
      echo '
        <label>' . __('Yes', 'aesirx-analytics') . ' <input type="radio" class="analytic-consent-class" name="aesirx_analytics_plugin_options[consent]" ' .
            ($storage == 'true' ? $checked : '') .
            ' value="true"  /></label>
        <label>' . __('No', 'aesirx-analytics') . ' <input type="radio" class="analytic-consent-class" name="aesirx_analytics_plugin_options[consent]" ' .
            ($storage == 'false' ? $checked : '') .
            ' value="false" /></label>';
    }, 
    'aesirx_analytics_plugin',
    'aesirx_analytics_settings'
  );

  add_settings_field(
    'aesirx_analytics_clientid',
    __('Client ID', 'aesirx-analytics'),
    function () {
      $options = get_option('aesirx_analytics_plugin_options', []);
      echo "<input id='aesirx_analytics_clientid' name='aesirx_analytics_plugin_options[clientid]' type='text' value='" .
        esc_attr($options['clientid'] ?? '') .
        "' />";
    },
    'aesirx_analytics_plugin',
    'aesirx_analytics_settings'
  );

  add_settings_field(
    'aesirx_analytics_secret',
    __('Client secret', 'aesirx-analytics'),
    function () {
      $options = get_option('aesirx_analytics_plugin_options', []);
      echo "<input id='aesirx_analytics_secret' name='aesirx_analytics_plugin_options[secret]' type='text' value='" .
        esc_attr($options['secret'] ?? '') .
        "' />";
    },
    'aesirx_analytics_plugin',
    'aesirx_analytics_settings'
  );

  add_settings_field(
    'aesirx_analytics_license',
    __('License', 'aesirx-analytics'),
    function () {
      $options = get_option('aesirx_analytics_plugin_options', []);
      echo "<input id='aesirx_analytics_license' name='aesirx_analytics_plugin_options[license]' type='text' value='" .
        esc_attr($options['license'] ?? '') .
        "' /> <p class= 'description'>
        Register to AesirX and get your client id, client secret and license here: <a target='_blank' href='https://web3id.aesirx.io'>https://web3id.aesirx.io</a>.</p>";
    },
    'aesirx_analytics_plugin',
    'aesirx_analytics_settings'
  );

  add_settings_section(
    'aesirx_analytics_info',
    '',
    function () {
      echo '<div class="aesirx_analytics_info"><div class="wrap">Invest $1000 as a seed fund and receive:
      <ul><li>Rewards worth of $6000!</li><li> Many more exclusive benefits!</li></ul><div>

      <p>* Apply for only the first 2000 investors</p>
      <a target="_blank" href="https://aesirx.io/seed-round?utm_source=wpplugin&utm_medium=web&utm_campaign=wordpress&utm_id=aesirx&utm_term=wordpress&utm_content=analytics">Become a Community Investor Now!</a></div>';
    },
    'aesirx_analytics_info'
  );

});

add_action('admin_menu', function () {
  add_options_page(
    __('Aesirx Analytics', 'aesirx-analytics'),
    __('Aesirx Analytics', 'aesirx-analytics'),
    'manage_options',
    'aesirx-analytics-plugin',
    function () {
      ?>
			<form action="options.php" method="post">
				<?php
    settings_fields('aesirx_analytics_plugin_options');
    do_settings_sections('aesirx_analytics_plugin');
    ?>
				<input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e(
      'Save'
    ); ?>"/>
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
    'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjQiIGhlaWdodD0iMjQiIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTExLjI2NjEgMlYyMiIgc3Ryb2tlPSJ3aGl0ZSIvPgo8cGF0aCBkPSJNNi4wOTA5IDYuMTk1NjhMMTYuOTk5OSAxNy41MjQ0IiBzdHJva2U9IndoaXRlIi8+Cjwvc3ZnPgo=',
    3
  );
});

add_action('admin_init', 'redirect_analytics_config', 1);
function redirect_analytics_config() {
  if ( isset($_GET['page'])
       && $_GET['page'] == 'aesirx-bi-dashboard'
       && !analytics_config_is_ok()) {
    wp_redirect('/wp-admin/options-general.php?page=aesirx-analytics-plugin');
    die;
  }
}

add_action('admin_enqueue_scripts', function ($hook) {
  if ($hook === 'toplevel_page_aesirx-bi-dashboard') {

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
        wp_enqueue_script('aesrix_bi' . md5($js), plugins_url($js, __DIR__), false, null, true);
      }
    }
    ?>
	  <script type="text/javascript">
		  window.env = {};
		  window.env.REACT_APP_CLIENT_ID = "app";
		  window.env.REACT_APP_CLIENT_SECRET = "secret";
		  window.env.REACT_APP_ENDPOINT_URL = "<?php echo $endpoint; ?>";
		  window.env.REACT_APP_DATA_STREAM = JSON.stringify(<?php echo json_encode($streams); ?>);
		  window.env.PUBLIC_URL="<?php echo plugin_dir_url(__DIR__) ?>";
	  </script>
	  <?php
  }
});

add_action( 'tgmpa_register', 'aesirx_analytics_register_required_plugins' );

function aesirx_analytics_register_required_plugins() {
  /*
   * Array of plugin arrays. Required keys are name and slug.
   * If the source is NOT from the .org repo, then source is also required.
   */
  $plugins = array(
      array(
          'name'      => 'WP Crontrol',
          'slug'      => 'wp-crontrol',
          'required'  => true,
          'version' => 'v1.3.0',
      ),
  );

  $config = array(
      'id'           => 'aesirx-analytics',
      'dismissable'  => false,
      'is_automatic' => true,
      'strings'      => array(
        'notice_can_install_required'     => _n_noop(
            /* translators: 1: plugin name(s). */
            'This plugin requires the following plugin: %1$s.',
            'This plugin requires the following plugins: %1$s.',
            'aesirx-analytics'
        ),
        'notice_can_activate_required'    => _n_noop(
            /* translators: 1: plugin name(s). */
            'The following required plugin is currently inactive: %1$s.',
            'The following required plugins are currently inactive: %1$s.',
            'aesirx-analytics'
        ),
    ),
  );

  tgmpa( $plugins, $config );
}
