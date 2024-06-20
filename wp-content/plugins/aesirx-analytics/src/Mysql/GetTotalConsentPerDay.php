<?php

// namespace AesirxAnalytics\Mysql;

use AesirxAnalytics\MysqlHelper;

Class AesirX_Analytics_Get_Total_Consent_Per_Day extends MysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        // let mut where_clause: Vec<String> = vec![];
        // let mut bind: Vec<String> = vec![];

        // add_consent_filters(params, &mut where_clause, &mut bind)?;

        // only need load 1 value of consent
        $sql =
            "SELECT 
            ROUND(COUNT(visitor_consent.uuid) / 2) AS total,
            DATE_FORMAT(visitor_consent.datetime, '%Y-%m-%d') as `date`
            FROM `#__analytics_visitor_consent` AS visitor_consent
            LEFT JOIN `#__analytics_visitors` AS visitors ON visitors.uuid = visitor_consent.visitor_uuid
            GROUP BY `date`";

        // let sort = add_sort(params, vec!["date", "total"], "date");

        // if !sort.is_empty() {
        //     sql.push("ORDER BY".to_string());
        //     sql.push(sort.join(","));
        // }

        // let total_sql: Vec<String> = vec![
        //     "SELECT \
        //     COUNT(DISTINCT DATE_FORMAT(visitor_consent.datetime, '%Y-%m-%d')) as total \
        //     FROM `#__analytics_visitor_consent` AS visitor_consent \
        //     LEFT JOIN `#__analytics_visitors` AS visitors ON visitors.uuid = visitor_consent.visitor_uuid \
        //     WHERE 
        //     where_clause.join(" AND "),
        // ];

        return parent::get_list($sql, $total_sql, $params);
    }
}
