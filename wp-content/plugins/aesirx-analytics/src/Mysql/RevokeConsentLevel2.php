<?php

use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Revoke_Consent_Level2 extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        // validation

        return parent::aesirx_analytics_expired_consent($params['consent_uuid'], date('Y-m-d H:i:s'));
    }
}