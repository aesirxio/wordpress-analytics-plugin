<?php

// namespace AesirxAnalytics\Mysql;

use AesirxAnalytics\MysqlHelper;

Class AesirX_Analytics_Get_Conversion_Statistic extends MysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        // let mut where_clause: Vec<String> = vec![];
        // let mut bind: Vec<String> = vec![];

        // add_conversion_filters(params, &mut where_clause, &mut bind)?;

        $sql =
            "SELECT
            CASE WHEN SUM(revenue_total) IS NOT NULL THEN CAST(SUM(revenue_total) as FLOAT) ELSE 0 END as total_revenue,
            CASE WHEN SUM(revenue_subtotal) IS NOT NULL THEN CAST(SUM(revenue_subtotal) as FLOAT) ELSE 0 END as conversion_rate,
            CASE WHEN SUM(revenue_total) IS NOT NULL THEN CAST(AVG(revenue_total) as FLOAT) ELSE 0 END as avg_order_value,
            CASE WHEN SUM(quantity) IS NOT NULL THEN CAST(SUM(CASE WHEN order_id IS NOT NULL THEN quantity ELSE 0 END) as FLOAT) ELSE 0 END as total_add_to_carts,
            CAST(COUNT(#__analytics_conversion.uuid) as FLOAT) as transactions
            from `#__analytics_conversion`
            left join `#__analytics_conversion_item` on #__analytics_conversion.uuid = #__analytics_conversion_item.conversion_uuid
            left join `#__analytics_flows` on #__analytics_conversion.flow_uuid = #__analytics_flows.uuid";

        // let total_sql: Vec<String> = vec![
        //     "SELECT
        //     "COUNT(DISTINCT name) as total
        //     "from `#__analytics_conversion`
        //     "left join `#__analytics_conversion_item` on #__analytics_conversion.uuid = #__analytics_conversion_item.conversion_uuid
        //     "left join `#__analytics_flows` on #__analytics_conversion.flow_uuid = #__analytics_flows.uuid
        //     "WHERE
        //     where_clause.join(" AND "),
        // ];

        return parent::get_list($sql, $total_sql, $params);
    }
}
