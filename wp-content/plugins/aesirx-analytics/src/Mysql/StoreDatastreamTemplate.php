<?php


use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Store_Datastream_Template extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        foreach ($params as $key => $value) {
            if (is_string($key)) {
                $new_value = sanitize_text_field($value);
                update_option('aesirx_analytics_plugin_options_datastream_' . $key, $new_value);
                $response[$key] = sanitize_text_field($new_value);
            }
        }

        return $response;
    }
}
