<?php
class AesirX_Analytics_Add_Consent extends AesirX_Analytics_Mysql
{
    function aesirx_analytics_mysql_execute($params)
    {
        $query = "INSERT INTO #__analytics_consent (uuid, consent, datetime";
        $value = $params['uuid'] . ', ' . $params['consent'] . ', ' . $params['datetime'];

        if ($params['wallet_uuid'])
        {
            $query .= ", wallet_uuid";
            $value .= ", " . $params['wallet_uuid'];
        }

        if ($params['web3id'])
        {
            $query .= ", web3id";
            $value .= ", " . $params['web3id'];
        }

        if ($params['expiration'])
        {
            $query .= ", expiration";
            $value .= ", " . $params['expiration'];
        }

        $query .= ") values (" . $value . ")";

        return $wpdb->get_results($query);
    }
}
