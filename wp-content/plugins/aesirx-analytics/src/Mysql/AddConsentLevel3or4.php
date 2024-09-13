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

        if (!$visitor || is_wp_error($visitor)) {
            return new WP_Error('validation_error', esc_html__('Visitor not found', 'aesirx-analytics'));
        }

        // Find wallet by network and wallet address
        $wallet = parent::aesirx_analytics_find_wallet($params['network'], $params['wallet']);
        
        if (!$wallet || is_wp_error($wallet)) {
            return new WP_Error('validation_error', esc_html__('Wallet not found', 'aesirx-analytics'));
        }

        // Extract nonce from wallet
        $nonce = $wallet->nonce;
        if (!$nonce) {
            return new WP_Error('validation_error', esc_html__('Wallet nonce not found', 'aesirx-analytics'));
        }

        // Validate network using extracted details
        $validate_nonce = parent::aesirx_analytics_validate_string($nonce, $params['wallet'], $params['request']['signature']);

        if (!$validate_nonce || is_wp_error($validate_nonce)) {
            return new WP_Error('validation_error', esc_html__('Nonce is not valid', 'aesirx-analytics'));
        }

        $web3id = null;

        if (isset($params['token']) && $params['token']) {
            $validate_contract = parent::aesirx_analytics_validate_contract($params['token']);

            if (!$validate_contract || is_wp_error($validate_contract)) {
                return new WP_Error('validation_error', esc_html__('Contract is not valid', 'aesirx-analytics'));
            }

            // Extract web3id from jwt_payload
            $web3idObj = parent::aesirx_analytics_decode_web3id($params['token']) ?? '';

            if (!$web3idObj || !isset($web3idObj['web3id'])) {
                return new WP_Error('validation_error', esc_html__('Invalid token', 'aesirx-analytics'));
            }

            $web3id = $web3idObj['web3id'];
        }

        // Fetch existing consents for level3 or level4
        $found_consent = [];
        $consent_list = self::aesirx_analytics_list_consent_level3_or_level4(
            $web3id,
            $params['wallet'],
            $visitor->domain,
            null
        );

        if (is_wp_error($consent_list)) {
            return $consent_list;
        }

        if (isset($consent_list->consents)) {
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
                parent::aesirx_analytics_add_consent($uuid, (int)$consent, gmdate('Y-m-d H:i:s'), $web3id, $wallet->uuid);
            }

            // Add visitor consent record
            parent::aesirx_analytics_add_visitor_consent($params['visitor_uuid'], $uuid, null, gmdate('Y-m-d H:i:s'), null, $params);
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

        // Prepare SQL conditions based on input parameters
        $domain_condition = $domain ? $wpdb->prepare(" AND visitor.domain = %s", $domain) : "";
        $expired_condition = !$expired ? "AND consent.expiration IS NULL" : "";
        $web3id_condition = $web3id ? $wpdb->prepare(" AND consent.web3id = %s", $web3id) : "AND consent.web3id IS NULL";

        try {

            // doing direct database calls to custom tables
            $consents = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->prepare(
                    "SELECT consent.*, wallet.address 
                    FROM {$wpdb->prefix}analytics_consent AS consent
                    LEFT JOIN {$wpdb->prefix}analytics_wallet AS wallet ON wallet.uuid = consent.wallet_uuid
                    LEFT JOIN {$wpdb->prefix}analytics_visitor_consent AS visitor_consent ON consent.uuid = visitor_consent.consent_uuid
                    LEFT JOIN {$wpdb->prefix}analytics_visitors AS visitor ON visitor_consent.visitor_uuid = visitor.uuid
                    WHERE wallet.address = %s %s %s %s
                    GROUP BY consent.uuid", 
                    $wallet, $expired_condition, $web3id_condition, $domain_condition
                )
            );

            // doing direct database calls to custom tables
            $visitors = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->prepare(
                    "SELECT visitor.*, visitor_consent.consent_uuid
                    FROM {$wpdb->prefix}analytics_visitors AS visitor
                    LEFT JOIN {$wpdb->prefix}analytics_visitor_consent AS visitor_consent ON visitor_consent.visitor_uuid = visitor.uuid
                    LEFT JOIN {$wpdb->prefix}analytics_consent AS consent ON consent.uuid = visitor_consent.consent_uuid
                    LEFT JOIN {$wpdb->prefix}analytics_wallet AS wallet ON wallet.uuid = consent.wallet_uuid
                    WHERE wallet.address = %s %s %s %s",
                    $wallet, $expired_condition, $web3id_condition, $domain_condition
                )
            );

            // doing direct database calls to custom tables
            $flows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->prepare(
                    "SELECT flows.*
                    FROM {$wpdb->prefix}analytics_flows AS flows
                    LEFT JOIN {$wpdb->prefix}analytics_visitors AS visitor ON visitor.uuid = flows.visitor_uuid
                    LEFT JOIN {$wpdb->prefix}analytics_visitor_consent AS visitor_consent ON visitor_consent.visitor_uuid = visitor.uuid
                    LEFT JOIN {$wpdb->prefix}analytics_consent AS consent ON consent.uuid = visitor_consent.consent_uuid
                    LEFT JOIN {$wpdb->prefix}analytics_wallet AS wallet ON wallet.uuid = consent.wallet_uuid
                    WHERE wallet.address = %s %s %s %s
                    ORDER BY flows.id",
                    $wallet, $expired_condition, $web3id_condition, $domain_condition
                )
            );

            return parent::aesirx_analytics_list_consent_common($consents, $visitors, $flows);
        } catch (Exception $e) {
            error_log("Query error: " . $e->getMessage());
            return new WP_Error('db_update_error', esc_html__('There was a problem querying the data in the database.', 'aesirx-analytics'), ['status' => 500]);
        }
    }
}