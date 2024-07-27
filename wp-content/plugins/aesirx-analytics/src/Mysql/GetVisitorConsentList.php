<?php

use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Get_Visitor_Consent_List extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        global $wpdb;

        $uuid = sanitize_text_field($params['uuid']);

        // doing direct database calls to custom tables
        $visitor = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare("SELECT * FROM {$wpdb->prefix}analytics_visitors WHERE uuid = %s", $uuid)
        );

        // doing direct database calls to custom tables
        $flows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare("SELECT * FROM {$wpdb->prefix}analytics_flows WHERE visitor_uuid = %s ORDER BY id", $uuid)
        );

        $exp = '';

        // handle expiration
        if (!isset($params['expired']) || is_null($params['expired']) || !$params['expired']) {
            $exp = $wpdb->prepare(" AND (`vc`.`expiration` >= %s OR `vc`.`expiration` IS NULL)
                    AND IF (c.uuid IS NULL, true, c.expiration IS NULL)", gmdate('Y-m-d H:i:s'));

            // doing direct database calls to custom tables
            $consents = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->prepare(
                    "SELECT vc.*, c.web3id, c.consent AS consent_from_consent, w.network, w.address,
                    c.expiration as consent_expiration, c.datetime as consent_datetime
                    FROM {$wpdb->prefix}analytics_visitor_consent AS vc
                    LEFT JOIN {$wpdb->prefix}analytics_consent AS c ON vc.consent_uuid = c.uuid
                    LEFT JOIN {$wpdb->prefix}analytics_wallet AS w ON c.wallet_uuid = w.uuid
                    WHERE vc.visitor_uuid = %s ORDER BY vc.datetime 
                    AND (`vc`.`expiration` >= %s OR `vc`.`expiration` IS NULL)
                    AND IF (c.uuid IS NULL, true, c.expiration IS NULL)",
                    sanitize_text_field($params['uuid']), gmdate('Y-m-d H:i:s')
                )
            );
        } else {
            // doing direct database calls to custom tables
            $consents = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->prepare(
                    "SELECT vc.*, c.web3id, c.consent AS consent_from_consent, w.network, w.address,
                    c.expiration as consent_expiration, c.datetime as consent_datetime
                    FROM {$wpdb->prefix}analytics_visitor_consent AS vc
                    LEFT JOIN {$wpdb->prefix}analytics_consent AS c ON vc.consent_uuid = c.uuid
                    LEFT JOIN {$wpdb->prefix}analytics_wallet AS w ON c.wallet_uuid = w.uuid
                    WHERE vc.visitor_uuid = %s ORDER BY vc.datetime",
                    sanitize_text_field($params['uuid'])
                )
            );
        }

        if ($wpdb->last_error) {
            error_log("Query error: " . $wpdb->last_error);
            return new WP_Error('db_query_error', esc_html__('There was a problem with the database query.', 'aesirx-analytics'), ['status' => 500]);
        }

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
