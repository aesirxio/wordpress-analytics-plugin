<?php

use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Get_Live_Visitors_List extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        global $wpdb;

        $where_clause = [
            "#__analytics_flows.start = #__analytics_flows.end",
            "#__analytics_flows.start >= NOW() - INTERVAL 30 MINUTE",
            "#__analytics_visitors.device != 'bot'"
        ];
        $bind = [];

        unset($params["filter"]["start"]);
        unset($params["filter"]["end"]);

        parent::aesirx_analytics_add_filters($params, $where_clause, $bind);

        // filters where clause for events

        $total_sql =
            "SELECT COUNT(DISTINCT #__analytics_flows.uuid) as total
            from `#__analytics_flows`
            left join `#__analytics_visitors` on #__analytics_visitors.uuid = #__analytics_flows.visitor_uuid
            left join `#__analytics_events` on #__analytics_events.flow_uuid = #__analytics_flows.uuid
            WHERE " . implode(" AND ", $where_clause) .
            " GROUP BY `#__analytics_flows`.`visitor_uuid`";

        $sql =
            "SELECT #__analytics_flows.*, ip, user_agent, device, browser_name, browser_name, browser_version, domain, lang, city, isp, country_name, country_code, geo_created_at, #__analytics_visitors.uuid AS visitor_uuid,
            MAX(CASE WHEN #__analytics_event_attributes.name = 'sop_id' THEN #__analytics_event_attributes.value ELSE NULL END) AS sop_id, 
            #__analytics_events.url AS url
            from `#__analytics_flows`
            left join `#__analytics_visitors` on #__analytics_visitors.uuid = #__analytics_flows.visitor_uuid
            left join `#__analytics_events` on #__analytics_events.flow_uuid = #__analytics_flows.uuid
            left join `#__analytics_event_attributes` on #__analytics_events.uuid = #__analytics_event_attributes.event_uuid
            WHERE " . implode(" AND ", $where_clause) .
            " GROUP BY `#__analytics_flows`.`visitor_uuid`";

        $sort = parent::aesirx_analytics_add_sort(
            $params,
            [
                "start",
                "end",
                "geo.country.name",
                "geo.country.code",
                "ip",
                "device",
                "browser_name",
                "browser_version",
                "domain",
                "lang",
                "url",
                "sop_id",
            ],
            "start",
        );

        if (!empty($sort)) {
            $sql .= " ORDER BY " . implode(", ", $sort);
        }

        $list_response = parent::aesirx_analytics_get_list($sql, $total_sql, $params, [], $bind);

        if (is_wp_error($list_response)) {
            return $list_response;
        }

        $list = $list_response['collection'];

        if (empty($list)) {
            return [
                'collection' => [],
                'page' => 1,
                'page_size' => 1,
                'total_pages' => 1,
                'total_elements' => 0,
            ];
        }

        $collection = [];

        $ret = [];
        $dirs = [];

        $bind = array_map(function($e) {
            return $e['uuid'];
        }, $list);

        // doing direct database calls to custom tables
        // placeholders depends one number of $bind
        $events = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}analytics_events WHERE flow_uuid IN (" . implode(', ', array_fill(0, count($bind), '%s')) . ")", 
                ...$bind
            )
        );

        // doing direct database calls to custom tables
        // placeholders depends one number of $bind
        $attributes = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}analytics_event_attributes 
                LEFT JOIN {$wpdb->prefix}analytics_events
                ON {$wpdb->prefix}analytics_events.uuid = {$wpdb->prefix}analytics_event_attributes.event_uuid 
                WHERE {$wpdb->prefix}analytics_events.flow_uuid IN (" . implode(', ', array_fill(0, count($bind), '%s')) . ")",
                ...$bind
            )
        ); 
        
        $hash_attributes = [];

        foreach ($attributes as $second) {
            $attr = (object)[
                'name' => $second->name,
                'value' => $second->value,
            ];
            if (!isset($hash_attributes[$second->event_uuid])) {
                $hash_attributes[$second->event_uuid] = [$attr];
            } else {
                $hash_attributes[$second->event_uuid][] = $attr;
            }
        }

        $hash_map = [];

        foreach ($events as $second) {
            $visitor_event = [
                'uuid' => $second->uuid,
                'visitor_uuid' => $second->visitor_uuid,
                'flow_uuid' => $second->flow_uuid,
                'url' => $second->url,
                'referer' => $second->referer,
                'start' => $second->start,
                'end' => $second->end,
                'event_name' => $second->event_name,
                'event_type' => $second->event_type,
                'attributes' => $hash_attributes[$second->uuid] ?? [],
            ];

            if (!isset($hash_map[$second->flow_uuid])) {
                $hash_map[$second->flow_uuid] = [$second->uuid => $visitor_event];
            } else {
                $hash_map[$second->flow_uuid][$second->uuid] = $visitor_event;
            }
        }

        foreach ($list as $item) {
            $item = (object) $item;
            
            if (!empty($collection) && end($collection)['uuid'] == $item->uuid) {
                continue;
            }

            $geo = isset($item->geo_created_at) ? (object)[
                'country' => (object)[
                    'name' => $item->country_name,
                    'code' => $item->country_code,
                ],
                'city' => $item->city,
                'isp' => $item->isp,
                'created_at' => $item->geo_created_at,
            ] : null;

            $events = isset($hash_map[$item->uuid]) ? array_values($hash_map[$item->uuid]) : null;

            $collection[] = [
                'uuid' => $item->uuid,
                'visitor_uuid' => $item->visitor_uuid,
                'ip' => $item->ip,
                'user_agent' => $item->user_agent,
                'device' => $item->device,
                'browser_name' => $item->browser_name,
                'browser_version' => $item->browser_version,
                'domain' => $item->domain,
                'lang' => $item->lang,
                'start' => $item->start,
                'end' => $item->end,
                'geo' => $geo,
                'events' => $events,
                'url' => $item->url,
                'sop_id' => $item->sop_id,
            ];
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
