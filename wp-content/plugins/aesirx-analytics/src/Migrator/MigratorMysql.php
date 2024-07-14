<?php
namespace AesirxAnalytics\Migrator;

class MigratorMysql {

    public static function aesirx_analytics_create_migrator_table_query() {
        global $wpdb;

        $table_name = sanitize_text_field($wpdb->prefix . 'analytics_migrations');
    
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta(
            "CREATE TABLE IF NOT EXISTS $table_name (
            id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
            app VARCHAR(384) NOT NULL,
            name VARCHAR(384) NOT NULL,
            applied_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (app, name))"
        );
    }

    public static function aesirx_analytics_fetch_rows() {
        global $wpdb;
    
        // doing direct database calls to custom tables
        $results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            "SELECT name FROM {$wpdb->prefix}analytics_migrations",
            ARRAY_A
        );

        // Validate and sanitize results
        $validated_results = array();
        if (!empty($results)) {
            foreach ($results as $row) {
                if (isset($row['name'])) {
                    // Sanitize the name field
                    $name = sanitize_text_field($row['name']);
                    if (!empty($name) && is_string($name)) {
                        $validated_results[] = array('name' => $name);
                    }
                }
            }
        }

        return $validated_results;
    }

    public static function aesirx_analytics_add_migration_query($name) {
        global $wpdb;
    
        $data = array(
            'app' => 'main', // Static value
            'name' => sanitize_text_field($name), // Sanitized user input
        );
        
        // Execute the insert
        // doing direct database calls to custom tables
        $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $wpdb->prefix . 'analytics_migrations',
            $data,
            array('%s', '%s') // Data types for 'app' and 'name'
        );
    }
}