<?php

use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Add_Consent_Level3or4 extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        // Decode signature
        $decoded = base64_decode($params['signature'], true);
        if ($decoded === false) {
            throw new Exception("Error decoding signature");
        }
        
        // Assuming you have a function to validate the signature
        $validated = validate_signature($decoded); // Implement this function accordingly

        if (!$validated) {
            throw new Exception("Signature validation failed");
        }

        // Find visitor by UUID
        $visitor = find_visitor_by_uuid($params['visitor_uuid']);
        if (!$visitor) {
            throw new Exception("Visitor not found");
        }

        // Find wallet by network and wallet address
        $wallet = aesirx_analytics_find_wallet($params['network'], $params['wallet']);
        if (!$wallet) {
            throw new Exception("Wallet not found");
        }

        // Extract nonce from wallet
        $nonce = $wallet->nonce;
        if (!$nonce) {
            throw new Exception("Nonce not found");
        }

        // Validate network using extracted details
        validate_network(
            $params['network'],
            $params['wallet'],
            $nonce,
            $decoded,
            $params['jwt_payload'],
            $params['version']
        );

        // Extract web3id from jwt_payload
        $web3id = $params['jwt_payload']['web3id'] ?? null;

        // Fetch existing consents for level3 or level4
        $found_consent = [];
        $consent_list = list_consent_level3_or_level4(
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
                            throw new Exception("Previous consent still active");
                        }
                    }
                }

                // Insert found consents into the map
                $found_consent[$one_consent->consent] = $one_consent->uuid;
            }
        }

        // Process each consent in the request
        foreach ($params['consents'] as $consent) {
            // Determine UUID for consent
            $uuid = $found_consent[(int)$consent] ?? null;
            if (!$uuid) {
                $uuid = generate_uuid(); // Implement your own UUID generation logic
                add_consent($uuid, $wallet->uuid, $web3id, (int)$consent);
            }

            // Add visitor consent record
            aesirx_analytics_add_visitor_consent($uuid, $params['visitor_uuid']);
        }

        // Update nonce
        aesirx_analytics_update_nonce($params['network'], $params['wallet'], null);

        return true;
    }

    function list_consent_level3_or_level4() {
        global $wpdb;

        // Prepare SQL conditions based on input parameters
        $domain_condition = $domain ? "AND visitor.domain = %s" : "";
        $expired_condition = !$expired ? "AND consent.expiration IS NULL" : "";
        $web3id_condition = $web3id ? "AND consent.web3id = %s" : "AND consent.web3id IS NULL";

        // SQL query to fetch consents
        $consent_sql = "
            SELECT consent.*, wallet.address 
            FROM {$wpdb->prefix}analytics_consent AS consent
            LEFT JOIN {$wpdb->prefix}analytics_wallet AS wallet ON wallet.uuid = consent.wallet_uuid
            LEFT JOIN {$wpdb->prefix}analytics_visitor_consent AS visitor_consent ON consent.uuid = visitor_consent.consent_uuid
            LEFT JOIN {$wpdb->prefix}analytics_visitors AS visitor ON visitor_consent.visitor_uuid = visitor.uuid
            WHERE wallet.address = %s $expired_condition $web3id_condition $domain_condition
            GROUP BY consent.uuid";

        // Prepare and execute the consent query
        $consent_query = $wpdb->prepare($consent_sql, $wallet, $web3id, $domain);
        $consents = $wpdb->get_results($consent_query);

        // SQL query to fetch visitors
        $visitor_sql = "
            SELECT visitor.*, visitor_consent.consent_uuid
            FROM {$wpdb->prefix}analytics_visitors AS visitor
            LEFT JOIN {$wpdb->prefix}analytics_visitor_consent AS visitor_consent ON visitor_consent.visitor_uuid = visitor.uuid
            LEFT JOIN {$wpdb->prefix}analytics_consent AS consent ON consent.uuid = visitor_consent.consent_uuid
            LEFT JOIN {$wpdb->prefix}analytics_wallet AS wallet ON wallet.uuid = consent.wallet_uuid
            WHERE wallet.address = %s $expired_condition $web3id_condition $domain_condition";

        // Prepare and execute the visitor query
        $visitor_query = $wpdb->prepare($visitor_sql, $wallet, $web3id, $domain);
        $visitors = $wpdb->get_results($visitor_query);

        // SQL query to fetch flows
        $flow_sql = "
            SELECT flows.*
            FROM {$wpdb->prefix}analytics_flows AS flows
            LEFT JOIN {$wpdb->prefix}analytics_visitors AS visitor ON visitor.uuid = flows.visitor_uuid
            LEFT JOIN {$wpdb->prefix}analytics_visitor_consent AS visitor_consent ON visitor_consent.visitor_uuid = visitor.uuid
            LEFT JOIN {$wpdb->prefix}analytics_consent AS consent ON consent.uuid = visitor_consent.consent_uuid
            LEFT JOIN {$wpdb->prefix}analytics_wallet AS wallet ON wallet.uuid = consent.wallet_uuid
            WHERE wallet.address = %s $expired_condition $web3id_condition $domain_condition
            ORDER BY flows.id";

        // Prepare and execute the flow query
        $flow_query = $wpdb->prepare($flow_sql, $wallet, $web3id, $domain);
        $flows = $wpdb->get_results($flow_query);

        return parent::aesirx_analytics_list_consent_common($consents, $visitors, $flows);
    }
}