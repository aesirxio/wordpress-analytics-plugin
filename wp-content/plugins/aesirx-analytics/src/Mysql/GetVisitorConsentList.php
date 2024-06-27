<?php

use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Get_Visitor_Consent_List extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        global $wpdb;

        $sql = "SELECT * FROM #__analytics_visitors WHERE uuid = '" . $params['uuid'] . "'";

        $sql = str_replace("#__", $wpdb->prefix, $sql);

        $visitor = $wpdb->get_row($sql);

        $sql = "SELECT * FROM #__analytics_flows WHERE visitor_uuid = " . $params['uuid'] . " ORDER BY id";

        $sql = str_replace("#__", $wpdb->prefix, $sql);

        $flows = $wpdb->get_results($sql);
        
        // handle expiration

        $sql = "SELECT `vc`.*, `c`.`web3id`, c.consent AS consent_from_consent, `w`.`network`, `w`.`address`,
            c.expiration as consent_expiration, c.datetime as consent_datetime
            FROM `#__analytics_visitor_consent` AS `vc`
            LEFT JOIN `#__analytics_consent` AS `c` ON `vc`.`consent_uuid` = `c`.`uuid`
            LEFT JOIN `#__analytics_wallet` AS `w` ON `c`.`wallet_uuid` = `w`.`uuid`
            WHERE `vc`.`visitor_uuid` = " . $params['uuid'] .
            " ORDER BY `vc`.`datetime`";

        $sql = str_replace("#__", $wpdb->prefix, $sql);

        $consents = $wpdb->get_results($sql);

        if ($visitor) {
            $res = [
                'uuid' => $visitor->uuid,
                'ip' => $visitor->ip,
                'user_agent' => $visitor->user_agent,
                'device' => $visitor->device,
                'browser_name' => $visitor->browser_name,
                'browser_version' => $visitor->browser_version,
                'domain' => $visitor->domain,
                'lang' => $visitor->lang,
                'visitor_flows' => [],
                'geo' => null,
                'visitor_consents' => [],
            ];

            if ($visitor->geo_created_at) {
                $res['geo'] = [
                    'country' => [
                        'name' => $visitor->country_name,
                        'code' => $visitor->country_code,
                    ],
                    'city' => $visitor->city,
                    'isp' => $visitor->isp,
                    'created_at' => $visitor->geo_created_at,
                ];
            }

            foreach ($flows as $flow) {
                $res['visitor_flows'][] = [
                    'uuid' => $flow->uuid,
                    'start' =>$flow->start,
                    'end' => $flow->end,
                    'multiple_events' => $flow->multiple_events,
                ];
            }

            foreach ($consents as $consent) {
                $res['visitor_consents'][] = [
                    'consent_uuid' => $consent->consent_uuid,
                    'consent' => $consent->consent_from_consent ?? $consent->consent ?? null,
                    'datetime' => $consent->consent_datetime ?? $consent->datetime ? $consent->consent_datetime ?? $consent->datetime : null,
                    'expiration' => $consent->consent_expiration ?? $consent->expiration ? $consent->consent_expiration ?? $consent->expiration : null,
                    'address' => $consent->address,
                    'network' => $consent->network,
                    'web3id' => $consent->web3id,
                ];
            }

            return $res;
        } else {
            return null;
        }
    }
}
