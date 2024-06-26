<?php

use AesirxAnalytics\MysqlHelper;

Class AesirX_Analytics_Revoke_Consent_Level2 extends MysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        // jwt_handler.decode_web3id(params.jwt.clone())?;

        return parent::expired_consent($params['consent_uuid'], date('Y-m-d H:i:s'));
    }
}