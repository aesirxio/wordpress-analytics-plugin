<?php

global $wpdb;

$aesirx_analytics_freemium_sql = [];

// Prepare the query with placeholders
$aesirx_analytics_freemium_sql[] = $wpdb->prepare(
    "UPDATE `{$wpdb->prefix}analytics_visitors` 
    SET domain = SUBSTRING(domain, LOCATE(%s, domain) + LENGTH(%s)) 
    WHERE domain LIKE %s;",
    'www.', 'www.', 'www.%'
);
