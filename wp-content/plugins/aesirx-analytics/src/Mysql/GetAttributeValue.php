<?php

// namespace AesirxAnalytics\Mysql;

use AesirxAnalytics\MysqlHelper;

Class AesirX_Analytics_Get_Attribute_Value extends MysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        global $wpdb;

        // let mut where_clause: Vec<String> = vec![];
        // let mut bind: Vec<String> = vec![];
        // add_filters(params, &mut where_clause, &mut bind)?;

        // add_attribute_filters(params, &mut where_clause, &mut bind);

        // let total_sql: Vec<String> = vec![
        //     "SELECT COUNT(DISTINCT #__analytics_event_attributes.name) as total
        //     "from `#__analytics_event_attributes`
        //     "left join `#__analytics_events` on #__analytics_event_attributes.event_uuid = #__analytics_events.uuid
        //     "left join `#__analytics_visitors` on #__analytics_visitors.uuid = #__analytics_events.visitor_uuid
        //     "WHERE
        //     where_clause.join(" AND "),
        // ];

        $sql =
            "SELECT DISTINCT #__analytics_event_attributes.name
            from #__analytics_event_attributes
            left join #__analytics_events on #__analytics_event_attributes.event_uuid = #__analytics_events.uuid
            left join #__analytics_visitors on #__analytics_visitors.uuid = #__analytics_events.visitor_uuid";

        // let sort = add_sort(params, vec!["name"], "name");

        // if !sort.is_empty() {
        //     sql.push("ORDER BY".to_string());
        //     sql.push(sort.join(","));
        // }

        // let list = self
        //     .get_list::<OutgoingMysqlAttributes>(sql, total_sql, bind.clone(), params)
        //     .await?;
        // let mut collection: Vec<OutgoingAttributesDetail> = vec![];

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
                "SELECT #__analytics_event_attributes.name, #__analytics_event_attributes.value, COUNT(#__analytics_event_attributes.id) as count,
                coalesce(COUNT(DISTINCT (#__analytics_events.visitor_uuid)), 0) as number_of_visitors,
                coalesce(COUNT(#__analytics_events.visitor_uuid), 0) as total_number_of_visitors,
                COUNT(#__analytics_events.uuid) as number_of_page_views,
                COUNT(DISTINCT (#__analytics_events.url)) AS number_of_unique_page_views,
                coalesce(SUM(TIMESTAMPDIFF(SECOND, #__analytics_events.start, #__analytics_events.end)) / count(distinct #__analytics_visitors.uuid), 0) DIV 1 as average_session_duration,
                coalesce((COUNT(#__analytics_events.uuid) / COUNT(DISTINCT (#__analytics_events.flow_uuid))), 0) DIV 1 as average_number_of_pages_per_session,
                coalesce((count(DISTINCT CASE WHEN #__analytics_flows.multiple_events = 0 THEN #__analytics_flows.uuid END) * 100) / count(DISTINCT (#__analytics_flows.uuid)), 0) DIV 1 as bounce_rate
                from `#__analytics_event_attributes`
                left join `#__analytics_events` on #__analytics_event_attributes.event_uuid = #__analytics_events.uuid
                left join `#__analytics_visitors` on #__analytics_visitors.uuid = #__analytics_events.visitor_uuid
                left join `#__analytics_flows` on #__analytics_flows.uuid = #__analytics_events.flow_uuid
                WHERE " . implode(" AND ", $where_clause) .
                " GROUP BY #__analytics_event_attributes.name, #__analytics_event_attributes.value";

            // let sql_list_string = self.prefix(sql.clone().join(" "));
            // let mut sql_list =
            //     sqlx::query_as::<_, OutgoingMysqlTmpAttributesDetail>(&sql_list_string);
            // for one_bind in bind.iter() {
            //     sql_list = sql_list.bind(one_bind);
            // }

            // let mut hash_map: HashMap<String, HashMap<String, i32>> = HashMap::new();

            $sql = str_replace("#__", "wp_", $sql);

            $secondArray = $wpdb->get_results($sql);

            // var_dump($second);

            $hash_map = [];

            // Process each result
            foreach ($secondArray as $second) {
                $name = $second->name;
                $value = $second->value;
                $count = $second->count;

                if (!isset($hash_map[$name])) {
                    $sub_hash = [];
                    $sub_hash[$value] = $count;
                    $sub_hash['number_of_visitors'] = $second->number_of_visitors;
                    $sub_hash['number_of_page_views'] = $second->number_of_page_views;
                    $sub_hash['number_of_unique_page_views'] = $second->number_of_unique_page_views;
                    $sub_hash['average_session_duration'] = $second->average_session_duration;
                    $sub_hash['average_number_of_pages_per_session'] = $second->average_number_of_pages_per_session;
                    $sub_hash['bounce_rate'] = $second->bounce_rate;

                    $hash_map[$name] = $sub_hash;
                } else {
                    $hash_map[$name][$value] = $count;
                }
            }

            $not_allowed = [
                "number_of_visitors",
                "number_of_page_views",
                "number_of_unique_page_views",
                "average_session_duration",
                "average_number_of_pages_per_session",
                "bounce_rate"
            ];

            $collection = [];

            foreach ($hash_map as $key => $vals) {
                $vals_vec = [];

                foreach ($vals as $key_val => $val_val) {
                    if (in_array($key_val, $not_allowed)) {
                        continue;
                    }

                    $vals_vec[] = (object)[
                        'value' => $key_val,
                        'count' => $val_val,
                        'number_of_visitors' => $vals['number_of_visitors'],
                        'number_of_page_views' => $vals['number_of_page_views'],
                        'number_of_unique_page_views' => $vals['number_of_unique_page_views'],
                        'average_session_duration' => $vals['average_session_duration'],
                        'average_number_of_pages_per_session' => $vals['average_number_of_pages_per_session'],
                        'bounce_rate' => $vals['bounce_rate']
                    ];
                }

                $collection[] = (object)[
                    'name' => $key,
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
