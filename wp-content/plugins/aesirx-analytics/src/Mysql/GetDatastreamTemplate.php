<?php


use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Get_Datastream_Template extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        return [
            'domain' => get_option('aesirx_analytics_plugin_options_datastream_domain', ''),
            'template' => get_option('aesirx_analytics_plugin_options_datastream_template', ''),
            'gtag_id' => get_option('aesirx_analytics_plugin_options_datastream_gtag_id', ''),
            'gtm_id' => get_option('aesirx_analytics_plugin_options_datastream_gtm_id', ''),
            'consent' => get_option('aesirx_analytics_plugin_options_datastream_consent', ''),
        ];
    }
}
