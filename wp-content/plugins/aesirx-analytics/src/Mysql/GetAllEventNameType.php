<?php


use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Get_All_Event_Name_Type extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        $where_clause = [];
        $bind = [];

        parent::aesirx_analytics_add_filters($params, $where_clause, $bind);

        $sql= "SELECT
            #__analytics_events.event_name,
            #__analytics_events.event_type,
            COUNT(#__analytics_events.uuid) as total_visitor,
            COUNT(DISTINCT #__analytics_events.visitor_uuid) as unique_visitor
            from #__analytics_events
            left join `#__analytics_visitors` on #__analytics_visitors.uuid = #__analytics_events.visitor_uuid 
            WHERE ".implode(" AND ", $where_clause).
            " GROUP BY #__analytics_events.event_name, #__analytics_events.event_type";

       $total_sql =
            "SELECT
            COUNT(DISTINCT #__analytics_events.event_name, #__analytics_events.event_type) as total
            from `#__analytics_events`
            left join `#__analytics_visitors` on #__analytics_visitors.uuid = #__analytics_events.visitor_uuid
            WHERE ".implode(" AND ", $where_clause);

        $sort = self::aesirx_analytics_add_sort($params, ["event_name", "total_visitor", "event_type", "unique_visitor"], "event_name");

        if (!empty($sort)) {
            $sql .= " ORDER BY " . implode(", ", $sort);
        }

        return parent::aesirx_analytics_get_list($sql, $total_sql, $params, [], $bind);
    }
}
