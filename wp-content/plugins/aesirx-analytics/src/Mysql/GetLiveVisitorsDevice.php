<?php

use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Get_Live_Visitors_Device extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        global $wpdb;

        unset($params["filter"]["start"]);
        unset($params["filter"]["end"]);
    
        $select = [
            "coalesce(COUNT(DISTINCT (#__analytics_events.visitor_uuid)), 0) as number_of_visitors",
            "coalesce(COUNT(#__analytics_events.visitor_uuid), 0) as total_number_of_visitors",
            "COUNT(#__analytics_events.uuid) as number_of_page_views",
            "COUNT(DISTINCT (#__analytics_events.url)) AS number_of_unique_page_views",
            "coalesce(SUM(TIMESTAMPDIFF(SECOND, #__analytics_events.start, #__analytics_events.end)) / count(distinct #__analytics_visitors.uuid), 0) DIV 1 as average_session_duration",
            "coalesce((COUNT(#__analytics_events.uuid) / COUNT(DISTINCT (#__analytics_events.flow_uuid))), 0) DIV 1 as average_number_of_pages_per_session",
            "coalesce((count(DISTINCT CASE WHEN #__analytics_flows.multiple_events = 0 THEN #__analytics_flows.uuid END) * 100) / count(DISTINCT (#__analytics_flows.uuid)), 0) DIV 1 as bounce_rate",
        ];

        $total_select = [];

        $groups = [
            "#__analytics_visitors.device"
        ];

        if (!empty($groups)) {
            foreach ($groups as $one_group) {
                $select[] = $one_group;
            }

            $total_select[] = "COUNT(DISTINCT " . implode(', COALESCE(', $groups) . ") AS total";
        }
        else {
            $total_select[] = "COUNT(#__analytics_events.uuid) AS total";
        }

        $where_clause = [
            "#__analytics_events.event_name = %s",
            "#__analytics_events.event_type = %s",
            "#__analytics_events.start = #__analytics_events.end",
            "#__analytics_events.start >= NOW() - INTERVAL 30 MINUTE",
            "#__analytics_visitors.device != 'bot'"
        ];

        $bind = [
            'visit',
            'action'
        ];

        parent::aesirx_analytics_add_filters($params, $where_clause, $bind);

        $total_sql = "SELECT " . implode(", ", $total_select) . " FROM #__analytics_events
                    LEFT JOIN #__analytics_visitors ON #__analytics_visitors.uuid = #__analytics_events.visitor_uuid
                    LEFT JOIN #__analytics_flows ON #__analytics_flows.uuid = #__analytics_events.flow_uuid
                    WHERE " . implode(" AND ", $where_clause);

        $sql = "SELECT " . implode(", ", $select) . " FROM #__analytics_events
                LEFT JOIN #__analytics_visitors ON #__analytics_visitors.uuid = #__analytics_events.visitor_uuid
                LEFT JOIN #__analytics_flows ON #__analytics_flows.uuid = #__analytics_events.flow_uuid
                WHERE " . implode(" AND ", $where_clause);

        if (!empty($groups)) {
            $sql .= " GROUP BY " . implode(", ", $groups);
        }

        $allowed = [
            "number_of_visitors",
            "number_of_page_views",
            "number_of_unique_page_views",
            "average_session_duration",
            "average_number_of_pages_per_session",
            "bounce_rate",
        ];
        $default = reset($allowed);

        foreach ($groups as $one_group) {
            $allowed[] = $one_group;
            $default = $one_group;
        }

        foreach ($groups as $additional_result) {
            $allowed[] = $additional_result;
        }

        $sort = parent::aesirx_analytics_add_sort($params, $allowed, $default);

        if (!empty($sort)) {
            $sql .= " ORDER BY " . implode(", ", $sort);
        }

        return parent::aesirx_analytics_get_list($sql, $total_sql, $params, $allowed, $bind);
    }
}
