<?php

use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Add_Consent_Level2 extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        $web3idObj = parent::aesirx_analytics_decode_web3id($params['token']) ?? '';

        if (is_wp_error($web3idObj)) {
            return $web3idObj;
        }

        if (!$web3idObj || !isset($web3idObj['web3id'])) {
            return new WP_Error('validation_error', esc_html__('Invalid token', 'aesirx-analytics'));
        }

        $web3id = $web3idObj['web3id'];
    
        $visitor = parent::aesirx_analytics_find_visitor_by_uuid($params['visitor_uuid']);

        if (!$visitor || is_wp_error($visitor)) {
            return new WP_Error('validation_error', esc_html__('Visitor not found', 'aesirx-analytics'));
        }

        $found_consent = [];

        $consent_list = self::list_consent_level2($web3id, $visitor->domain, null);

        if (is_wp_error($consent_list)) {
            return $consent_list;
        }

        if ($consent_list) {
            foreach ($consent_list as $one_consent) {
                // Make sure this consent is part of visitor uuid we work on
                if (in_array($params['visitor_uuid'], array_column($one_consent->visitor, 'uuid'))) {
                    foreach ($params['consents'] as $consent) {
                        if (intval($consent) == $one_consent->consent) {
                            return new WP_Error('rejected', esc_html__('Previous consent still active', 'aesirx-analytics'));
                        }
                    }
                }
                $found_consent[$one_consent->consent] = $one_consent->uuid;
            }
        }

        foreach ($params['request']['consent'] as $consent) {
            $uuid = $found_consent[intval($consent)] ?? null;

            if (!$uuid) {
                $uuid = wp_generate_uuid4();

                $datetime = gmdate('Y-m-d H:i:s');
                parent::aesirx_analytics_add_consent($uuid, intval($consent), $datetime, $web3id);
            }

            $datetime = gmdate('Y-m-d H:i:s');
            parent::aesirx_analytics_add_visitor_consent($params['visitor_uuid'], $uuid, null, $datetime, null, $params);
        }

        return true;
    }

    function list_consent_level2($web3id, $domain, $consent) {
        global $wpdb;

        $dom = $domain ? $wpdb->prepare("AND visitor.domain = %s", sanitize_text_field($domain)) : "";
        $exp = !$expired ? "AND consent.expiration IS NULL" : "";

        try {
            // Fetch consents
            // doing direct database calls to custom tables
            $consents = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->prepare(
                    "SELECT consent.* 
                    FROM {$wpdb->prefix}analytics_consent AS consent 
                    LEFT JOIN {$wpdb->prefix}analytics_visitor_consent AS visitor_consent 
                    ON consent.uuid = visitor_consent.consent_uuid 
                    LEFT JOIN {$wpdb->prefix}analytics_visitors as visitor 
                    ON visitor_consent.visitor_uuid = visitor.uuid 
                    WHERE consent.wallet_uuid IS NULL %s AND consent.web3id = %s %s 
                    GROUP BY consent.uuid", 
                    $exp, sanitize_text_field($web3id), $dom
                )
            );

            // Fetch visitors
            // doing direct database calls to custom tables
            $visitors = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->prepare(
                    "SELECT visitor.*, visitor_consent.consent_uuid 
                    FROM {$wpdb->prefix}analytics_visitors AS visitor 
                    LEFT JOIN {$wpdb->prefix}analytics_visitor_consent AS visitor_consent 
                    ON visitor_consent.visitor_uuid = visitor.uuid 
                    LEFT JOIN {$wpdb->prefix}analytics_consent AS consent 
                    ON consent.uuid = visitor_consent.consent_uuid 
                    WHERE consent.wallet_uuid IS NULL %s AND consent.web3id = %s %s", 
                    $exp, sanitize_text_field($web3id), $dom
                )
            );

            // Fetch flows
            // doing direct database calls to custom tables
            $flows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->prepare(
                    "SELECT flows.* 
                    FROM {$wpdb->prefix}analytics_flows AS flows 
                    LEFT JOIN {$wpdb->prefix}analytics_visitors AS visitor 
                    ON visitor.uuid = flows.visitor_uuid 
                    LEFT JOIN {$wpdb->prefix}analytics_visitor_consent AS visitor_consent 
                    ON visitor_consent.visitor_uuid = visitor.uuid 
                    LEFT JOIN {$wpdb->prefix}analytics_consent AS consent 
                    ON consent.uuid = visitor_consent.consent_uuid 
                    WHERE consent.wallet_uuid IS NULL %s AND consent.web3id = %s %s 
                    ORDER BY id", 
                    $exp, sanitize_text_field($web3id), $dom
                )
            );

            return parent::aesirx_analytics_list_consent_common($consents, $visitors, $flows);
        } catch (Exception $e) {
            error_log("Query error: " . $e->getMessage());
            return new WP_Error('db_update_error', esc_html__('There was a problem querying the data in the database.', 'aesirx-analytics'), ['status' => 500]);
        }
    }
}