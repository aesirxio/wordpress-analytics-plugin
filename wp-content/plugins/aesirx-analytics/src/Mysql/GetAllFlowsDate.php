<?php

use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Get_All_Flows_Date extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        $where_clause = [];
        $bind = [];
        
        parent::aesirx_analytics_add_filters($params, $where_clause, $bind);

        $sql =
            "SELECT
            DATE_FORMAT(start, '%%Y-%%m-%%d') as date,
            CAST(SUM(CASE WHEN #__analytics_events.event_type = 'conversion' THEN 1 ELSE 0 END) as SIGNED) AS conversion, 
            CAST(SUM(CASE WHEN #__analytics_events.event_name = 'visit' THEN 1 ELSE 0 END) as SIGNED) AS pageview, 
            CAST(SUM(CASE WHEN #__analytics_events.event_name != 'visit' THEN 1 ELSE 0 END) as SIGNED) AS event 
            from `#__analytics_events`
            left join `#__analytics_visitors` on #__analytics_visitors.uuid = #__analytics_events.visitor_uuid
            WHERE " . implode(" AND ", $where_clause) .
            " GROUP BY date";

        $total_sql =
            "SELECT
            COUNT(DISTINCT DATE_FORMAT(start, '%%Y-%%m-%%d')) as total
            from `#__analytics_events`
            left join `#__analytics_visitors` on #__analytics_visitors.uuid = #__analytics_events.visitor_uuid
            WHERE " . implode(" AND ", $where_clause);

        $sort = parent::aesirx_analytics_add_sort($params, ["date", "event", "conversion"], "date");

        if (!empty($sort)) {
            $sql .= " ORDER BY " . implode(", ", $sort);
        }

        return parent::aesirx_analytics_get_list($sql, $total_sql, $params, [], $bind);
    }
}
