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
			jQuery("#aesirx_analytics_license").parents("tr").show();
		} else {
			jQuery("#aesirx_analytics_license").parents("tr").hide();
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
});
