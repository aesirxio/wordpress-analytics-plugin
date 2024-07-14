<?php

use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Close_Visitor_Event extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Validate required parameters
        if (!isset($params['request']['event_uuid']) || empty($params['request']['event_uuid'])) {
            return new WP_Error('missing_parameter', esc_html__('The event uuid parameter is required.', 'aesirx-analytics'), ['status' => 400]);
        }
        
        if (!isset($params['request']['visitor_uuid']) || empty($params['request']['visitor_uuid'])) {
            return new WP_Error('missing_parameter', esc_html__('The event uuid parameter is required.', 'aesirx-analytics'), ['status' => 400]);
        }

        $event_uuid   = sanitize_text_field($params['request']['event_uuid']);
        $visitor_uuid = sanitize_text_field($params['request']['visitor_uuid']);
        
        // Get the current date and time
        $now = gmdate('Y-m-d H:i:s');

        // Update the analytics events table
        $sql_update_event = $wpdb->prepare(
            "UPDATE {$wpdb->prefix}analytics_events SET end = %s WHERE uuid = %s AND visitor_uuid = %s",
            $now, $event_uuid, $visitor_uuid
        );
        $result_update_event = dbDelta($sql_update_event);

        // Check if the first update was successful
        if ($result_update_event === false) {
            return new WP_Error('db_update_error', esc_html__('Failed to update analytics events.', 'aesirx-analytics'));
        }

        // Find the event by UUID and visitor UUID
        $visitor_event = parent::aesirx_analytics_find_event_by_uuid($event_uuid, $visitor_uuid);

        if ($visitor_event === null) {
            return new WP_Error('db_query_error', esc_html__('Visitor event not found.', 'aesirx-analytics'));
        }

        // Update the analytics flows table
        $sql_update_flows = $wpdb->prepare(
            "UPDATE {$wpdb->prefix}analytics_flows SET end = %s WHERE uuid = %s",
            $now, $visitor_event->flow_uuid
        );
        $result_update_flows = dbDelta($sql_update_flows);

        // Check if the second update was successful
        if ($result_update_flows === false) {
            return new WP_Error('db_update_error', esc_html__('Failed to update analytics flows', 'aesirx-analytics'));
        }

        return true;
    }
}
