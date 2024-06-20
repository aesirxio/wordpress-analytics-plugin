<?php

// namespace AesirxAnalytics\Mysql;

use AesirxAnalytics\MysqlHelper;

Class AesirX_Analytics_Get_All_Visitors extends MysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {

        // let mut where_clause: Vec<String> = vec![
        //     "#__analytics_events.event_name = ?".to_string(),
        //     "#__analytics_events.event_type = ?".to_string(),
        // ];
        // let mut bind: Vec<String> = vec!["visit".to_string(), "action".to_string()];
        // add_filters(params, &mut where_clause, &mut bind)?;

        $sql = "SELECT
            DATE_FORMAT(start, '%Y-%m-%d') as date,
            COUNT(DISTINCT #__analytics_events.visitor_uuid) as visits,
            COUNT(DISTINCT #__analytics_events.url) as total_page_views
            from #__analytics_events
            left join #__analytics_visitors on #__analytics_visitors.uuid = #__analytics_events.visitor_uuid
            WHERE #__analytics_events.event_name = 'visit' AND #__analytics_events.event_type = 'action'
            GROUP BY date";

        $total_sql = "SELECT
            COUNT(DISTINCT DATE_FORMAT(start, '%Y-%m-%d')) as total
            from `#__analytics_events`
            left join `#__analytics_visitors` on #__analytics_visitors.uuid = #__analytics_events.visitor_uuid
            WHERE #__analytics_events.event_name = 'visit' AND #__analytics_events.event_type = 'action'";

        // let sort = add_sort(params, vec!["date", "visits", "total_page_views"], "date");

        // if !sort.is_empty() {
        //     sql.push("ORDER BY".to_string());
        //     sql.push(sort.join(","));
        // }

        return parent::get_list($sql, $total_sql, $params);

        // return parent::get_statistics_per_field_wp(
        //     ['#__analytics_visitors.region'],
        //     [],
        //     $params
        // );
    }
}
