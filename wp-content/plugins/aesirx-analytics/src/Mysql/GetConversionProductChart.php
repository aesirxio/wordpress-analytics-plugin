<?php


use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Get_Conversion_Product_Chart extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        $where_clause = [];
        $bind = [];

        parent::aesirx_analytics_add_conversion_filters($params, $where_clause, $bind);

        $sql =
            "SELECT
            DATE_FORMAT(start, '%%Y-%%m-%%d') as date,
            SUM(quantity) DIV 1 as quantity
            from `#__analytics_conversion`
            left join `#__analytics_conversion_item` on #__analytics_conversion.uuid = #__analytics_conversion_item.conversion_uuid
            left join `#__analytics_flows` on #__analytics_conversion.flow_uuid = #__analytics_flows.uuid
            WHERE " . implode(" AND ", $where_clause) .
            " GROUP BY date";

        $total_sql =
            "SELECT
            COUNT(DISTINCT DATE_FORMAT(start, '%%Y-%%m-%%d')) as total
            from `#__analytics_conversion`
            left join `#__analytics_conversion_item` on #__analytics_conversion.uuid = #__analytics_conversion_item.conversion_uuid
            left join `#__analytics_flows` on #__analytics_conversion.flow_uuid = #__analytics_flows.uuid
            WHERE " . implode(" AND ", $where_clause);

        $sort = self::aesirx_analytics_add_sort($params, ["date", "quantity"], "date");

        if (!empty($sort)) {
            $sql .= " ORDER BY " . implode(", ", $sort);
        }
        
        return parent::aesirx_analytics_get_list($sql, $total_sql, $params, [], $bind);
    }
}
