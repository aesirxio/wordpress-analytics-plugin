<?php

use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Add_Consent_Level3or4 extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        // Decode signature
        $decoded = base64_decode($params['request']['signature'], true);
        if ($decoded === false) {
            return new WP_Error('validation_error', esc_html__('Invalid signature', 'aesirx-analytics'));
        }

        // Find visitor by UUID
        $visitor = parent::aesirx_analytics_find_visitor_by_uuid($params['visitor_uuid']);

        if (!$visitor) {
            return new WP_Error('validation_error', esc_html__('Visitor not found', 'aesirx-analytics'));
        }

        // Find wallet by network and wallet address
        $wallet = parent::aesirx_analytics_find_wallet($params['network'], $params['wallet']);
        
        if (!$wallet) {
            return new WP_Error('validation_error', esc_html__('Wallet not found', 'aesirx-analytics'));
        }

        // Extract nonce from wallet
        $nonce = $wallet->nonce;
        if (!$nonce) {
            return new WP_Error('validation_error', esc_html__('Wallet nonce not found', 'aesirx-analytics'));
        }

        // Validate network using extracted details
        $validate_nonce = parent::aesirx_analytics_validate_string($nonce, $params['wallet'], $params['request']['signature']);

        if (!$validate_nonce) {
            return new WP_Error('validation_error', esc_html__('Nonce is not valid', 'aesirx-analytics'));
        }

        $validate_contract = parent::aesirx_analytics_validate_contract($params['token']);

        if (!$validate_contract) {
            return new WP_Error('validation_error', esc_html__('Contract is not valid', 'aesirx-analytics'));
        }

        // Extract web3id from jwt_payload
        $web3idObj = parent::aesirx_analytics_decode_web3id($params['token']) ?? '';

        if (!$web3idObj || !isset($web3idObj['web3id'])) {
            return new WP_Error('validation_error', esc_html__('Invalid token', 'aesirx-analytics'));
        }

        $web3id = $web3idObj['web3id'];

        // Fetch existing consents for level3 or level4
        $found_consent = [];
        $consent_list = self::aesirx_analytics_list_consent_level3_or_level4(
            $web3id,
            $params['wallet'],
            $visitor->domain,
            null
        );

        if ($consent_list) {
            foreach ($consent_list->consents as $one_consent) {
                // Check if consent is part of the current visitor UUID
                if (in_array($params['visitor_uuid'], array_column($one_consent->visitor, 'uuid'))) {
                    foreach ($params['consents'] as $consent) {
                        if ((int)$consent === $one_consent->consent) {
                            return new WP_Error('rejected', esc_html__("Previous consent still active", 'aesirx-analytics'));
                        }
                    }
                }

                // Insert found consents into the map
                $found_consent[$one_consent->consent] = $one_consent->uuid;
            }
        }

        // Process each consent in the request
        foreach ($params['request']['consent'] as $consent) {
            // Determine UUID for consent
            $uuid = $found_consent[(int)$consent] ?? null;
            if (!$uuid) {
                $uuid = wp_generate_uuid4();
                parent::aesirx_analytics_add_consent($uuid, (int)$consent, date('Y-m-d H:i:s'), $web3id, $wallet->uuid);
            }

            // Add visitor consent record
            parent::aesirx_analytics_add_visitor_consent($params['visitor_uuid'], $uuid, null, date('Y-m-d H:i:s'));
        }

        // Update nonce
        parent::aesirx_analytics_update_nonce($params['network'], $params['wallet'], null);

        return true;
    }

    function aesirx_analytics_list_consent_level3_or_level4($web3id, $wallet, $domain, $expired) {
        global $wpdb;

        $web3id = sanitize_text_field($web3id);
        $wallet = sanitize_text_field($wallet);
        $domain = sanitize_text_field($domain);

        $table_consent = $wpdb->prefix . 'analytics_consent';
        $table_visitor_consent = $wpdb->prefix . 'analytics_visitor_consent';
        $table_visitors = $wpdb->prefix . 'analytics_visitors';
        $table_wallet = $wpdb->prefix . 'analytics_wallet';
        $table_flows = $wpdb->prefix . 'analytics_flows';

        // Prepare SQL conditions based on input parameters
        $domain_condition = $domain ? "AND visitor.domain = %s" : "";
        $expired_condition = !$expired ? "AND consent.expiration IS NULL" : "";
        $web3id_condition = $web3id ? "AND consent.web3id = %s" : "AND consent.web3id IS NULL";

        try {
            // SQL query to fetch consents
            $consent_sql = "
            SELECT consent.*, wallet.address 
            FROM {$table_consent} AS consent
            LEFT JOIN {$table_wallet} AS wallet ON wallet.uuid = consent.wallet_uuid
            LEFT JOIN {$table_visitor_consent} AS visitor_consent ON consent.uuid = visitor_consent.consent_uuid
            LEFT JOIN {$table_visitors} AS visitor ON visitor_consent.visitor_uuid = visitor.uuid
            WHERE wallet.address = %s $expired_condition $web3id_condition $domain_condition
            GROUP BY consent.uuid";

            // Prepare and execute the consent query
            $consent_query = $wpdb->prepare($consent_sql, $wallet, $web3id, $domain);
            $consents = $wpdb->get_results($consent_query);

            // SQL query to fetch visitors
            $visitor_sql = "
                SELECT visitor.*, visitor_consent.consent_uuid
                FROM {$table_visitors} AS visitor
                LEFT JOIN {$table_visitor_consent} AS visitor_consent ON visitor_consent.visitor_uuid = visitor.uuid
                LEFT JOIN {$table_consent} AS consent ON consent.uuid = visitor_consent.consent_uuid
                LEFT JOIN {$table_wallet} AS wallet ON wallet.uuid = consent.wallet_uuid
                WHERE wallet.address = %s $expired_condition $web3id_condition $domain_condition";

            // Prepare and execute the visitor query
            $visitor_query = $wpdb->prepare($visitor_sql, $wallet, $web3id, $domain);
            $visitors = $wpdb->get_results($visitor_query);

            // SQL query to fetch flows
            $flow_sql = "
                SELECT flows.*
                FROM {$table_flows} AS flows
                LEFT JOIN {$table_visitors} AS visitor ON visitor.uuid = flows.visitor_uuid
                LEFT JOIN {$table_visitor_consent} AS visitor_consent ON visitor_consent.visitor_uuid = visitor.uuid
                LEFT JOIN {$table_consent} AS consent ON consent.uuid = visitor_consent.consent_uuid
                LEFT JOIN {$table_wallet} AS wallet ON wallet.uuid = consent.wallet_uuid
                WHERE wallet.address = %s $expired_condition $web3id_condition $domain_condition
                ORDER BY flows.id";

            // Prepare and execute the flow query
            $flow_query = $wpdb->prepare($flow_sql, $wallet, $web3id, $domain);
            $flows = $wpdb->get_results($flow_query);

            return parent::aesirx_analytics_list_consent_common($consents, $visitors, $flows);
        } catch (Exception $e) {
            error_log("Query error: " . $e->getMessage());
            return new WP_Error('db_update_error', esc_html__('There was a problem querying the data in the database.', 'aesirx-analytics'), ['status' => 500]);
        }
    }
}