<?php

// namespace AesirxAnalyticsMysql;

use AesirxAnalytics\MysqlHelper;

Class AesirX_Analytics_Get_Total_Consent_Tier extends MysqlHelper
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
            CASE 
            WHEN visitor_consent.consent_uuid IS NULL THEN 1 
            WHEN consent.web3id IS NOT NULL AND consent.wallet_uuid IS NOT NULL THEN 4 
            WHEN consent.web3id IS NULL AND consent.wallet_uuid IS NOT NULL THEN 3 
            WHEN consent.web3id IS NOT NULL AND consent.wallet_uuid IS NULL THEN 2 
            ELSE 1 END AS tier 
            FROM `#__analytics_visitor_consent` AS visitor_consent 
            LEFT JOIN `#__analytics_visitors` AS visitors ON visitors.uuid = visitor_consent.visitor_uuid 
            LEFT JOIN `#__analytics_consent` AS consent ON consent.uuid = visitor_consent.consent_uuid 
            GROUP BY tier";

        // let sort = add_sort(params, vec!["tier", "total"], "tier");

        // if !sort.is_empty() {
        //     sql.push("ORDER BY".to_string());
        //     sql.push(sort.join(","));
        // }

        // let total_sql: Vec<String> = vec![
        //     "SELECT 
        //     COUNT(DISTINCT CASE 
        //     WHEN visitor_consent.consent_uuid IS NULL THEN 1 
        //     WHEN consent.web3id IS NOT NULL AND consent.wallet_uuid IS NOT NULL THEN 4 
        //     WHEN consent.web3id IS NULL AND consent.wallet_uuid IS NOT NULL THEN 3 
        //     WHEN consent.web3id IS NOT NULL AND consent.wallet_uuid IS NULL THEN 2 
        //     ELSE 1 END) AS total 
        //     FROM `#__analytics_visitor_consent` AS visitor_consent 
        //     LEFT JOIN `#__analytics_visitors` AS visitors ON visitors.uuid = visitor_consent.visitor_uuid 
        //     LEFT JOIN `#__analytics_consent` AS consent ON consent.uuid = visitor_consent.consent_uuid 
        //     WHERE 
        //     where_clause.join(" AND "),
        // ];

        return parent::get_list($sql, $total_sql, $params);
    }
}
