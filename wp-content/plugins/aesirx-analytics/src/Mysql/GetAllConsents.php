<?php


use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Get_All_Consents extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        global $wpdb;

        $where_clause = ["COALESCE(consent.consent, visitor_consent.consent) = %d"];
        $bind = [1];

        parent::aesirx_analytics_add_consent_filters($params, $where_clause, $bind);

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
            LEFT JOIN #__analytics_wallet AS wallet ON wallet.uuid = consent.wallet_uuid 
            WHERE " . implode(" AND ", $where_clause);

        $total_sql =
            "SELECT COUNT(visitor_consent.uuid) AS total 
            FROM `#__analytics_visitor_consent` AS visitor_consent 
            LEFT JOIN `#__analytics_visitors` AS visitors ON visitors.uuid = visitor_consent.visitor_uuid 
            LEFT JOIN `#__analytics_consent` AS consent ON consent.uuid = visitor_consent.consent_uuid 
            LEFT JOIN #__analytics_wallet AS wallet ON wallet.uuid = consent.wallet_uuid 
            WHERE " . implode(" AND ", $where_clause);

        $sort = self::aesirx_analytics_add_sort(
            $params,
            [
                "datetime",
                "expiration",
                "consent",
                "tier",
                "web3id",
                "wallet",
            ],
            "datetime",
        );

        if (!empty($sort)) {
            $sql .= " ORDER BY " . implode(", ", $sort);
        }

        $list_response = parent::aesirx_analytics_get_list($sql, $total_sql, $params, [], $bind);

        if (is_wp_error($list_response)) {
            return $list_response;
        }

        $list = $list_response['collection'];

        $collection = [];

        foreach ($list as $one) {
            $one = (object) $one;
            $uuid = isset($one->uuid) ? $one->uuid : null;
            $wallet = isset($one->wallet_uuid) ? (object)[
                'uuid' => $one->wallet_uuid,
                'address' => $one->address,
                'network' => $one->network,
            ] : null;
            
            $collection[] = (object)[
                'uuid' => $uuid,
                'tier' => $one->tier,
                'web3id' => $one->web3id,
                'consent' => $one->consent,
                'datetime' => $one->datetime,
                'expiration' => isset($one->expiration) ? $one->expiration : null,
                'wallet' => $wallet,
            ];
        }

        return [
            'collection' => $collection,
            'page' => $list_response['page'],
            'page_size' => $list_response['page_size'],
            'total_pages' => $list_response['total_pages'],
            'total_elements' => $list_response['total_elements'],
        ];
    }
}
