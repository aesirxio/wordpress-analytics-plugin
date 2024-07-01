<?php

global $wpdb;

// Add a primary key to the id column of the analytics_conversion table
$query_add_primary_key = "ALTER TABLE `{$wpdb->prefix}analytics_conversion` ADD PRIMARY KEY(`id`)";
$wpdb->query($query_add_primary_key);