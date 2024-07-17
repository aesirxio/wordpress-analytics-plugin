<?php


use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Get_Conversion_Product extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        $where_clause = [];
        $bind = [];

        parent::aesirx_analytics_add_conversion_filters($params, $where_clause, $bind);

        $sql =
            "SELECT
            name as product,
            sku,
            extension,
            SUM(quantity) DIV 1 as quantity,
            COUNT(quantity) as items_sold,
            SUM(revenue_subtotal) DIV 1 as product_revenue,
            CAST(AVG(price) as FLOAT) as avg_price,
            CAST((SUM(quantity) / COUNT(quantity)) as FLOAT) as avg_quantity
            from `#__analytics_conversion`
            left join `#__analytics_conversion_item` on #__analytics_conversion.uuid = #__analytics_conversion_item.conversion_uuid
            left join `#__analytics_flows` on #__analytics_conversion.flow_uuid = #__analytics_flows.uuid 
            WHERE " . implode(" AND ", $where_clause) .
            " GROUP BY name, sku, extension";

        $total_sql =
            "SELECT
            COUNT(DISTINCT name, sku, extension) as total
            from `#__analytics_conversion`
            left join `#__analytics_conversion_item` on #__analytics_conversion.uuid = #__analytics_conversion_item.conversion_uuid
            left join `#__analytics_flows` on #__analytics_conversion.flow_uuid = #__analytics_flows.uuid
            WHERE " . implode(" AND ", $where_clause);

        $sort = self::aesirx_analytics_add_sort(
            $params,
            [
                "product",
                "sku",
                "extension",
                "quantity",
                "items_sold",
                "product_revenue",
                "avg_price",
                "avg_quantity",
            ],
            "quantity"
        );

        if (!empty($sort)) {
            $sql .= " ORDER BY " . implode(", ", $sort);
        }

        return parent::aesirx_analytics_get_list($sql, $total_sql, $params, [], $bind);
    }
}
