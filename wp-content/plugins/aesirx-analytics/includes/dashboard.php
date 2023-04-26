<?php
$options = get_option('aesirx_analytics_plugin_options');

if (empty($options['domain']) && empty($options['license'])) {
wp_redirect('/wp-admin/options-general.php?page=aesirx-analytics-plugin');
}

?>


<div id="biapp"></div>