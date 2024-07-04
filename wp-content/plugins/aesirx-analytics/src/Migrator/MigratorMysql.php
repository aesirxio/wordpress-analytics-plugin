<?php
namespace AesirxAnalytics\Migrator;

class MigratorMysql {

    public static function aesirx_analytics_create_migrator_table_query() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $table_name = $wpdb->prefix . 'analytics_migrations';
        
        $query = "
            CREATE TABLE IF NOT EXISTS $table_name (
                id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
                app VARCHAR(384) NOT NULL,
                name VARCHAR(384) NOT NULL,
                applied_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE (app, name)
            ) ENGINE=InnoDB $charset_collate";
    
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
    
        return $results;
    }

    public static function aesirx_analytics_add_migration_query($name) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'analytics_migrations';
        $query = $wpdb->prepare("INSERT INTO $table_name (app, name) VALUES (%s, %s)", array('main', $name));
        return $query;
    }
}