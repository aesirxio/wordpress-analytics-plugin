<?php

global $wpdb;

$sql = [];

// Create analytics_wallet table
$sql[] = "
    CREATE TABLE `{$wpdb->prefix}analytics_wallet` (
        `uuid` char(36) NOT NULL,
        `network` varchar(255) NOT NULL,
        `address` varchar(255) NOT NULL,
        `nonce` int DEFAULT NULL,
        PRIMARY KEY (`uuid`)
    ) ENGINE=InnoDB;";

// Create analytics_consent table
$sql[] = "
    CREATE TABLE `{$wpdb->prefix}analytics_consent` (
        `uuid` char(36) NOT NULL,
        `wallet_uuid` char(36) DEFAULT NULL,
        `web3id` varchar(255) DEFAULT NULL,
        `consent` int NOT NULL,
        `datetime` datetime NOT NULL,
        `expiration` datetime DEFAULT NULL,
        PRIMARY KEY (`uuid`),
        KEY `analytics_consent_1` (`wallet_uuid`),
        CONSTRAINT `analytics_consent_1` FOREIGN KEY (`wallet_uuid`) REFERENCES `{$wpdb->prefix}analytics_wallet` (`uuid`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB;";

// Create analytics_visitor_consent table
$sql[] = "
    CREATE TABLE `{$wpdb->prefix}analytics_visitor_consent` (
        `uuid` char(36) NOT NULL,
        `visitor_uuid` char(36) NOT NULL,
        `consent_uuid` char(36) DEFAULT NULL,
        `consent` int DEFAULT NULL,
        `datetime` datetime DEFAULT NULL,
        `expiration` datetime DEFAULT NULL,
        PRIMARY KEY (`uuid`),
        KEY `analytics_visitor_consent_1` (`visitor_uuid`),
        KEY `analytics_visitor_consent_2` (`consent_uuid`),
        CONSTRAINT `analytics_visitor_consent_1` FOREIGN KEY (`visitor_uuid`) REFERENCES `{$wpdb->prefix}analytics_visitors` (`uuid`) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT `analytics_visitor_consent_2` FOREIGN KEY (`consent_uuid`) REFERENCES `{$wpdb->prefix}analytics_consent` (`uuid`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB;";
