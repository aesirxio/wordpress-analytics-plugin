<?php

use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

include WP_PLUGIN_DIR . '/src/Mysql/GetVisitorConsentList.php';

Class AesirX_Analytics_Add_Consent_Level1 extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        $now = date('Y-m-d H:i:s');

        $class = new \AesirX_Analytics_Get_Visitor_Consent_List();

        $consents = $class->aesirx_analytics_mysql_execute($params);
    
        foreach ($consents as $consent) {
            // Check if it's level1
            if (is_null($consent->consent_uuid)) {
                // Check if it's the same consent number
                if (!is_null($consent->consent) && $consent->consent != (int) $params['consent']) {
                    continue;
                }
    
                // Check if it's expired
                if (!is_null($consent->expiration) && $consent->expiration > $now) {
                    return new WP_Error('not_expired', esc_html__('Previous consent was not expired', 'aesirx-analytics'), ['status' => 400]);
                }
            }
        }

        return parent::aesirx_analytics_add_visitor_consent(
            $params['uuid'],
            null,
            $params['consent'],
            $now,
            date('Y-m-d H:i:s', strtotime('+30 minutes', strtotime($now))),
        );
    }
}