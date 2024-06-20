<?php

// namespace AesirxAnalytics\Mysql;

use AesirxAnalytics\MysqlHelper;

Class AesirX_Analytics_Get_Conversion_Product extends MysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        // let mut where_clause: Vec<String> = vec![];
        // let mut bind: Vec<String> = vec![];

        // add_conversion_filters(params, &mut where_clause, &mut bind)?;

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
            GROUP BY name, sku, extension";

        // let total_sql: Vec<String> = vec![
        //     "SELECT
        //     "COUNT(DISTINCT name, sku, extension) as total
        //     "from `#__analytics_conversion`
        //     "left join `#__analytics_conversion_item` on #__analytics_conversion.uuid = #__analytics_conversion_item.conversion_uuid
        //     "left join `#__analytics_flows` on #__analytics_conversion.flow_uuid = #__analytics_flows.uuid
        //     "WHERE
        //     where_clause.join(" AND "),
        // ];

        // let sort = add_sort(
        //     params,
        //     vec![
        //         "product",
        //         "sku",
        //         "extension",
        //         "quantity",
        //         "items_sold",
        //         "product_revenue",
        //         "avg_price",
        //         "avg_quantity",
        //     ],
        //     "quantity",
        // );

        // if !sort.is_empty() {
        //     sql.push("ORDER BY".to_string());
        //     sql.push(sort.join(","));
        // }

        return parent::get_list($sql, $total_sql, $params);
    }
}
