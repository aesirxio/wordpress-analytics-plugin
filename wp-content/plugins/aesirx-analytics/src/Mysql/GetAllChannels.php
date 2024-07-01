<?php


use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Get_All_Channels extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        global $wpdb;
        $where_clause = [];
        $bind = [];

        $select = [
            "CASE 
                WHEN #__analytics_events.referer IS NOT NULL AND #__analytics_events.referer <> '' THEN
                    CASE 
                        WHEN #__analytics_events.referer REGEXP 'google\\.' THEN 'search'
                        WHEN #__analytics_events.referer REGEXP 'bing\\.' THEN 'search'
                        WHEN #__analytics_events.referer REGEXP 'yandex\\.' THEN 'search'
                        WHEN #__analytics_events.referer REGEXP 'yahoo\\.' THEN 'search'
                        WHEN #__analytics_events.referer REGEXP 'duckduckgo\\.' THEN 'search'
                        ELSE 'referer'
                    END
                ELSE 'direct'
            END as channel",
            "coalesce(COUNT(DISTINCT (#__analytics_events.visitor_uuid)), 0) as number_of_visitors",
            "coalesce(COUNT(#__analytics_events.visitor_uuid), 0) as total_number_of_visitors",
            "COUNT(#__analytics_events.uuid) as number_of_page_views",
            "COUNT(DISTINCT (#__analytics_events.url)) AS number_of_unique_page_views",
            "coalesce(SUM(TIMESTAMPDIFF(SECOND, #__analytics_events.start, #__analytics_events.end)) / count(distinct #__analytics_visitors.uuid), 0) DIV 1 as average_session_duration",
            "coalesce((COUNT(#__analytics_events.uuid) / COUNT(DISTINCT (#__analytics_events.flow_uuid))), 0) DIV 1 as average_number_of_pages_per_session",
            "coalesce((count(DISTINCT CASE WHEN #__analytics_flows.multiple_events = 0 THEN #__analytics_flows.uuid END) * 100) / count(DISTINCT (#__analytics_flows.uuid)), 0) DIV 1 as bounce_rate",
        ];

        parent::aesirx_analytics_add_filters($params, $where_clause, $bind);

        $acquisition = false;
        foreach ($params['filter'] as $key => $vals) {
            if ($key === "acquisition") {
                $list = is_array($vals) ? $vals : [$vals];
                if ($list[0] === "true") {
                    $acquisition = true;
                }
                break;
            }
        }

        if ($acquisition) {
            $where_clause[] = "#__analytics_flows.multiple_events = %d";
            $bind[] = 0;
        }

        $sql = "SELECT " . 
            implode(", ", $select) .
            " from #__analytics_events
            left join #__analytics_visitors on #__analytics_visitors.uuid = #__analytics_events.visitor_uuid
            left join #__analytics_flows on #__analytics_flows.uuid = #__analytics_events.flow_uuid
            WHERE " . implode(" AND ", $where_clause) .
            " GROUP BY channel";

        $total_select = "3 AS total";

        $total_sql =
            "SELECT " .
            $total_select .
            " from #__analytics_events
            left join #__analytics_visitors on #__analytics_visitors.uuid = #__analytics_events.visitor_uuid
            left join #__analytics_flows on #__analytics_flows.uuid = #__analytics_events.flow_uuid
            WHERE " . implode(" AND ", $where_clause);

        $allowed = [
            "number_of_visitors",
            "number_of_page_views",
            "number_of_unique_page_views",
            "average_session_duration",
            "average_number_of_pages_per_session",
            "bounce_rate",
        ];

        $sort = self::aesirx_analytics_add_sort($params, $allowed, "channel");

        if (!empty($sort)) {
            $sql .= " ORDER BY " . implode(", ", $sort);
        }

        return parent::aesirx_analytics_get_list($sql, $total_sql, $params, $allowed, $bind);
    }
}
