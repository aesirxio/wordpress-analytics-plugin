<?php

// namespace AesirxAnalytics\Mysql;

use AesirxAnalytics\MysqlHelper;

Class AesirX_Analytics_Get_All_Events_Name extends MysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        global $wpdb;

        // let mut where_clause: Vec<String> = vec![];
        // let mut bind: Vec<String> = vec![];

        // add_filters(params, &mut where_clause, &mut bind)?;

        // add_attribute_filters(params, &mut where_clause, &mut bind);

        $sql =
            "SELECT
            DATE_FORMAT(start, '%Y-%m-%d') as date,
            #__analytics_events.event_name,
            #__analytics_events.event_type,
            COUNT(DISTINCT #__analytics_events.visitor_uuid) as total_visitor
            from `#__analytics_events`
            left join `#__analytics_visitors` on #__analytics_visitors.uuid = #__analytics_events.visitor_uuid
            left join `#__analytics_event_attributes` on #__analytics_event_attributes.event_uuid = #__analytics_events.uuid
            GROUP BY date, #__analytics_events.event_name, #__analytics_events.event_type";

        // let total_sql: Vec<String> = vec![
        //     "SELECT
        //     "COUNT(DISTINCT DATE_FORMAT(start, '%Y-%m-%d'), #__analytics_events.event_name, #__analytics_events.event_type) as total
        //     "from `#__analytics_events`
        //     "left join `#__analytics_visitors` on #__analytics_visitors.uuid = #__analytics_events.visitor_uuid
        //     "left join `#__analytics_event_attributes` on #__analytics_event_attributes.event_uuid = #__analytics_events.uuid
        //     "WHERE
        //     where_clause.join(" AND "),
        // ];

        // let sort = add_sort(
        //     params,
        //     vec!["date", "event_name", "total_visitor", "event_type"],
        //     "date",
        // );

        // if !sort.is_empty() {
        //     sql.push("ORDER BY".to_string());
        //     sql.push(sort.join(","));
        // }

        return parent::get_list($sql, $total_sql, $params);
    }
}
