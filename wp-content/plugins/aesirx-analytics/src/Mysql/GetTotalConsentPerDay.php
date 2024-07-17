<?php


use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Get_Total_Consent_Per_Day extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        $where_clause = [];
        $bind = [];

        parent::aesirx_analytics_add_consent_filters($params, $where_clause, $bind);

        $sql =
            "SELECT 
            ROUND(COUNT(visitor_consent.uuid) / 2) AS total,
            DATE_FORMAT(visitor_consent.datetime, '%%Y-%%m-%%d') as `date`
            FROM `#__analytics_visitor_consent` AS visitor_consent
            LEFT JOIN `#__analytics_visitors` AS visitors ON visitors.uuid = visitor_consent.visitor_uuid
            WHERE " . implode(" AND ", $where_clause) .
            " GROUP BY `date`";

        $total_sql =
            "SELECT
            COUNT(DISTINCT DATE_FORMAT(visitor_consent.datetime, '%%Y-%%m-%%d')) as total
            FROM `#__analytics_visitor_consent` AS visitor_consent
            LEFT JOIN `#__analytics_visitors` AS visitors ON visitors.uuid = visitor_consent.visitor_uuid
            WHERE " . implode(" AND ", $where_clause);

        $sort = self::aesirx_analytics_add_sort($params, ["date", "total"], "date");

        if (!empty($sort)) {
            $sql .= " ORDER BY " . implode(", ", $sort);
        }

        return parent::aesirx_analytics_get_list($sql, $total_sql, $params, [], $bind);
    }
}
