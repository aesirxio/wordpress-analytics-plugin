<?php


use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Get_List_Events extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        global $wpdb;

        $where_clause = [];
        parent::aesirx_analytics_add_filters($params, $where_clause);

        // add_attribute_filters(params, &mut where_clause, &mut bind);

        foreach ([$params['filter'], $params['filter_not']] as $filter_array) {
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

        $sql = str_replace("#__", $wpdb->prefix, $sql);
        $total_sql = str_replace("#__", $wpdb->prefix, $total_sql);

        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 20;
        $skip = ($page - 1) * $pageSize;

        $sql .= " LIMIT " . $skip . ", " . $pageSize;

        $total_elements = (int) $wpdb->get_var($total_sql);
        $total_pages = ceil($total_elements / $pageSize);

        $list = $wpdb->get_results($sql, ARRAY_A);

        $collection = [];

        if ($list) {
            $bind = array_map(function($e) {
                return $e['uuid'];
            }, $list);

            $sql =
                "SELECT *
                from #__analytics_event_attributes
                WHERE
                event_uuid IN ('" . implode("', '", $bind) . "')";

            $sql = str_replace("#__", $wpdb->prefix, $sql);
            
            $secondArray = $wpdb->get_results($sql);

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
                    'start' => $item->start,
                    'end' => $item->end,
                    'event_name' => $item->event_name,
                    'event_type' => $item->event_type,
                    'attributes' => !empty($attributes) ? $attributes : null,
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
