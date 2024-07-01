<?php


use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Get_Ip_List_Without_geo extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = []) {
        global $wpdb;

        $sql       = "SELECT distinct ip FROM " . $wpdb->prefix . "analytics_visitors WHERE geo_created_at IS NULL";
        $total_sql = "SELECT count(distinct ip) as total FROM " . $wpdb->prefix . "analytics_visitors WHERE geo_created_at IS NULL";

        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 20;
        $skip = ($page - 1) * $pageSize;

        $sql .= " LIMIT " . $skip . ", " . $pageSize;

        $total_elements = (int) $wpdb->get_var($total_sql);
        $total_pages = ceil($total_elements / $pageSize);
        
        $list = parent::aesirx_analytics_get_list($sql, $total_sql, $params);

        $ips = [];
        
        foreach ($list->collection as $one) {
            $ips[] = $one->ip;
        }

        $list_response = [
            'collection' => $ips,
            'page' => (int) $page,
            'page_size' => (int) $pageSize,
            'total_pages' => $total_pages,
            'total_elements' => $total_elements,
        ];

        return $list_response;
    }
}
