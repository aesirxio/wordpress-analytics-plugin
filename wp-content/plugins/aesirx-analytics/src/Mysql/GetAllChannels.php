<?php

// namespace AesirxAnalytics\Mysql;

use AesirxAnalytics\MysqlHelper;

Class AesirX_Analytics_Get_All_Channels extends MysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        // let mut where_clause: Vec<String> = vec![];
        // let mut bind: Vec<String> = vec![];

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

        // add_filters(params, &mut where_clause, &mut bind)?;

        // let mut acquisition = false;

        // for (key, vals) in params.clone().filter.as_ref().unwrap().iter() {
        //     if key == "acquisition" {
        //         if let Some(list) = get_parameters_as_array(vals) {
        //             if list.first().unwrap() == "true" {
        //                 acquisition = true;
        //             }
        //         }

        //         break;
        //     }
        // }

        // if acquisition {
        //     where_clause.push("#__analytics_flows.multiple_events = 0")
        // }

        $sql = "SELECT " . 
            implode(", ", $select) .
            " from #__analytics_events
            left join #__analytics_visitors on #__analytics_visitors.uuid = #__analytics_events.visitor_uuid
            left join #__analytics_flows on #__analytics_flows.uuid = #__analytics_events.flow_uuid
            GROUP BY channel";

        // let total_select: Vec<String> = vec!["count(CASE 
        //     WHEN #__analytics_events.referer IS NOT NULL AND #__analytics_events.referer <> '' THEN
        //             CASE 
        //                 WHEN #__analytics_events.referer REGEXP 'google\\.' THEN 'search'
        //                 WHEN #__analytics_events.referer REGEXP 'bing\\.' THEN 'search'
        //                 WHEN #__analytics_events.referer REGEXP 'yandex\\.' THEN 'search'
        //                 WHEN #__analytics_events.referer REGEXP 'yahoo\\.' THEN 'search'
        //                 WHEN #__analytics_events.referer REGEXP 'duckduckgo\\.' THEN 'search'
        //                 ELSE 'referer'
        //             END
        //         ELSE 'direct'
        // END) AS total"
        //     ];

        // let total_sql: Vec<String> = vec![
        //     "SELECT ",
        //     total_select.join(", "),
        //     "from `#__analytics_events`",
        //     "left join `#__analytics_visitors` on #__analytics_visitors.uuid = #__analytics_events.visitor_uuid",
        //     "left join `#__analytics_flows` on #__analytics_flows.uuid = #__analytics_events.flow_uuid",
        //     "WHERE",
        //     where_clause.join(" AND "),
        // ];

        // let allowed = vec![
        //     "number_of_visitors",
        //     "number_of_page_views",
        //     "number_of_unique_page_views",
        //     "average_session_duration",
        //     "average_number_of_pages_per_session",
        //     "bounce_rate",
        //     "channel",
        // ];

        // let sort = add_sort(params, allowed, "channel");

        // if !sort.is_empty() {
        //     sql.push("ORDER BY");
        //     sql.push(sort.join(","));
        // }

        return parent::get_list($sql, $total_sql, $params);
    }
}
