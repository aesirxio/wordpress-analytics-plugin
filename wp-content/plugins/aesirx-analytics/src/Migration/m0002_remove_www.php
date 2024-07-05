<?php

global $wpdb;

$sql = [];

// Prepare the query with placeholders
$sql[] = $wpdb->prepare(
    "UPDATE `{$wpdb->prefix}analytics_visitors` 
    SET domain = SUBSTRING(domain, LOCATE(%s, domain) + LENGTH(%s)) 
    WHERE domain LIKE %s;",
    'www.', 'www.', 'www.%'
);
