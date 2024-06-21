<?php

// namespace AesirxAnalytics\Mysql;

use AesirxAnalytics\MysqlHelper;

Class AesirX_Analytics_Get_All_Events extends MysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        $where_clause = [
            "#__analytics_events.event_name = 'visit'",
            "#__analytics_events.event_type = 'action'",
        ];

        self::add_filters($params, $where_clause);

        $sql =
            "SELECT
            DATE_FORMAT(start, '%Y-%m-%d') as date,
            COUNT(#__analytics_events.visitor_uuid) as visits,
            COUNT(DISTINCT #__analytics_events.visitor_uuid) as unique_visits
            from `#__analytics_events`
            left join `#__analytics_visitors` on #__analytics_visitors.uuid = #__analytics_events.visitor_uuid 
            WHERE " . implode(" AND ", $where_clause) .
            " GROUP BY date";

        $total_sql =
            "SELECT
            COUNT(DISTINCT DATE_FORMAT(start, '%Y-%m-%d')) as total
            from `#__analytics_events`
            left join `#__analytics_visitors` on #__analytics_visitors.uuid = #__analytics_events.visitor_uuid
            WHERE " . implode(" AND ", $where_clause);

        $sort = self::add_sort($params, ["date", "unique_visits", "visits"], "date");

        if (!empty($sort)) {
            $sql .= " ORDER BY " . implode(", ", $sort);
        }

        return parent::get_list($sql, $total_sql, $params);
    }
}
