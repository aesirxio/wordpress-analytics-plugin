<?php

use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Revoke_Consent_Level1 extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        // Validate and sanitize each parameter in the $params array
        $validated_params = [];
        foreach ($params as $key => $value) {
            $validated_params[$key] = sanitize_text_field($value);
        }

        global $wpdb;

        $expiration = gmdate('Y-m-d H:i:s');
        $visitor_uuid = $validated_params['visitor_uuid'];

        // Execute the update
        // doing direct database calls to custom tables
        $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prefix . 'analytics_visitor_consent',
            ['expiration' => $expiration],
            ['visitor_uuid' => $visitor_uuid, 'consent_uuid' => null, 'expiration' => null],
            array('%s'),  // Data type for 'expiration'
            array('%s')   // Data type for 'visitor_uuid'
        );

        // Execute the update
        // doing direct database calls to custom tables
        $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prefix . 'analytics_visitors',
            [
                'ip' => '',
                'lang' => '',
                'browser_version' => '',
                'browser_name' => '',
                'device' => '',
                'user_agent' => ''
            ],
            ['uuid' => $visitor_uuid],
        );

        if ($wpdb->last_error) {
            error_log('Query error: ' . $wpdb->last_error);
            return new WP_Error($wpdb->last_error);
        }
        
        return true;
    }
}