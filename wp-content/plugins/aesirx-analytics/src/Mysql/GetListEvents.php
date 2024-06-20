<?php

// namespace AesirxAnalytics\Mysql;

use AesirxAnalytics\MysqlHelper;

Class AesirX_Analytics_Get_List_Events extends MysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        global $wpdb;

        // let mut where_clause: Vec<String> = vec![];
        // let mut bind: Vec<String> = vec![];
        // add_filters(params, &mut where_clause, &mut bind)?;

        // add_attribute_filters(params, &mut where_clause, &mut bind);

        // for (filter, is_not) in [(&params.filter, false), (&params.filter_not, true)] {
        //     if filter.is_none() {
        //         continue;
        //     }

        //     for (key, val) in filter.as_ref().unwrap().iter() {
        //         match val {
        //             ParameterValue::Primitive(_) | ParameterValue::Array(_) => {
        //                 match get_parameters_as_array(val) {
        //                     None => {}
        //                     Some(list) => match key.as_str() {
        //                         "visitor_uuid" | "flow_uuid" | "uuid" => {
        //                             where_clause.push(format!(
        //                                 "#__analytics_events.{} {} IN ({})",
        //                                 if is_not { "NOT" } else { "" },
        //                                 key.as_str(),
        //                                 format_args!("?{}", ", ?".repeat(list.len() - 1))
        //                             ));
        //                             bind.extend(list);
        //                         }
        //                         _ => {}
        //                     },
        //                 }
        //             }
        //             _ => {}
        //         }
        //     }
        // }

        // let total_sql: Vec<String> = vec![
        //     "SELECT COUNT(DISTINCT #__analytics_events.uuid) as total
        //     "from `#__analytics_events`
        //     "left join `#__analytics_visitors` on #__analytics_visitors.uuid = #__analytics_events.visitor_uuid
        //     "left join `#__analytics_event_attributes` on #__analytics_event_attributes.event_uuid = #__analytics_events.uuid
        //     "WHERE
        //     where_clause.join(" AND "),
        // ];

        $sql =
            "SELECT #__analytics_events.*, #__analytics_visitors.domain
            from `#__analytics_events`
            left join `#__analytics_visitors` on #__analytics_visitors.uuid = #__analytics_events.visitor_uuid
            left join `#__analytics_event_attributes` on #__analytics_event_attributes.event_uuid = #__analytics_events.uuid";

        // let sort = add_sort(
        //     params,
        //     vec![
        //         "start",
        //         "end",
        //         "url",
        //         "event_name",
        //         "event_type",
        //         "domain",
        //         "referrer",
        //     ],
        //     "start",
        // );

        // if !sort.is_empty() {
        //     sql.push("ORDER BY".to_string());
        //     sql.push(sort.join(","));
        // }

        // let list = self
        //     .get_list::<OutgoingMysqlListVisitorEvent>(sql, total_sql, bind.clone(), params)
        //     .await?;
        // let mut collection: Vec<OutgoingListVisitorEvent> = vec![];

        $sql = str_replace("#__", "wp_", $sql);

        $list = $wpdb->get_results($sql, ARRAY_A);

        $collection = [];

        // var_dump($list);

        if ($list) {
            // let bind: Vec<String> = list.collection.iter().map(|e| e.uuid.clone()).collect();

            $bind = array_map(function($e) {
                return $e['uuid'];
            }, $list);

            $sql =
                "SELECT *
                from #__analytics_event_attributes
                WHERE
                event_uuid IN ('" . implode("', '", $bind) . "')";

            // let sql_list_string = self.prefix(sql.clone().join(" "));
            // let mut sql_list = sqlx::query_as::<_, MysqlVisitorEventAttribute>(&sql_list_string);
            // for one_bind in bind.iter() {
            //     sql_list = sql_list.bind(one_bind);
            // }


            // let mut hash_map: HashMap<String, HashMap<String, String>> = HashMap::new();

            // for second in sql_list.fetch_all(&self.sqlx_conn).await?.iter() {
            //     match hash_map.get_mut(second.name.clone().as_str()) {
            //         None => {
            //             let mut sub_hash: HashMap<String, String> = HashMap::new();
            //             sub_hash.insert(second.name.clone(), second.value.clone());
            //             hash_map.insert(second.event_uuid.clone(), sub_hash);
            //         }
            //         Some(some) => {
            //             some.insert(second.name.clone(), second.value.clone());
            //         }
            //     };
            // }

            // for item in list.collection.iter() {
            //     let mut attributes: Vec<VisitorEventAttribute> = vec![];
            //     match hash_map.get(item.uuid.as_str()) {
            //         None => {}
            //         Some(some) => {
            //             for (attr_name, attr_val) in some.iter() {
            //                 attributes.push(VisitorEventAttribute {
            //                     name: attr_name.clone(),
            //                     value: attr_val.clone(),
            //                 });
            //             }
            //         }
            //     };

            //     collection.push(OutgoingListVisitorEvent {
            //         uuid: Uuid::parse_str(item.uuid.clone())?,
            //         visitor_uuid: Uuid::parse_str(item.visitor_uuid.clone())?,
            //         flow_uuid: Uuid::parse_str(item.flow_uuid.clone())?,
            //         url: item.url.clone(),
            //         domain: item.domain.clone(),
            //         referer: item.referer.clone(),
            //         start: Utc.from_utc_datetime(&item.start),
            //         end: Utc.from_utc_datetime(&item.end),
            //         event_name: item.event_name.clone(),
            //         event_type: item.event_type.clone(),
            //         attributes: if !attributes.is_empty() {
            //             Some(attributes)
            //         } else {
            //             None
            //         },
            //     });
            // }

            $sql = str_replace("#__", "wp_", $sql);
            
            $secondArray = $wpdb->get_results($sql);

            $hash_map = [];

            // Process each result
            foreach ($secondArray as $second) {
                $name = $second->name;
                $event_uuid = $second->event_uuid;
                $value = $second->value;

                if (!isset($hash_map[$event_uuid])) {
                    $sub_hash = [];
                    $sub_hash[$name] = $value;
                    $hash_map[$event_uuid] = $sub_hash;
                } else {
                    $hash_map[$event_uuid][$name] = $value;
                }
            }

            $collection = [];

            foreach ($list->collection as $item) {
                $attributes = [];

                if (isset($hash_map[$item->uuid])) {
                    foreach ($hash_map[$item->uuid] as $attr_name => $attr_val) {
                        $attributes[] = (object)[
                            'name' => $attr_name,
                            'value' => $attr_val,
                        ];
                    }
                }

                $collection[] = (object)[
                    'uuid' => $item->uuid,
                    'visitor_uuid' => $item->visitor_uuid,
                    'flow_uuid' => $item->flow_uuid,
                    'url' => $item->url,
                    'domain' => $item->domain,
                    'referer' => $item->referer,
                    'start' => new DateTime($item->start),
                    'end' => new DateTime($item->end),
                    'event_name' => $item->event_name,
                    'event_type' => $item->event_type,
                    'attributes' => !empty($attributes) ? $attributes : null,
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
