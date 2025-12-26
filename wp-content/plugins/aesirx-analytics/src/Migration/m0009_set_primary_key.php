<?php

global $wpdb;

$aesirx_analytics_freemium_sql = [];

// Add a primary key to the id column of the analytics_conversion table
$aesirx_analytics_freemium_sql[] = "ALTER TABLE `{$wpdb->prefix}analytics_conversion` ADD PRIMARY KEY(`id`);";