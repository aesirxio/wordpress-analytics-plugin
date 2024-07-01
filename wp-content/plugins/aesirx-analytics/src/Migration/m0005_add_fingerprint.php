<?php

global $wpdb;

// Prepare and execute the query to add a new column 'fingerprint'
$query_add_column = "ALTER TABLE `{$wpdb->prefix}analytics_visitors` ADD `fingerprint` VARCHAR(255) NULL DEFAULT NULL FIRST";
$wpdb->query($query_add_column);

// Prepare and execute the query to add a unique index on the 'fingerprint' column
$query_add_index = "ALTER TABLE `{$wpdb->prefix}analytics_visitors` ADD UNIQUE `fingerprint_idx` (`fingerprint`)";
$wpdb->query($query_add_index);
