<?php

// namespace AesirxAnalytics\Mysql;

use AesirxAnalytics\MysqlHelper;

Class AesirX_Analytics_Get_All_Event_Name_Type extends MysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        // let mut where_clause: Vec<String> = vec![];
        // let mut bind: Vec<String> = vec![];

        // add_filters(params, &mut where_clause, &mut bind)?;

        $sql= "SELECT
            #__analytics_events.event_name,
            #__analytics_events.event_type,
            COUNT(#__analytics_events.uuid) as total_visitor,
            COUNT(DISTINCT #__analytics_events.visitor_uuid) as unique_visitor
            from #__analytics_events
            left join `#__analytics_visitors` on #__analytics_visitors.uuid = #__analytics_events.visitor_uuid
            GROUP BY #__analytics_events.event_name, #__analytics_events.event_type";

        // let total_sql: Vec<String> = vec![
        //     "SELECT".to_string(),
        //     "COUNT(DISTINCT #__analytics_events.event_name, #__analytics_events.event_type) as total".to_string(),
        //     "from `#__analytics_events`".to_string(),
        //     "left join `#__analytics_visitors` on #__analytics_visitors.uuid = #__analytics_events.visitor_uuid".to_string(),
        //     "WHERE".to_string(),
        //     where_clause.join(" AND "),
        // ];

        // let sort = add_sort(
        //     params,
        //     vec![
        //         "event_name",
        //         "total_visitor",
        //         "event_type",
        //         "unique_visitor",
        //     ],
        //     "event_name",
        // );

        // if !sort.is_empty() {
        //     sql.push("ORDER BY".to_string());
        //     sql.push(sort.join(","));
        // }

        return parent::get_list($sql, $total_sql, $params);
    }
}
