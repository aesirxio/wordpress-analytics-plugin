<?php

global $wpdb;

$sql = [];

// Create the analytics_conversion table
$sql[] = "
    CREATE TABLE `{$wpdb->prefix}analytics_conversion` (
        `id` int(10) UNSIGNED AUTO_INCREMENT UNIQUE KEY NOT NULL,
        `uuid` char(36) NOT NULL,
        `flow_uuid` char(36) NOT NULL,
        `order_id` varchar(255) DEFAULT NULL,
        `extension` varchar(255) NOT NULL,
        `revenue_total` INT(10) UNSIGNED NOT NULL,
        `revenue_subtotal` INT(10) UNSIGNED NOT NULL,
        `revenue_tax` INT(10) UNSIGNED NOT NULL,
        `revenue_discount` INT(10) UNSIGNED NOT NULL,
        `revenue_shipping` INT(10) UNSIGNED NOT NULL,
        `conversion_type` varchar(255) NOT NULL,
        UNIQUE KEY `idx_unique` (`extension`, `conversion_type`, `uuid`, `order_id`),
        UNIQUE KEY `uuid` (`uuid`)
    ) ENGINE=InnoDB;";

// Create the analytics_conversion_item table
$sql[] = "
    CREATE TABLE `{$wpdb->prefix}analytics_conversion_item` (
        `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        `conversion_uuid` char(36) NOT NULL,
        `sku` VARCHAR(255) NOT NULL,
        `name` VARCHAR(255) NOT NULL,
        `price` INT(10) UNSIGNED NOT NULL,
        `quantity` INT(10) UNSIGNED NOT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB;";

// Add foreign key constraint to analytics_conversion table
$sql[] = "
    ALTER TABLE `{$wpdb->prefix}analytics_conversion`
    ADD CONSTRAINT `{$wpdb->prefix}analytics_conversion_ibfk_1` FOREIGN KEY (`flow_uuid`)
    REFERENCES `{$wpdb->prefix}analytics_flows` (`uuid`) ON DELETE CASCADE ON UPDATE CASCADE;";

// Add foreign key constraint to analytics_conversion_item table
$sql[] = "
    ALTER TABLE `{$wpdb->prefix}analytics_conversion_item`
    ADD CONSTRAINT `{$wpdb->prefix}analytics_conversion_item_ibfk_1` FOREIGN KEY (`conversion_uuid`)
    REFERENCES `{$wpdb->prefix}analytics_conversion` (`uuid`) ON DELETE CASCADE ON UPDATE CASCADE;";
