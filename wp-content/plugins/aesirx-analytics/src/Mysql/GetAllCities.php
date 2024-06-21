<?php


use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Get_All_Cities extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        return parent::aesirx_analytics_get_statistics_per_field(
            ['#__analytics_visitors.city'],
            [],
            $params
        );
    }
}
