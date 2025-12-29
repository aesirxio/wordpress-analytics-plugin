<?php

global $wpdb;

$aesirx_analytics_freemium_sql = [];

// Prepare and execute the query to drop the existing unique index 'fingerprint_idx'
$aesirx_analytics_freemium_sql[] = "ALTER TABLE `{$wpdb->prefix}analytics_visitors` DROP INDEX `fingerprint_idx`;";

// Prepare and execute the query to add a new unique index on the 'fingerprint' and 'domain' columns
$aesirx_analytics_freemium_sql[] = "ALTER TABLE `{$wpdb->prefix}analytics_visitors` ADD UNIQUE `fingerprint_domain_idx` (`fingerprint`, `domain`);";
