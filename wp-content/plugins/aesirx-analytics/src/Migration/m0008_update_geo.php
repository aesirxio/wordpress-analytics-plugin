<?php

global $wpdb;

// Add a new column 'region' to the analytics_visitors table
$query_add_column = "ALTER TABLE `{$wpdb->prefix}analytics_visitors` ADD `region` VARCHAR(255) NULL DEFAULT NULL";
$wpdb->query($query_add_column);

// Update geo_created_at to NULL where city is not empty and is not NULL
$query_update = "UPDATE `{$wpdb->prefix}analytics_visitors` SET geo_created_at = NULL WHERE city != '' AND city IS NOT NULL";
$wpdb->query($query_update);
