<?php

// namespace AesirxAnalytics\Mysql;

use AesirxAnalytics\MysqlHelper;

Class AesirX_Analytics_Get_All_Events extends MysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        // let mut where_clause: Vec<String> = vec![
        //     "#__analytics_events.event_name = ?
        //     "#__analytics_events.event_type = ?
        // ];
        // let mut bind: Vec<String> = vec!["visit "action".to_string()];
        // add_filters(params, &mut where_clause, &mut bind)?;

        $sql =
            "SELECT
            DATE_FORMAT(start, '%Y-%m-%d') as date,
            COUNT(#__analytics_events.visitor_uuid) as visits,
            COUNT(DISTINCT #__analytics_events.visitor_uuid) as unique_visits
            from `#__analytics_events`
            left join `#__analytics_visitors` on #__analytics_visitors.uuid = #__analytics_events.visitor_uuid
            GROUP BY date";

        // let total_sql: Vec<String> = vec![
        //     "SELECT
        //     "COUNT(DISTINCT DATE_FORMAT(start, '%Y-%m-%d')) as total
        //     "from `#__analytics_events`
        //     "left join `#__analytics_visitors` on #__analytics_visitors.uuid = #__analytics_events.visitor_uuid
        //     "WHERE
        //     where_clause.join(" AND "),
        // ];

        // let sort = add_sort(params, vec!["date", "unique_visits", "visits"], "date");

        // if !sort.is_empty() {
        //     sql.push("ORDER BY".to_string());
        //     sql.push(sort.join(","));
        // }

        return parent::get_list($sql, $total_sql, $params);
    }
}
