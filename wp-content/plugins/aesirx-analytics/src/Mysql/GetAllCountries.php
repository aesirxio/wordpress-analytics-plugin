<?php


use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Get_All_Countries extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        return parent::aesirx_analytics_get_statistics_per_field(
            ['#__analytics_visitors.country_code'],
            [
                ['select' => '#__analytics_visitors.country_name', 'result' => 'country_name'],
            ],
            $params
        );
    }
}
