<?php


use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Get_List_Events extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        global $wpdb;

        $where_clause = [];
        $bind = [];

        parent::aesirx_analytics_add_filters($params, $where_clause, $bind);
        parent::aesirx_analytics_add_attribute_filters($params, $where_clause, $bind);

        foreach ([$params['filter'] ?? null, $params['filter_not'] ?? null] as $filter_array) {
            $is_not = $filter_array === (isset($params['filter_not']) ? $params['filter_not'] : null);
            if (empty($filter_array)) {
                continue;
            }
    
            foreach ($filter_array as $key => $vals) {
                $list = is_array($vals) ? $vals : [$vals];

                switch ($key) {
                    case 'visitor_uuid':
                    case 'flow_uuid':
                    case 'uuid':
                        $where_clause[] = '#__analytics_events.' . $key . ' ' . ($is_not ? 'NOT ' : '') . 'IN ("' . implode(', ', $list) . '")';
                        break;
                    default:
                        break;
                }
            }
        }

        $total_sql =
            "SELECT COUNT(DISTINCT #__analytics_events.uuid) as total
            from `#__analytics_events`
            left join `#__analytics_visitors` on #__analytics_visitors.uuid = #__analytics_events.visitor_uuid
            left join `#__analytics_event_attributes` on #__analytics_event_attributes.event_uuid = #__analytics_events.uuid
            WHERE " . implode(" AND ", $where_clause);

        $sql =
            "SELECT #__analytics_events.*, #__analytics_visitors.domain
            from `#__analytics_events`
            left join `#__analytics_visitors` on #__analytics_visitors.uuid = #__analytics_events.visitor_uuid
            left join `#__analytics_event_attributes` on #__analytics_event_attributes.event_uuid = #__analytics_events.uuid
            WHERE " . implode(" AND ", $where_clause);

        $sort = self::aesirx_analytics_add_sort(
            $params,
            [
                "start",
                "end",
                "url",
                "event_name",
                "event_type",
                "domain",
                "referrer",
            ],
            "start"
        );

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
            $event_attribute_bind = array_map(function($e) {
                return $e['uuid'];
            }, $list);
            
            // %s depends one number of $event_attribute_bind
            // doing direct database calls to custom tables
            $secondArray = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->prepare(
                    "SELECT * 
                    FROM {$wpdb->prefix}analytics_event_attributes 
                    WHERE event_uuid IN (" . implode(', ', array_fill(0, count($event_attribute_bind), '%s')) . ")",
                    ...$event_attribute_bind
                )
            );

            $hash_map = [];

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

            foreach ($list as $item) {
                $item = (object) $item;
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
                    'start' => $item->start,
                    'end' => $item->end,
                    'event_name' => $item->event_name,
                    'event_type' => $item->event_type,
                    'attributes' => !empty($attributes) ? $attributes : null,
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
