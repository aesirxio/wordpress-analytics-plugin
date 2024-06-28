<?php


use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Get_All_Events_Name extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        global $wpdb;

        $where_clause = [
            "#__analytics_events.event_name = %s",
            "#__analytics_events.event_type = %s",
        ];

        $bind = [
            'visit',
            'action'
        ];

        parent::aesirx_analytics_add_filters($params, $where_clause, $bind);
        parent::aesirx_analytics_add_attribute_filters($params, $where_clause, $bind);

        $sql =
            "SELECT
            DATE_FORMAT(start, '%%Y-%%m-%%d') as date,
            #__analytics_events.event_name,
            #__analytics_events.event_type,
            COUNT(DISTINCT #__analytics_events.visitor_uuid) as total_visitor
            from `#__analytics_events`
            left join `#__analytics_visitors` on #__analytics_visitors.uuid = #__analytics_events.visitor_uuid
            left join `#__analytics_event_attributes` on #__analytics_event_attributes.event_uuid = #__analytics_events.uuid
            WHERE " . implode(" AND ", $where_clause) .
            " GROUP BY date, #__analytics_events.event_name, #__analytics_events.event_type";

        $total_sql =
            "SELECT
            COUNT(DISTINCT DATE_FORMAT(start, '%%Y-%%m-%%d'), #__analytics_events.event_name, #__analytics_events.event_type) as total
            from `#__analytics_events`
            left join `#__analytics_visitors` on #__analytics_visitors.uuid = #__analytics_events.visitor_uuid
            left join `#__analytics_event_attributes` on #__analytics_event_attributes.event_uuid = #__analytics_events.uuid
            WHERE " . implode(" AND ", $where_clause);

        $sort = self::aesirx_analytics_add_sort($params, ["date", "event_name", "total_visitor", "event_type"], "date");

        if (!empty($sort)) {
            $sql .= " ORDER BY " . implode(", ", $sort);
        }

        return parent::aesirx_analytics_get_list($sql, $total_sql, $params, [], $bind);
    }
}
