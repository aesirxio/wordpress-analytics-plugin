<?php

global $wpdb;

$aesirx_analytics_freemium_sql = [];

// Prepare and execute the query to add a new column 'fingerprint'
$aesirx_analytics_freemium_sql[] = "ALTER TABLE `{$wpdb->prefix}analytics_visitors` ADD `fingerprint` VARCHAR(255) NULL DEFAULT NULL FIRST;";

// Prepare and execute the query to add a unique index on the 'fingerprint' column
$aesirx_analytics_freemium_sql[] = "ALTER TABLE `{$wpdb->prefix}analytics_visitors` ADD UNIQUE `fingerprint_idx` (`fingerprint`);";
