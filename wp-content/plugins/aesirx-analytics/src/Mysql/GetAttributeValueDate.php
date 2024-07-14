<?php


use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Get_Attribute_Value_Date extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        global $wpdb;

        $where_clause = [];
        $bind = [];

        parent::aesirx_analytics_add_filters($params, $where_clause, $bind);
        parent::aesirx_analytics_add_attribute_filters($params, $where_clause, $bind);

        $total_sql =
            "SELECT COUNT(DISTINCT #__analytics_event_attributes.name, DATE_FORMAT(#__analytics_events.start, '%%Y-%%m-%%d')) as total
            from `#__analytics_event_attributes`
            left join `#__analytics_events` on #__analytics_event_attributes.event_uuid = #__analytics_events.uuid
            left join `#__analytics_visitors` on #__analytics_visitors.uuid = #__analytics_events.visitor_uuid
            WHERE " . implode(" AND ", $where_clause);

        $sql =
            "SELECT
            #__analytics_event_attributes.name,
            DATE_FORMAT(#__analytics_events.start, '%%Y-%%m-%%d') as date
            FROM #__analytics_event_attributes
            LEFT JOIN #__analytics_events ON #__analytics_event_attributes.event_uuid = #__analytics_events.uuid
            LEFT JOIN #__analytics_visitors ON #__analytics_visitors.uuid = #__analytics_events.visitor_uuid 
            WHERE " . implode(" AND ", $where_clause) .
            " GROUP BY #__analytics_event_attributes.name, date";

        $sort = self::aesirx_analytics_add_sort($params, ["name", "date"], "date");

        if (!empty($sort)) {
            $sql .= " ORDER BY " . implode(", ", $sort);
        }

        $list_response = parent::aesirx_analytics_get_list($sql, $total_sql, $params, [], $bind);

        if (is_wp_error($list_response)) {
            return $list_response;
        }
        
        $list = $list_response['collection'];

        $collection = [];

        if ($list) {
            $names = array_map(function($e) {
                return $e['name'];
            }, $list);

            // doing direct database calls to custom tables
            $secondArray = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->prepare(
                    "SELECT 
                    DATE_FORMAT( {$wpdb->prefix}analytics_events.start, '%%Y-%%m-%%d') as date,
                    {$wpdb->prefix}analytics_event_attributes.name, 
                    {$wpdb->prefix}analytics_event_attributes.value,
                    COUNT( {$wpdb->prefix}analytics_event_attributes.id) as count
                    from {$wpdb->prefix}analytics_event_attributes
                    left join {$wpdb->prefix}analytics_events'
                        on {$wpdb->prefix}analytics_event_attributes.event_uuid = {$wpdb->prefix}analytics_events.uuid
                    left join {$wpdb->prefix}analytics_visitors on {$wpdb->prefix}analytics_visitors.uuid = {$wpdb->prefix}analytics_events.visitor_uuid
                    WHERE {$wpdb->prefix}analytics_event_attributes.name IN (%s)" .
                    " GROUP BY {$wpdb->prefix}analytics_event_attributes.name,  {$wpdb->prefix}analytics_event_attributes.value",
                    "'" . implode("', '", $names) . "'"
                )
            );

            foreach ($secondArray as $second) {
                $key_string = $second->date . '-' . $second->name;
            
                if (!isset($hash_map[$key_string])) {
                    $sub_hash = [];
                    $sub_hash[$second->value] = $second->count;
                    $hash_map[$key_string] = $sub_hash;
                } else {
                    $hash_map[$key_string][$second->value] = $second->count;
                }
            }
            
            $collection = [];
            
            foreach ($hash_map as $key_string => $vals) {
                $vals_vec = [];
            
                foreach ($vals as $key_val => $val_val) {
                    $vals_vec[] = (object)[
                        'value' => $key_val,
                        'count' => $val_val,
                    ];
                }
            
                $key = explode('-', $key_string);
            
                $collection[] = (object)[
                    'date' => $key[0],
                    'name' => $key[1],
                    'values' => $vals_vec
                ];
            }
        }

        return [
            'collection' => $collection,
            'page' => $list_response['page'],
            'page_size' => $list_response['page_size'],
            'total_pages' => $list_response['total_pages'],
            'total_elements' => $list_response['total_elements'],
        ];
    }
}
