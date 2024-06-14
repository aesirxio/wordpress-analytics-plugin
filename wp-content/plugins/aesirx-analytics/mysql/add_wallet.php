<?php
class AesirX_Analytics_Add_Consent extends AesirX_Analytics_Mysql
{
    function aesirx_analytics_mysql_execute($params)
    {
        $query = "INSERT INTO #__analytics_wallet (uuid, network, address, nonce) values " . $params['uuid'] . ', ' . $params['network'] . ', ' . $params['address'] . ', ' . $params['nonce'];

        return $wpdb->get_results($query);
    }
}
