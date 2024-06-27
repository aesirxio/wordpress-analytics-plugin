<?php

use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Revoke_Consent_Level3or4 extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        global $wpdb;

        // Decode the signature
        $decoded = base64_decode($params['signature']);
        if ($decoded === false) {
            return new WP_Error('invalid_signature', __('Invalid signature.'));
        }

        // Find the wallet
        $network = sanitize_text_field($params['network']);
        $wallet = sanitize_text_field($params['wallet']);

        $wallet_row = parent::find_wallet($network, $wallet);

        if (!$wallet_row) {
            return new WP_Error('wallet_not_found', __('Wallet not found.'));
        }

        // Check for nonce
        $nonce = $wallet_row->nonce;
        if (!$nonce) {
            return new WP_Error('nonce_not_found', __('Nonce not found.'));
        }

        // Validate network (this is a placeholder function, you need to implement the actual validation logic)
        $is_valid = parent::aesirx_analytics_validate_network($network_factory, $network, $nonce, $wallet, $decoded, $params['jwt_payload'], $version);
        if (!$is_valid) {
            return new WP_Error('validation_failed', __('Network validation failed.'));
        }

        // Expire the consent
        $expiration = date('Y-m-d H:i:s'); // Get the current time in UTC
        $consent_uuid = sanitize_text_field($params['consent_uuid']);

        $result = parent::aesirx_analytics_expired_consent($consent_uuid, $expiration);

        if ($result === false) {
            return new WP_Error('update_failed', __('Failed to update consent expiration.'));
        }

        // Update the nonce to None (NULL in this context)
        $result = parent::update_nonce($network, $wallet, null);

        if ($result === false) {
            return new WP_Error('nonce_update_failed', __('Failed to update nonce.'));
        }

        return true;
    }
}