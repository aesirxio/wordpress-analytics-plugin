<?php

global $wpdb;

// Prepare the SQL query to change the column type and default value
$query = "ALTER TABLE `{$wpdb->prefix}analytics_wallet` CHANGE `nonce` `nonce` VARCHAR(255) NULL DEFAULT NULL";

// Execute the query
$wpdb->query($query);
