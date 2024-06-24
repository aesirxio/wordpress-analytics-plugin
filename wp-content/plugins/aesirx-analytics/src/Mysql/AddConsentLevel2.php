<?php

use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Add_Consent_Level2 extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        // decode jwt
        // list($web3id, _) = decode_web3id($params['jwt']);

        $web3id = '@bao';
    
        $visitor = find_visitor_by_uuid($params['visitor_uuid']);
        if (!$visitor) {
            return new WP_Error('validation_error', 'Visitor not found');
        }

        $found_consent = [];

        $consent_list = list_consent_level2($web3id, $visitor->domain, null);
        if ($consent_list) {
            foreach ($consent_list as $one_consent) {
                // Make sure this consent is part of visitor uuid we work on
                if (in_array($params['visitor_uuid'], array_column($one_consent->visitor, 'uuid'))) {
                    foreach ($params['consents'] as $consent) {
                        if (intval($consent) == $one_consent->consent) {
                            return new WP_Error('rejected', 'Previous consent still active');
                        }
                    }
                }
                $found_consent[$one_consent->consent] = $one_consent->uuid;
            }
        }

        foreach ($params['consents'] as $consent) {
            $uuid = $found_consent[intval($consent)] ?? null;
            if (!$uuid) {
                $uuid = wp_generate_uuid4();
                $datetime = new DateTime('now', new DateTimeZone('UTC'));
                add_consent($uuid, $web3id, intval($consent), $datetime);
            }

            $datetime = new DateTime('now', new DateTimeZone('UTC'));
            aesirx_analytics_add_visitor_consent($uuid, $params['visitor_uuid'], $datetime);
        }

        return true;
    }

    function list_consent_level2($web3id, $domain, $consent) {
        global $wpdb;

        // Generate SQL conditions based on the provided parameters
        $dom = $domain ? "AND visitor.domain = %s" : "";
        $exp = !$expired ? "AND consent.expiration IS NULL" : "";

        // Fetch consents
        $sql = $wpdb->prepare(
            "SELECT consent.* 
            FROM {$wpdb->prefix}analytics_consent AS consent 
            LEFT JOIN {$wpdb->prefix}analytics_visitor_consent AS visitor_consent 
            ON consent.uuid = visitor_consent.consent_uuid 
            LEFT JOIN {$wpdb->prefix}analytics_visitors as visitor 
            ON visitor_consent.visitor_uuid = visitor.uuid 
            WHERE consent.wallet_uuid IS NULL $exp AND consent.web3id = %s $dom 
            GROUP BY consent.uuid", 
            $web3id, $domain
        );
        $consents = $wpdb->get_results($sql);

        // Fetch visitors
        $sql = $wpdb->prepare(
            "SELECT visitor.*, visitor_consent.consent_uuid 
            FROM {$wpdb->prefix}analytics_visitors AS visitor 
            LEFT JOIN {$wpdb->prefix}analytics_visitor_consent AS visitor_consent 
            ON visitor_consent.visitor_uuid = visitor.uuid 
            LEFT JOIN {$wpdb->prefix}analytics_consent AS consent 
            ON consent.uuid = visitor_consent.consent_uuid 
            WHERE consent.wallet_uuid IS NULL $exp AND consent.web3id = %s $dom", 
            $web3id, $domain
        );
        $visitors = $wpdb->get_results($sql);

        // Fetch flows
        $sql = $wpdb->prepare(
            "SELECT flows.* 
            FROM {$wpdb->prefix}analytics_flows AS flows 
            LEFT JOIN {$wpdb->prefix}analytics_visitors AS visitor 
            ON visitor.uuid = flows.visitor_uuid 
            LEFT JOIN {$wpdb->prefix}analytics_visitor_consent AS visitor_consent 
            ON visitor_consent.visitor_uuid = visitor.uuid 
            LEFT JOIN {$wpdb->prefix}analytics_consent AS consent 
            ON consent.uuid = visitor_consent.consent_uuid 
            WHERE consent.wallet_uuid IS NULL $exp AND consent.web3id = %s $dom 
            ORDER BY id", 
            $web3id, $domain
        );
        $flows = $wpdb->get_results($sql);

        return parent::aesirx_analytics_list_consent_common($consents, $visitors, $flows);
    }
}