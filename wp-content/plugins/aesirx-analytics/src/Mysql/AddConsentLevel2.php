<?php

use AesirxAnalytics\AesirxAnalyticsMysqlHelper;
use WP_Error;

Class AesirX_Analytics_Add_Consent_Level2 extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        $web3idObj = parent::aesirx_analytics_decode_web3id($params['token']) ?? '';

        if (!$web3idObj || !isset($web3idObj['web3id'])) {
            return new WP_Error('validation_error', esc_html__('Invalid token', 'aesirx-analytics'));
        }

        $web3id = $web3idObj['web3id'];
    
        $visitor = parent::aesirx_analytics_find_visitor_by_uuid($params['visitor_uuid']);

        if (!$visitor) {
            return new WP_Error('validation_error', esc_html__('Visitor not found', 'aesirx-analytics'));
        }

        $found_consent = [];

        $consent_list = self::list_consent_level2($web3id, $visitor->domain, null);

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

                $datetime = date('Y-m-d H:i:s');
                parent::aesirx_analytics_add_consent($uuid, intval($consent), $datetime, $web3id);
            }

            $datetime = date('Y-m-d H:i:s');
            parent::aesirx_analytics_add_visitor_consent($params['visitor_uuid'], $uuid, null, $datetime);
        }

        return true;
    }

    function list_consent_level2($web3id, $domain, $consent) {
        global $wpdb;
        $table_consent = $wpdb->prefix . 'analytics_consent';
        $table_visitor_consent = $wpdb->prefix . 'analytics_visitor_consent';
        $table_visitors = $wpdb->prefix . 'analytics_visitors';
        $table_flows = $wpdb->prefix . 'analytics_flows';

        $dom = $domain ? "AND visitor.domain = %s" : "";
        $exp = !$expired ? "AND consent.expiration IS NULL" : "";

        try {
            // Fetch consents
            $sql = $wpdb->prepare(
                "SELECT consent.* 
                FROM {$table_consent} AS consent 
                LEFT JOIN {$table_visitor_consent} AS visitor_consent 
                ON consent.uuid = visitor_consent.consent_uuid 
                LEFT JOIN {$table_visitors} as visitor 
                ON visitor_consent.visitor_uuid = visitor.uuid 
                WHERE consent.wallet_uuid IS NULL $exp AND consent.web3id = %s $dom 
                GROUP BY consent.uuid", 
                $web3id, $domain
            );
            $consents = $wpdb->get_results($sql);

            // Fetch visitors
            $sql = $wpdb->prepare(
                "SELECT visitor.*, visitor_consent.consent_uuid 
                FROM {$table_visitors} AS visitor 
                LEFT JOIN {$table_visitor_consent} AS visitor_consent 
                ON visitor_consent.visitor_uuid = visitor.uuid 
                LEFT JOIN {$table_consent} AS consent 
                ON consent.uuid = visitor_consent.consent_uuid 
                WHERE consent.wallet_uuid IS NULL $exp AND consent.web3id = %s $dom", 
                $web3id, $domain
            );
            $visitors = $wpdb->get_results($sql);

            // Fetch flows
            $sql = $wpdb->prepare(
                "SELECT flows.* 
                FROM {$table_flows} AS flows 
                LEFT JOIN {$table_visitors} AS visitor 
                ON visitor.uuid = flows.visitor_uuid 
                LEFT JOIN {$table_visitor_consent} AS visitor_consent 
                ON visitor_consent.visitor_uuid = visitor.uuid 
                LEFT JOIN {$table_consent} AS consent 
                ON consent.uuid = visitor_consent.consent_uuid 
                WHERE consent.wallet_uuid IS NULL $exp AND consent.web3id = %s $dom 
                ORDER BY id", 
                $web3id, $domain
            );
            $flows = $wpdb->get_results($sql);

            return parent::aesirx_analytics_list_consent_common($consents, $visitors, $flows);
        } catch (Exception $e) {
            return new WP_Error('db_update_error', esc_html__('There was a problem querying the data in the database.', 'aesirx-analytics'), $e->getMessage());
        }
    }
}