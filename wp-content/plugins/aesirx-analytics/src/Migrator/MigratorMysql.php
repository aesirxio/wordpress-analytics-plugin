<?php
namespace AesirxAnalytics\Migrator;

class MigratorMysql {

    public static function aesirx_analytics_create_migrator_table_query() {
        global $wpdb;

        $table_name = sanitize_text_field($wpdb->prefix . 'analytics_migrations');
        
        $query = "
            CREATE TABLE IF NOT EXISTS $table_name (
                id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
                app VARCHAR(384) NOT NULL,
                name VARCHAR(384) NOT NULL,
                applied_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE (app, name)
            )";
    
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($query);
    }

    public static function aesirx_analytics_fetch_rows() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'analytics_migrations';
    
        $results = $wpdb->get_results("
            SELECT name
            FROM $table_name
        ", ARRAY_A);
    
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
    
        // Sanitize the input parameter
        $name = sanitize_text_field($name);
    
        // Sanitize the table name
        $table_name = sanitize_text_field($wpdb->prefix . 'analytics_migrations');
    
        // Prepare the SQL query using sanitized parameters
        $query = $wpdb->prepare(
            "INSERT INTO $table_name (app, name) VALUES (%s, %s)",
            'main', // Static value
            $name   // Sanitized user input
        );
    
        // Execute the query
        $wpdb->query($query);
    }
}