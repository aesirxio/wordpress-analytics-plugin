<?php

global $wpdb;

$sql = [];

// Add a primary key to the id column of the analytics_conversion table
$sql[] = "ALTER TABLE `{$wpdb->prefix}analytics_conversion` ADD PRIMARY KEY(`id`);";