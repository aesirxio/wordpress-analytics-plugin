<?php

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
			echo '<h3>' . __('When you join forces with AesirX, you are not just becoming a Partner - you are also becoming a freedom fighter in the battle for privacy! Earn 25% Affiliate Commission <a href="https://aesirx.io/seed-round?utm_source=wpplugin&utm_medium=web&utm_campaign=wordpress&utm_id=aesirx&utm_term=wordpress&utm_content=analytics">[Click to Join]</a>') . '</h3>';
      echo '<p>' .
        __('Here you can set all the options for using the aesirx analytics', 'aesirx-analytics') .
				'</p>' .
        '
        <p>Read more detail at <a target="_blank" href="https://github.com/aesirxio/analytics#in-ssr-site">https://github.com/aesirxio/analytics#in-ssr-site</a></p><p class= "description">
        <h3>Note: Please set Permalink structure is NOT plain.</h3></p>';
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
    <label>Internal <input type="radio" class="analytic-storage-class" name="aesirx_analytics_plugin_options[storage]" ' .
        ($storage == 'internal' ? $checked : '') .
        ' value="internal"  /></label>
    <label>External <input type="radio" class="analytic-storage-class" name="aesirx_analytics_plugin_options[storage]" ' .
        ($storage == 'external' ? $checked : '') .
        ' value="external" /></label>

    <script>
    jQuery(document).ready(function() {
	function switch_radio(test) {
		if (test == "internal") {
			jQuery("#aesirx_analytics_domain").parents("tr").hide();
			jQuery("#aesirx_analytics_license").parents("tr").show();
		} else {
			jQuery("#aesirx_analytics_domain").parents("tr").show();
			jQuery("#aesirx_analytics_license").parents("tr").hide();
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
        "' /><p class= 'description'>
		You can setup 1st party server at <a target='_blank' href='https://github.com/aesirxio/analytics-1stparty'>https://github.com/aesirxio/analytics-1stparty</a>.</p>";
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
		You can get License at <a target='_blank' href='https://analytics.aesirx.io'>https://analytics.aesirx.io</a>.</p>";
    },
    'aesirx_analytics_plugin',
    'aesirx_analytics_settings'
  );

	add_settings_section(
		'aesirx_analytics_settings2',
		'',
		function () {
			echo '<h3>
        To track events, simply add special data-attribute to the element you want to track. For example, you might have a button with the following code:
        </h3><code>
        ' .
				htmlentities('<button class="button"') .
				'<br>
        ' .
				htmlentities('data-aesirx-event-name="sign up"') .
				'<br>
        ' .
				htmlentities('data-aesirx-event-type="login"') .
				'<br>
        ' .
				htmlentities('data-aesirx-event-attribute-a="value-a"') .
				'<br>
        ' .
				htmlentities('data-aesirx-event-attribute-b="value-b"') .
				'>Sign Up' .
				htmlentities('</button>') . '</code><br><br>';
		},
	'aesirx_analytics_plugin'
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
    }
  );

  add_menu_page(
    'AesirX BI Dashboard',
    'AesirX BI',
    'manage_options',
    'aesirx-bi-dashboard',
    function () {
      include 'dashboard.php';
    }
  );
});

add_action('admin_enqueue_scripts', function () {
  global $wp;
  $options = get_option('aesirx_analytics_plugin_options');

  $protocols = ['http://', 'https://', 'http://www.', 'https://www.', 'www.'];
  $domain = str_replace($protocols, '', site_url());
  $streams = [['name' => get_bloginfo('name'), 'domain' => $domain]];
  $endpoint =
    ($options['storage'] ?? 'internal') == 'internal'
      ? get_bloginfo('url')
      : $options['domain'] ?? '';
  ?>
	  <script>
		  window.env = {};
		  window.env.REACT_APP_CLIENT_ID = "app";
		  window.env.REACT_APP_CLIENT_SECRET = "secret";
		  window.env.REACT_APP_ENDPOINT_URL = "<?php echo $endpoint; ?>";
		  window.env.REACT_APP_DATA_STREAM = JSON.stringify(<?php echo json_encode($streams); ?>);
		  window.env.PUBLIC_URL="/wp-content/plugins/aesirx-analytics";
	  </script>
	  <%= htmlWebpackPlugin.tags.headTags %>
	  <?php
});
