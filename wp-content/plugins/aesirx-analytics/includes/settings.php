<?php

add_action('admin_init', function () {
	register_setting(
		'aesirx_analytics_plugin_options',
		'aesirx_analytics_plugin_options',
		function ($value) {
			$valid = true;
			$input = (array) $value;

			if ($input['storage'] == 'internal')
			{
				if (empty($input['license']))
				{
					$valid = false;
					add_settings_error('aesirx_analytics_plugin_options', 'license', __('License is empty.', 'aesirx-analytics'));
				}
			}
			elseif ($input['storage'] == 'external')
			{
				if (empty($input['domain']))
				{
					$valid = false;
					add_settings_error('aesirx_analytics_plugin_options', 'domain', __('Domain is empty.', 'aesirx-analytics'));
				}
				elseif (filter_var($input['domain'], FILTER_VALIDATE_URL) === false)
				{
					$valid = false;
					add_settings_error('aesirx_analytics_plugin_options', 'domain', __('Invalid domain format.', 'aesirx-analytics'));
				}
			}

			// Ignore the user's changes and use the old database value.
			if (!$valid)
			{
				$value = get_option('aesirx_analytics_plugin_options');
			}

			return $value;
		});
	add_settings_section('aesirx_analytics_settings', 'Aesirx analytics', function () {
		echo '<p>' . __('Here you can set all the options for using the aesirx analytics', 'aesirx-analytics') . '</p>';
	}, 'aesirx_analytics_plugin');

	add_settings_field('aesirx_analytics_storage', __('1st party server', 'aesirx-analytics'), function () {
		$options = get_option('aesirx_analytics_plugin_options', []);
		$checked = 'checked="checked"';
		$storage = $options['storage'] ?? 'internal';
		echo '
    <label>Internal <input type="radio" class="analytic-storage-class" name="aesirx_analytics_plugin_options[storage]" ' . ($storage == 'internal' ? $checked : '') . ' value="internal"  /></label>
    <label>External <input type="radio" class="analytic-storage-class" name="aesirx_analytics_plugin_options[storage]" ' . ($storage == 'external' ? $checked : '') . ' value="external" /></label>

    <script>
    jQuery(document).ready(function() {
	function switch_radio(test) {
		if (test == "internal") {
			jQuery("#aesirx_analytics_domain").parents("tr").hide();
		} else {
			jQuery("#aesirx_analytics_domain").parents("tr").show();
		}
	}
    jQuery("input.analytic-storage-class").click(function() {
		switch_radio(jQuery(this).val())
    });
	switch_radio("' . $storage . '");
});
</script>';
	}, 'aesirx_analytics_plugin', 'aesirx_analytics_settings');

	add_settings_field('aesirx_analytics_domain', __('domain <i>(Use next format: http://example.com:1000/)</i>', 'aesirx-analytics'), function () {
		$options = get_option('aesirx_analytics_plugin_options', []);
		echo "<input id='aesirx_analytics_domain' name='aesirx_analytics_plugin_options[domain]' type='text' value='" . esc_attr($options['domain'] ?? '') . "' />";
	}, 'aesirx_analytics_plugin', 'aesirx_analytics_settings');

	add_settings_field('aesirx_analytics_license', __('License', 'aesirx-analytics'), function () {
		$options = get_option('aesirx_analytics_plugin_options', []);
		echo "<input id='aesirx_analytics_license' name='aesirx_analytics_plugin_options[license]' type='text' value='" . esc_attr($options['license'] ?? '') . "' />";
	}, 'aesirx_analytics_plugin', 'aesirx_analytics_settings');

	add_settings_field(
    'aesirx_bi_domain_react_app_data_stream',
    __('Domains', 'aesirx-bi'),
    function () {
      $options = get_option('aesirx_analytics_plugin_options', []);

      echo '<table width="100%" border="0" id="asirx_bi_wp_setting_table" cellspacing="0">
                <tr>
                    <th width="50%">' .
        __('Name', 'aesirx-bi') .
        '</th>
                    <th width="50%">' .
        __('Domain', 'aesirx-bi') .
        '</th>
                </tr>';

      $rowNumber = 0;

      if (empty(esc_attr($options['aesirx_bi_domain_react_app_data_stream']))) { ?>
            <tr>
                <td>
                    <input class="regular-text ltr" id='aesirx_analytics_plugin_options_stream_name_0' name='aesirx_analytics_plugin_options[aesirx_bi_domain_react_app_data_stream][stream0][name]' type='text' value='' />
                </td>
                <td>
                    <input class="regular-text ltr" id='aesirx_analytics_plugin_options_stream_domain_0' name='aesirx_analytics_plugin_options[aesirx_bi_domain_react_app_data_stream][stream0][domain]' type='text' value='' />
                </td>
            </tr>
            <?php $rowNumber++;} else {foreach (
          $options['aesirx_bi_domain_react_app_data_stream']
          as $key => $data
        ) { ?>
                <tr>
                    <td>
                        <input
                                id='aesirx_analytics_plugin_options_stream_name_<?php echo $rowNumber; ?>'
                                name='aesirx_analytics_plugin_options[aesirx_bi_domain_react_app_data_stream][stream<?php echo $rowNumber; ?>][name]'
                                type='text'
                                class="regular-text ltr"
                                value='<?php echo esc_attr($data['name'] ?? ''); ?>'
                        />
                    </td>
                    <td>
                        <input
                                id='aesirx_analytics_plugin_options_stream_domain_<?php echo $rowNumber; ?>'
                                name='aesirx_analytics_plugin_options[aesirx_bi_domain_react_app_data_stream][stream<?php echo $rowNumber; ?>][domain]'
                                type='text'
                                class="regular-text ltr"
                                value='<?php echo esc_attr($data['domain'] ?? ''); ?>'
                        />
                    </td>
                    <td>
                        <button type="button" class="aesirx_analytics_plugin_options_stream_delete">Delete</button>
                    </td>
                </tr>
                <?php $rowNumber++;}}

      echo '</table>';

      echo '<input type="hidden" name="row_number" id="aesirx_bi_setting_stream_row" value="' .
        $rowNumber .
        '" />';
      echo '<button type="button" onclick="addNewAesirxBISettingRow()" class="button button-secondary" name="aesirx_bi_stream_add_new_row" id="aesirx_bi_stream_add_new_row">' .
        __('ADD', 'aesirx-bi') .
        '</button>';
    },
   'aesirx_analytics_plugin', 'aesirx_analytics_settings'
  );

});

