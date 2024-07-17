<?php


use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Get_All_Visitors extends AesirxAnalyticsMysqlHelper
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

        $sql = "SELECT
            DATE_FORMAT(start, '%%Y-%%m-%%d') as date,
            COUNT(DISTINCT #__analytics_events.visitor_uuid) as visits,
            COUNT(DISTINCT #__analytics_events.url) as total_page_views
            from #__analytics_events
            left join #__analytics_visitors on #__analytics_visitors.uuid = #__analytics_events.visitor_uuid
            WHERE " . implode(" AND ", $where_clause) .
            " GROUP BY date";

        $total_sql = "SELECT
            COUNT(DISTINCT DATE_FORMAT(start, '%%Y-%%m-%%d')) as total
            from `#__analytics_events`
            left join `#__analytics_visitors` on #__analytics_visitors.uuid = #__analytics_events.visitor_uuid
            WHERE " . implode(" AND ", $where_clause);

        $sort = self::aesirx_analytics_add_sort($params, ["date", "visits", "total_page_views"], "date");

        if (!empty($sort)) {
            $sql .= " ORDER BY " . implode(", ", $sort);
        }

        return parent::aesirx_analytics_get_list($sql, $total_sql, $params, [], $bind);
    }
}
