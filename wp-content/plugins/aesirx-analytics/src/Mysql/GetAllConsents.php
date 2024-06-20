<?php

// namespace AesirxAnalyticsMysql;

use AesirxAnalytics\MysqlHelper;

Class AesirX_Analytics_Get_All_Consents extends MysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        global $wpdb;

        // only need load 1 value of consent
        // let mut where_clause: Vec<String> =
        //     vec!["COALESCE(consent.consent, visitor_consent.consent) = 1".to_string()];
        // let mut bind: Vec<String> = vec![];

        // add_consent_filters(params, &mut where_clause, &mut bind)?;

        $sql =
            "SELECT 
            visitor_consent.consent_uuid AS uuid, 
            consent.web3id, 
            COALESCE(consent.consent, visitor_consent.consent) AS consent, 
            COALESCE(consent.datetime, visitor_consent.datetime) AS datetime, 
            COALESCE(consent.expiration, visitor_consent.expiration) AS expiration, 
            wallet.uuid AS wallet_uuid, 
            wallet.address AS address, 
            wallet.network AS network, 
            CASE 
            WHEN visitor_consent.consent_uuid IS NULL THEN 1 
            WHEN consent.web3id IS NOT NULL AND consent.wallet_uuid IS NOT NULL THEN 4 
            WHEN consent.web3id IS NULL AND consent.wallet_uuid IS NOT NULL THEN 3 
            WHEN consent.web3id IS NOT NULL AND consent.wallet_uuid IS NULL THEN 2 
            ELSE 1 END AS tier 
            FROM `#__analytics_visitor_consent` AS visitor_consent 
            LEFT JOIN `#__analytics_visitors` AS visitors ON visitors.uuid = visitor_consent.visitor_uuid 
            LEFT JOIN `#__analytics_consent` AS consent ON consent.uuid = visitor_consent.consent_uuid 
            LEFT JOIN #__analytics_wallet AS wallet ON wallet.uuid = consent.wallet_uuid";

        // let total_sql: Vec<String> = vec![
        //     "SELECT COUNT(visitor_consent.uuid) AS total 
        //     FROM `#__analytics_visitor_consent` AS visitor_consent 
        //     LEFT JOIN `#__analytics_visitors` AS visitors ON visitors.uuid = visitor_consent.visitor_uuid 
        //     LEFT JOIN `#__analytics_consent` AS consent ON consent.uuid = visitor_consent.consent_uuid 
        //     LEFT JOIN #__analytics_wallet AS wallet ON wallet.uuid = consent.wallet_uuid 
        //     WHERE ".to_string(),
        //     where_clause.join(" AND "),
        // ];

        // let mut new_list: Vec<OutgoingConsents> = vec![];

        // let sort = add_sort(
        //     params,
        //     vec![
        //         "datetime",
        //         "expiration",
        //         "consent",
        //         "tier",
        //         "web3id",
        //         "wallet",
        //     ],
        //     "datetime",
        // );

        // if !sort.is_empty() {
        //     sql.push("ORDER BY".to_string());
        //     sql.push(sort.join(","));
        // }

        // let list = self
        //     .get_list::<SqlOutgoingConsents>(sql, total_sql, bind, params)
        //     .await?;

        $sql = str_replace("#__", "wp_", $sql);

        $list = $wpdb->get_results($sql, ARRAY_A);

        $collection = [];

        // var_dump($list);

        // for one in list.collection.iter() {
        //     new_list.push(OutgoingConsents {
        //         uuid: match one.uuid.clone() {
        //             Some(some) => Some(Uuid::parse_str(some)?),
        //             None => None,
        //         },
        //         tier: one.tier,
        //         web3id: one.web3id.clone(),
        //         consent: one.consent,
        //         datetime: Utc.from_utc_datetime(&one.datetime),
        //         expiration: one.expiration.map(|some| Utc.from_utc_datetime(&some)),
        //         wallet: match one.wallet_uuid.clone() {
        //             None => None,
        //             Some(some) => Some(OutgoingConsentsWallet {
        //                 uuid: Uuid::parse_str(some)?,
        //                 address: one.address.clone().unwrap(),
        //                 network: one.network.clone().unwrap(),
        //             }),
        //         },
        //     });
        // }

        foreach ($list as $one) {
            $uuid = isset($one->uuid) ? Uuid::fromString($one->uuid) : null;
            $wallet = isset($one->wallet_uuid) ? (object)[
                'uuid' => Uuid::fromString($one->wallet_uuid),
                'address' => $one->address,
                'network' => $one->network,
            ] : null;
            
            $collection[] = (object)[
                'uuid' => $uuid,
                'tier' => $one->tier,
                'web3id' => $one->web3id,
                'consent' => $one->consent,
                'datetime' => new DateTime($one->datetime),
                'expiration' => isset($one->expiration) ? new DateTime($one->expiration) : null,
                'wallet' => $wallet,
            ];
        }

        $list_response = [
            'collection' => $collection,
            // 'page' => $params->calcPage(),
            // 'pageSize' => $params->calcPageSize(),
            // 'totalPages' => 1, // Placeholder, calculate if needed
            // 'totalElements' => $wpdb->get_var($total_sql),
            'page' => 1,
            'pageSize' => 1,
            'totalPages' => 1, // Placeholder, calculate if needed
            'totalElements' => 1,
        ];

        return $list_response;
    }
}
