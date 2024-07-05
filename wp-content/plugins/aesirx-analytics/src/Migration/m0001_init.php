<?php

global $wpdb;

$sql = [];

// Create analytics_events table
$sql[] = "
    CREATE TABLE `{$wpdb->prefix}analytics_events` (
        `uuid` char(36) NOT NULL,
        `flow_uuid` char(36) NOT NULL,
        `visitor_uuid` char(36) NOT NULL,
        `url` varchar(255) NOT NULL,
        `referer` varchar(255) DEFAULT NULL,
        `start` datetime NOT NULL,
        `end` datetime NOT NULL,
        `event_name` varchar(255) NOT NULL,
        `event_type` varchar(255) NOT NULL,
        PRIMARY KEY (`uuid`),
        KEY `visitor_uuid` (`visitor_uuid`),
        KEY `flow_uuid` (`flow_uuid`),
        INDEX `idx_start_end` (`start`, `end`)
    ) ENGINE=InnoDB;";

// Create analytics_event_attributes table
$sql[] = "
    CREATE TABLE `{$wpdb->prefix}analytics_event_attributes` (
        `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
        `event_uuid` char(36) NOT NULL,
        `name` varchar(255) NOT NULL,
        `value` varchar(255) NOT NULL,
        KEY `idx_uuid` (`event_uuid`)
    ) ENGINE=InnoDB;";

// Create analytics_flows table
$sql[] = "
    CREATE TABLE `{$wpdb->prefix}analytics_flows` (
        `id` INT(11) UNSIGNED AUTO_INCREMENT UNIQUE KEY NOT NULL,
        `uuid` char(36) NOT NULL,
        `visitor_uuid` char(36) NOT NULL,
        `start` datetime NOT NULL,
        `end` datetime NOT NULL,
        `multiple_events` TINYINT(1) DEFAULT 0,
        PRIMARY KEY (`uuid`),
        KEY `visitor_uuid` (`visitor_uuid`),
        INDEX `idx_start_end` (`start`, `end`)
    ) ENGINE=InnoDB;";

// Create analytics_visitors table
$sql[] = "
    CREATE TABLE `{$wpdb->prefix}analytics_visitors` (
        `uuid` char(36) NOT NULL,
        `ip` varchar(255) NOT NULL,
        `user_agent` varchar(255) NOT NULL,
        `device` varchar(255) NOT NULL,
        `browser_name` varchar(255) NOT NULL,
        `browser_version` varchar(255) NOT NULL,
        `domain` varchar(255) NOT NULL,
        `lang` varchar(255) NOT NULL,
        `country_code` varchar(255) DEFAULT NULL,
        `country_name` varchar(255) DEFAULT NULL,
        `city` varchar(225) DEFAULT NULL,
        `isp` varchar(255) DEFAULT NULL,
        `geo_created_at` datetime DEFAULT NULL,
        PRIMARY KEY (`uuid`),
        INDEX `idx_domain` (`domain`)
    ) ENGINE=InnoDB;";

// Add foreign key constraints for analytics_events table
$sql[] = "
    ALTER TABLE `{$wpdb->prefix}analytics_events`
    ADD CONSTRAINT `analytics_event_1` FOREIGN KEY (`visitor_uuid`) REFERENCES `{$wpdb->prefix}analytics_visitors` (`uuid`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `analytics_event_2` FOREIGN KEY (`flow_uuid`) REFERENCES `{$wpdb->prefix}analytics_flows` (`uuid`) ON DELETE CASCADE ON UPDATE CASCADE;";

// Add foreign key constraint for analytics_event_attributes table
$sql[] = "
    ALTER TABLE `{$wpdb->prefix}analytics_event_attributes`
    ADD CONSTRAINT `analytics_ev_attr_1` FOREIGN KEY (`event_uuid`) REFERENCES `{$wpdb->prefix}analytics_events` (`uuid`) ON DELETE CASCADE ON UPDATE CASCADE;";

// Add foreign key constraint for analytics_flows table
$sql[] = "
    ALTER TABLE `{$wpdb->prefix}analytics_flows`
    ADD CONSTRAINT `analytics_flow_1` FOREIGN KEY (`visitor_uuid`) REFERENCES `{$wpdb->prefix}analytics_visitors` (`uuid`) ON DELETE CASCADE ON UPDATE CASCADE;";