add_action('admin_menu', function () {
	add_options_page(
		__('Aesirx analytics', 'aesirx-analytics'),
		__('Aesirx analytics', 'aesirx-analytics'),
		'manage_options',
		'aesirx-analytics-plugin',
		function () {
			?>
			<form action="options.php" method="post">
				<?php
				settings_fields('aesirx_analytics_plugin_options');
				do_settings_sections('aesirx_analytics_plugin'); ?>
				<input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e('Save'); ?>"/>
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

	$streams = [];

	if (!empty($options['aesirx_bi_domain_react_app_data_stream'])) {
	  foreach ($options['aesirx_bi_domain_react_app_data_stream'] as $key => $data) {
		$stream = [];
		$stream['name'] = $data['name'];
		$stream['domain'] = $data['domain'];

		$streams[] = $stream;
	  }
	}

	$domain = ($options['storage'] ?? 'internal') == 'internal' ? get_bloginfo('url') : ($options['domain'] ?? '');

	?>
	  <script>
		  window.env = {};
		  window.env.REACT_APP_CLIENT_ID = "app";
		  window.env.REACT_APP_CLIENT_SECRET = "secret";
		  window.env.REACT_APP_ENDPOINT_URL = "<?php echo $domain; ?>";
		  window.env.REACT_APP_LICENSE = "<?php echo $options[
			'license'
		  ]; ?>";
		  window.env.REACT_APP_DATA_STREAM = JSON.stringify(<?php echo json_encode($streams); ?>);
		  window.env.PUBLIC_URL="/wp-content/plugins/aesirx-analytics";

		  function addNewAesirxBISettingRow(){
			  var table       = document.getElementById('asirx_bi_wp_setting_table');
			  var rowNumber   = parseInt(document.getElementById('aesirx_bi_setting_stream_row').value);

			  var row = table.insertRow();

			  var cell1 = row.insertCell(0);
			  var cell2 = row.insertCell(1);
			  var cell3 = row.insertCell(2);

			  var streamName = document.createElement("input");
			  streamName.setAttribute("type", "text");
			  streamName.setAttribute("class", "regular-text ltr");
			  streamName.setAttribute("name", "aesirx_analytics_plugin_options[aesirx_bi_domain_react_app_data_stream][stream"+ rowNumber +"][name]");
			  streamName.setAttribute("id","aesirx_analytics_plugin_options_stream_name_"+rowNumber);

			  var streamDomain = document.createElement("input");
			  streamDomain.setAttribute("type", "text");
			  streamDomain.setAttribute("class", "regular-text ltr");
			  streamDomain.setAttribute("name", "aesirx_analytics_plugin_options[aesirx_bi_domain_react_app_data_stream][stream"+ rowNumber +"][domain]");
			  streamDomain.setAttribute("id","aesirx_analytics_plugin_options_stream_domain_"+rowNumber);

			  var streamDelete = document.createElement("button");
			  streamDelete.setAttribute("type", "button");
			  streamDelete.setAttribute("class", "aesirx_analytics_plugin_options_stream_delete");
			  streamDelete.textContent = "Delete";

			  streamDelete.addEventListener('click', function (e) {
				  e.target.parentElement.parentElement.remove();
			  })

			  cell1.appendChild(streamName);
			  cell2.appendChild(streamDomain);
			  cell3.appendChild(streamDelete);

			  rowNumber++;
			  document.getElementById('aesirx_bi_setting_stream_row').value = rowNumber;
			  return false;
		  }

		  document.addEventListener('DOMContentLoaded', function() {
			  document.querySelectorAll('.aesirx_analytics_plugin_options_stream_delete').forEach(function(button) {
				  button.onclick = function(e) {
					  e.target.parentElement.parentElement.remove();
				  }
			  });
		  });

	  </script>
	  <%= htmlWebpackPlugin.tags.headTags %>
	  <?php
  });
