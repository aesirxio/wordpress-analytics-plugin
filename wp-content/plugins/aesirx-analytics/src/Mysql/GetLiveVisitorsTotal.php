<?php

use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Get_All_Flows extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        global $wpdb;

        $where_clause = [
            "#__analytics_flows.start = #__analytics_flows.end",
        ];
        $bind = [];

        parent::aesirx_analytics_add_filters($params, $where_clause, $bind);

        $sql =
            "SELECT COUNT(DISTINCT #__analytics_flows.visitor_uuid) as total
            from `#__analytics_flows`
            left join `#__analytics_visitors` on #__analytics_visitors.uuid = #__analytics_flows.visitor_uuid
            WHERE " . implode(" AND ", $where_clause);

        $total = (int) $wpdb->get_var(
            $wpdb->prepare($sql, $bind)
        );
        
        return [
            "total" => $total
        ];
    }
}
