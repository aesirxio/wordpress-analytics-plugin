<?php


use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Get_Conversion_Statistic extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        $where_clause = [];
        $bind = [];

        parent::aesirx_analytics_add_conversion_filters($params, $where_clause, $bind);

        $sql =
            "SELECT
            CASE WHEN SUM(revenue_total) IS NOT NULL THEN CAST(SUM(revenue_total) as FLOAT) ELSE 0 END as total_revenue,
            CASE WHEN SUM(revenue_subtotal) IS NOT NULL THEN CAST(SUM(revenue_subtotal) as FLOAT) ELSE 0 END as conversion_rate,
            CASE WHEN SUM(revenue_total) IS NOT NULL THEN CAST(AVG(revenue_total) as FLOAT) ELSE 0 END as avg_order_value,
            CASE WHEN SUM(quantity) IS NOT NULL THEN CAST(SUM(CASE WHEN order_id IS NOT NULL THEN quantity ELSE 0 END) as FLOAT) ELSE 0 END as total_add_to_carts,
            CAST(COUNT(#__analytics_conversion.uuid) as FLOAT) as transactions
            from `#__analytics_conversion`
            left join `#__analytics_conversion_item` on #__analytics_conversion.uuid = #__analytics_conversion_item.conversion_uuid
            left join `#__analytics_flows` on #__analytics_conversion.flow_uuid = #__analytics_flows.uuid 
            WHERE " . implode(" AND ", $where_clause);

        $total_sql =
            "SELECT
            COUNT(DISTINCT name) as total
            from `#__analytics_conversion`
            left join `#__analytics_conversion_item` on #__analytics_conversion.uuid = #__analytics_conversion_item.conversion_uuid
            left join `#__analytics_flows` on #__analytics_conversion.flow_uuid = #__analytics_flows.uuid
            WHERE " . implode(" AND ", $where_clause);

        return parent::aesirx_analytics_get_list($sql, $total_sql, $params, [], $bind);
    }
}
