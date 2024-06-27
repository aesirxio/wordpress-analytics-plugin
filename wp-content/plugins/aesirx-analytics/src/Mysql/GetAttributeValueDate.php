<?php


use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Get_Attribute_Value_Date extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        global $wpdb;

        $where_clause = [];

        self::aesirx_analytics_add_filters($params, $where_clause);

        // add_attribute_filters(params, &mut where_clause, &mut bind);

        $total_sql =
            "SELECT COUNT(DISTINCT #__analytics_event_attributes.name, DATE_FORMAT(#__analytics_events.start, '%Y-%m-%d')) as total
            from `#__analytics_event_attributes`
            left join `#__analytics_events` on #__analytics_event_attributes.event_uuid = #__analytics_events.uuid
            left join `#__analytics_visitors` on #__analytics_visitors.uuid = #__analytics_events.visitor_uuid
            WHERE " . implode(" AND ", $where_clause);

        $sql =
            "SELECT
            #__analytics_event_attributes.name,
            DATE_FORMAT(#__analytics_events.start, '%Y-%m-%d') as date
            FROM #__analytics_event_attributes
            LEFT JOIN #__analytics_events ON #__analytics_event_attributes.event_uuid = #__analytics_events.uuid
            LEFT JOIN #__analytics_visitors ON #__analytics_visitors.uuid = #__analytics_events.visitor_uuid 
            WHERE " . implode(" AND ", $where_clause) .
            " GROUP BY #__analytics_event_attributes.name, date";

        $sort = self::aesirx_analytics_add_sort($params, ["name", "date"], "date");

        if (!empty($sort)) {
            $sql .= " ORDER BY " . implode(", ", $sort);
        }

        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 20;
        $skip = ($page - 1) * $pageSize;

        $sql .= " LIMIT " . $skip . ", " . $pageSize;

        $total_elements = (int) $wpdb->get_var($total_sql);
        $total_pages = ceil($total_elements / $pageSize);

        $sql = str_replace("#__", $wpdb->prefix, $sql);
        $total_sql = str_replace("#__", $wpdb->prefix, $total_sql);

        $list = $wpdb->get_results($sql, ARRAY_A);

        $collection = [];

        if ($list) {
            $names = array_map(function($e) {
                return $e['name'];
            }, $list);

            $where_clause = [
                "#__analytics_event_attributes.name IN ('" . implode("', '", $names) . "')",
            ];

            $sql =
                "SELECT DATE_FORMAT(#__analytics_events.start, '%Y-%m-%d') as date, #__analytics_event_attributes.name, #__analytics_event_attributes.value, COUNT(#__analytics_event_attributes.id) as count
                from `#__analytics_event_attributes`
                left join `#__analytics_events` on #__analytics_event_attributes.event_uuid = #__analytics_events.uuid
                left join `#__analytics_visitors` on #__analytics_visitors.uuid = #__analytics_events.visitor_uuid
                GROUP BY #__analytics_event_attributes.name, #__analytics_event_attributes.value";

            $sql = str_replace("#__", $wpdb->prefix, $sql);

            $secondArray = $wpdb->get_results($sql);

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

        $list_response = [
            'collection' => $collection,
            'page' => (int) $page,
            'page_size' => (int) $pageSize,
            'total_pages' => $total_pages,
            'total_elements' => $total_elements,
        ];

        return $list_response;
    }
}
