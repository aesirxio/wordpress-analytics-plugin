<?php

use AesirxAnalytics\AesirxAnalyticsMysqlHelper;
use WP_Error;

Class AesirX_Analytics_Start_Fingerprint extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        $now = date('Y-m-d H:i:s');
        $options = get_option('aesirx_analytics_plugin_options');
        $config =[
            'url_api_enrich' => 'https://dev01.aesirx.io/index.php?webserviceClient=site&webserviceVersion=1.0.0&option=aesir_analytics&api=hal&task=enrichVisitor',
            'license' => $options['license']
        ];

        $list = parent::aesirx_analytics_get_ip_list_without_geo($params);

        if (count($list) == 0) {
            return;
        }

        $client = new WP_Http();

        $response = $client->request( $config['url_api_enrich'], array(
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'licenses' => $config['license'],
                'ip' => $list,
            )),
        ));
    
        if ( is_wp_error( $response ) ) {
            error_log("Error in API request: " . $response->get_error_message());
            return new WP_Error('api_request_error', esc_html__('Error in API request: ', 'aesirx-analytics'), $response->get_error_message());
        }
    
        $body = wp_remote_retrieve_body( $response );
        $enrich = json_decode( $body, true );
    
        if (isset($enrich['error'])) {
            return new WP_Error('api_error', esc_html__('API error: ', 'aesirx-analytics'), $enrich['error']['message']);
        }
    
        // Update geo information for IPs
        foreach ($enrich['result'] as $result) {
            parent::aesirx_analytics_update_null_geo_per_ip(
                $result['ip'],
                array(
                    'country' => array(
                        'name' => $result['country_name'],
                        'code' => $result['country_code']
                    ),
                    'city' => $result['city'],
                    'region' => $result['region'],
                    'isp' => $result['isp'],
                    'created_at' => $now->format('Y-m-d H:i:s'),
                )
            );
        }
    }
}
