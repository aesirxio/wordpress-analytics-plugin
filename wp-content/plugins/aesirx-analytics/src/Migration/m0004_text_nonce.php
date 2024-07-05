<?php

global $wpdb;

$sql = [];

// Prepare the SQL query to change the column type and default value
$sql[] = "ALTER TABLE `{$wpdb->prefix}analytics_wallet` CHANGE `nonce` `nonce` VARCHAR(255) NULL DEFAULT NULL;";
