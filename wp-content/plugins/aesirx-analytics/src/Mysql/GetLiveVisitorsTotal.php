<?php

use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Get_Live_Visitors_Total extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        global $wpdb;

        $where_clause = [
            "#__analytics_flows.start = #__analytics_flows.end",
            "#__analytics_flows.start >= NOW() - INTERVAL 30 MINUTE",
            "#__analytics_visitors.device != 'bot'"
        ];
        $bind = [];

        unset($params["filter"]["start"]);
        unset($params["filter"]["end"]);

        parent::aesirx_analytics_add_filters($params, $where_clause, $bind);

        $sql =
            "SELECT COUNT(DISTINCT `#__analytics_flows`.`visitor_uuid`) as total
            from `#__analytics_flows`
            left join `#__analytics_visitors` on `#__analytics_visitors`.`uuid` = `#__analytics_flows`.`visitor_uuid`
            WHERE " . implode(" AND ", $where_clause) . 
            " GROUP BY `#__analytics_flows`.`visitor_uuid`";

        $sql = str_replace("#__", $wpdb->prefix, $sql);

        // used placeholders and $wpdb->prepare() in variable $sql
        // doing direct database calls to custom tables
        $total = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare($sql, $bind) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        );
        
        return [
            "total" => $total
        ];
    }
}
