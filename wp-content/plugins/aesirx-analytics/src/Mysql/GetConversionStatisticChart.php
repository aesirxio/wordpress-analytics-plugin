<?php

// namespace AesirxAnalytics\Mysql;

use AesirxAnalytics\MysqlHelper;

Class AesirX_Analytics_Get_Conversion_Statistic_Chart extends MysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        // let mut where_clause: Vec<String> = vec![];
        // let mut bind: Vec<String> = vec![];

        // add_conversion_filters(params, &mut where_clause, &mut bind)?;

        $sql =
            "SELECT
            DATE_FORMAT(start, '%Y-%m-%d') as date,
            CAST(SUM(revenue_total) as FLOAT) as total_revenue,
            CAST(SUM(CASE WHEN order_id IS NOT NULL THEN 1 ELSE 0 END) as FLOAT) as total_purchasers
            from `#__analytics_conversion`
            left join `#__analytics_conversion_item` on #__analytics_conversion.uuid = #__analytics_conversion_item.conversion_uuid
            left join `#__analytics_flows` on #__analytics_conversion.flow_uuid = #__analytics_flows.uuid
            GROUP BY date";

        // let total_sql: Vec<String> = vec![
        //     "SELECT
        //     "COUNT(DISTINCT DATE_FORMAT(start, '%Y-%m-%d')) as total
        //     "from `#__analytics_conversion`
        //     "left join `#__analytics_conversion_item` on #__analytics_conversion.uuid = #__analytics_conversion_item.conversion_uuid
        //     "left join `#__analytics_flows` on #__analytics_conversion.flow_uuid = #__analytics_flows.uuid
        //     "WHERE
        //     where_clause.join(" AND "),
        // ];

        // let sort = add_sort(
        //     params,
        //     vec!["date", "total_revenue", "total_purchasers"],
        //     "quantity",
        // );

        // if !sort.is_empty() {
        //     sql.push("ORDER BY".to_string());
        //     sql.push(sort.join(","));
        // }

        return parent::get_list($sql, $total_sql, $params);
    }
}
