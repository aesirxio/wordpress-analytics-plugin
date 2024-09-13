<?php

use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Revoke_Consent_Level3or4 extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        global $wpdb;

        // Decode the signaturea
        $decoded = base64_decode($params['request']['signature']);
        if ($decoded === false) {
            return new WP_Error('invalid_signature', esc_html__('Invalid signature.', 'aesirx-analytics'));
        }

        // Find the wallet
        $network = sanitize_text_field($params['network']);
        $wallet = sanitize_text_field($params['wallet']);

        $wallet_row = parent::aesirx_analytics_find_wallet($network, $wallet);

        if (!$wallet_row || is_wp_error($wallet_row)) {
            return new WP_Error('wallet_not_found', esc_html__('Wallet not found.', 'aesirx-analytics'));
        }

        // Check for nonce
        $nonce = $wallet_row->nonce;
        if (!$nonce) {
            return new WP_Error('nonce_not_found', esc_html__('Nonce not found.', 'aesirx-analytics'));
        }

        // Validate network using extracted details
        $validate_nonce = parent::aesirx_analytics_validate_string($nonce, $params['wallet'], $params['request']['signature']);

        if (!$validate_nonce || is_wp_error($validate_nonce)) {
            return new WP_Error('validation_error', esc_html__('Nonce is not valid', 'aesirx-analytics'));
        }

        if (isset($params['token']) && $params['token']) {
            $validate_contract = parent::aesirx_analytics_validate_contract($params['token']);

            if (!$validate_contract || is_wp_error($validate_contract)) {
                return new WP_Error('validation_error', esc_html__('Contract is not valid', 'aesirx-analytics'));
            }
        }

        // Expire the consent
        $expiration = gmdate('Y-m-d H:i:s');
        $consent_uuid = sanitize_text_field($params['consent_uuid']);

        $result = parent::aesirx_analytics_expired_consent($consent_uuid, $expiration);

        if ($result === false || is_wp_error($result)) {
            return new WP_Error('update_failed', esc_html__('Failed to update consent expiration.', 'aesirx-analytics'));
        }

        // Update the nonce to None (NULL in this context)
        $result = parent::aesirx_analytics_update_nonce($network, $wallet, null);

        if ($result === false) {
            return new WP_Error('nonce_update_failed', esc_html__('Failed to update nonce.', 'aesirx-analytics'));
        }

        return true;
    }
}