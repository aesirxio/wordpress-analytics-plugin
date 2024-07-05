<?php

// namespace AesirxAnalytics\Mysql;

use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Get_Nonce extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        $validate_address = parent::aesirx_analytics_validate_address($params['address']);

        if (!$validate_address) {
            return new WP_Error('validation_error', esc_html__('Address is not valid', 'aesirx-analytics'));
        }

        $num = (string) rand(10000, 99999);

        if (is_null($params['text'])) {
            $num = "Please sign nonce $num issued by {$params['domain']} in " . date('Y-m-d H:i:s');
        } else {
            $text = $params['text'];
            if (strpos($text, '{nonce}') === false || strpos($text, '{domain}') === false || strpos($text, '{time}') === false) {
                return false;
            }

            $num = str_replace('{nonce}', $num, $text);
            $num = str_replace('{domain}', $params['domain'], $num);
            $num = str_replace('{time}', date('Y-m-d H:i:s'), $num);
        }

        $wallet = parent::aesirx_analytics_find_wallet($params['network'], $params['address']);

        if ($wallet) {
            parent::aesirx_analytics_update_nonce($params['network'], $params['address'], $num);
        } else {
            parent::aesirx_analytics_add_wallet(wp_generate_uuid4(), $params['network'], $params['address'], $num);
        }

        return ['nonce' => $num];
    }
}
