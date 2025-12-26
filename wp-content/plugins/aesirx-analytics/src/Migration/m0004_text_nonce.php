<?php

global $wpdb;

$aesirx_analytics_freemium_sql = [];

// Prepare the SQL query to change the column type and default value
$aesirx_analytics_freemium_sql[] = "ALTER TABLE `{$wpdb->prefix}analytics_wallet` CHANGE `nonce` `nonce` VARCHAR(255) NULL DEFAULT NULL;";
