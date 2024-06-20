<?php

// namespace AesirxAnalytics\Mysql;

use AesirxAnalytics\MysqlHelper;

Class AesirX_Analytics_Get_Attribute_Value_Date extends MysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        global $wpdb;

        // let mut where_clause: Vec<String> = vec![];
        // let mut bind: Vec<String> = vec![];
        // add_filters(params, &mut where_clause, &mut bind)?;

        // add_attribute_filters(params, &mut where_clause, &mut bind);

        // let total_sql: Vec<String> = vec![
        //     "SELECT COUNT(DISTINCT #__analytics_event_attributes.name, DATE_FORMAT(#__analytics_events.start, '%Y-%m-%d')) as total
        //     "from `#__analytics_event_attributes`
        //     "left join `#__analytics_events` on #__analytics_event_attributes.event_uuid = #__analytics_events.uuid
        //     "left join `#__analytics_visitors` on #__analytics_visitors.uuid = #__analytics_events.visitor_uuid
        //     "WHERE
        //     where_clause.join(" AND "),
        // ];

        $sql =
            "SELECT
            #__analytics_event_attributes.name,
            DATE_FORMAT(#__analytics_events.start, '%Y-%m-%d') as date
            FROM #__analytics_event_attributes
            LEFT JOIN #__analytics_events ON #__analytics_event_attributes.event_uuid = #__analytics_events.uuid
            LEFT JOIN #__analytics_visitors ON #__analytics_visitors.uuid = #__analytics_events.visitor_uuid
            GROUP BY #__analytics_event_attributes.name, date";

        // let sort = add_sort(params, vec!["name", "date"], "date");

        // if !sort.is_empty() {
        //     sql.push("ORDER BY".to_string());
        //     sql.push(sort.join(","));
        // }

        // let list = self
        //     .get_list::<OutgoingMysqlAttributesWithDate>(sql, total_sql, bind.clone(), params)
        //     .await?;
        // let mut collection: Vec<OutgoingAttributesWithDate> = vec![];

        $sql = str_replace("#__", "wp_", $sql);

        $list = $wpdb->get_results($sql, ARRAY_A);

        $collection = [];

        // var_dump($list);

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

            $sql = str_replace("#__", "wp_", $sql);

            $secondArray = $wpdb->get_results($sql);

            foreach ($secondArray as $second) {
                // $key = new key($second->date, $second->name);
                $key_string = $second->date . '-' . $second->name; // Assuming a method to convert the object to a unique string key
            
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
            
                // Assuming a method to convert the string key back to the object
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
            // 'page' => $params->calcPage(),
            // 'pageSize' => $params->calcPageSize(),
            // 'totalPages' => 1, // Placeholder, calculate if needed
            // 'totalElements' => $wpdb->get_var($total_sql),
            'page' => 1,
            'pageSize' => 1,
            'totalPages' => 1, // Placeholder, calculate if needed
            'totalElements' => 1,
        ];

        return $list_response;
    }
}
