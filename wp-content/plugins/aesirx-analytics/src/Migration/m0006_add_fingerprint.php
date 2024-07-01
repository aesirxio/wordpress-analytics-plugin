<?php

global $wpdb;

// Prepare and execute the query to drop the existing unique index 'fingerprint_idx'
$query_drop_index = "ALTER TABLE `{$wpdb->prefix}analytics_visitors` DROP INDEX `fingerprint_idx`";
$wpdb->query($query_drop_index);

// Prepare and execute the query to add a new unique index on the 'fingerprint' and 'domain' columns
$query_add_index = "ALTER TABLE `{$wpdb->prefix}analytics_visitors` ADD UNIQUE `fingerprint_domain_idx` (`fingerprint`, `domain`)";
$wpdb->query($query_add_index);
