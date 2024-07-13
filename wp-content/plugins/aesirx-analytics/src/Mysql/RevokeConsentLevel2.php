<?php

use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Revoke_Consent_Level2 extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        // Validate and sanitize each parameter in the $params array
        $validated_params = [];
        foreach ($params as $key => $value) {
            $validated_params[$key] = sanitize_text_field($value);
        }

        return parent::aesirx_analytics_expired_consent($validated_params['consent_uuid'], gmdate('Y-m-d H:i:s'));
    }
}