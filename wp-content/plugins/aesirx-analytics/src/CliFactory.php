<?php

namespace AesirxAnalytics;

use AesirxAnalyticsLib\Cli\AesirxAnalyticsCli;
use AesirxAnalyticsLib\Cli\Env;

class CliFactory {
    protected static ?AesirxAnalyticsCli $instance = null;

    public static function getCli(): AesirxAnalyticsCli {

        if (!is_null(static::$instance))
        {
            return static::$instance;
        }

        global $wpdb, $table_prefix;

        $host     = DB_HOST;
        $port     = null;
        $hostData = $wpdb->parse_db_host( DB_HOST );

        if ( $hostData ) {
            list( $host, $dbPort ) = $hostData;

            if ( ! is_null( $dbPort ) ) {
                $port = $dbPort;
            }
        }

        static::$instance = new AesirxAnalyticsCli(
            new Env(
                $options['license'] ?? '',
                DB_USER,
                urlencode( DB_PASSWORD ),
                DB_NAME,
                $table_prefix,
                $host,
                $port
            ),
            WP_PLUGIN_DIR . '/aesirx-analytics/assets/analytics-cli'
        );

        return static::$instance;
    }
}