<?php

use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Get_All_Flows extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        global $wpdb;

        $where_clause = [
            '#__analytics_visitors.ip != ""',
            '#__analytics_visitors.user_agent != ""',
            '#__analytics_visitors.device != ""',
            '#__analytics_visitors.browser_version != ""',
            '#__analytics_visitors.browser_name != ""',
            '#__analytics_visitors.lang != ""',
        ];
        $where_clause_event = [];
        $bind = [];
        $bind_event = [];

        $detail_page = false;
        parent::aesirx_analytics_add_filters($params, $where_clause, $bind);

        if ( isset($params['flow_uuid']) && !empty($params['flow_uuid'])) {
            $where_clause = ["#__analytics_flows.uuid = %s"];
            $bind = [ sanitize_text_field($params['flow_uuid'])];
            $detail_page = true;
        }

        // filters where clause for events

        $total_sql =
            "SELECT COUNT(DISTINCT #__analytics_flows.uuid) as total
            from `#__analytics_flows`
            left join `#__analytics_visitors` on #__analytics_visitors.uuid = #__analytics_flows.visitor_uuid
            left join `#__analytics_events` on #__analytics_events.flow_uuid = #__analytics_flows.uuid
            WHERE " . implode(" AND ", $where_clause);

        $sql =
            "SELECT #__analytics_flows.*, ip, user_agent, device, browser_name, browser_name, browser_version, domain, lang, city, isp, country_name, country_code, geo_created_at, #__analytics_visitors.uuid AS visitor_uuid, 
            COUNT(DISTINCT #__analytics_events.uuid) AS action, 
            CAST(SUM(CASE WHEN #__analytics_events.event_type = 'conversion' THEN 1 ELSE 0 END) as SIGNED) AS conversion, 
            CAST(SUM(CASE WHEN #__analytics_events.event_name = 'visit' THEN 1 ELSE 0 END) as SIGNED) AS pageview, 
            CAST(SUM(CASE WHEN #__analytics_events.event_name != 'visit' THEN 1 ELSE 0 END) as SIGNED) AS event, 
            MAX(CASE WHEN #__analytics_event_attributes.name = 'sop_id' THEN #__analytics_event_attributes.value ELSE NULL END) AS sop_id, 
            TIMESTAMPDIFF(SECOND, #__analytics_flows.start, #__analytics_flows.end) AS duration, 
            #__analytics_events.url AS url, 
            CAST(
                SUM(CASE WHEN #__analytics_events.event_name = 'visit' THEN 1 ELSE 0 END) * 2 +
                SUM(CASE WHEN #__analytics_events.event_name != 'visit' THEN 1 ELSE 0 END) * 5 +
                SUM(CASE WHEN #__analytics_events.event_type = 'conversion' THEN 1 ELSE 0 END) * 10
            as FLOAT) AS ux_percent, 
            CAST(SUM(CASE WHEN #__analytics_events.event_name = 'visit' THEN 1 ELSE 0 END) * 2 as FLOAT) AS visit_actions, 
            CAST(SUM(CASE WHEN #__analytics_events.event_name != 'visit' THEN 1 ELSE 0 END) * 5 as FLOAT) AS event_actions, 
            CAST(SUM(CASE WHEN #__analytics_events.event_type = 'conversion' THEN 1 ELSE 0 END) * 10 as FLOAT) AS conversion_actions 
            from `#__analytics_flows`
            left join `#__analytics_visitors` on #__analytics_visitors.uuid = #__analytics_flows.visitor_uuid
            left join `#__analytics_events` on #__analytics_events.flow_uuid = #__analytics_flows.uuid
            left join `#__analytics_event_attributes` on #__analytics_events.uuid = #__analytics_event_attributes.event_uuid
            WHERE " . implode(" AND ", $where_clause) .
            " GROUP BY #__analytics_flows.uuid";

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
                "action",
                "event",
                "conversion",
                "url",
                "ux_percent",
                "pageview",
                "bounce_rate",
                "sop_id",
                "duration",
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

        $collection = [];

        $ret = [];
        $dirs = [];

        if (!empty($list)) {
            if (isset($params['request']['with']) && !empty($params['request']['with'])) {
                $with = $params['request']['with'];
                if (in_array("events", $with)) {
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

                    foreach ($events as $second) {
                        $og_title = null;
                        $og_description = null;
                        $og_image = null;

                        // OG
                        if ($detail_page == true && !empty($second->url)) {
                            // Try to fetch and parse the Open Graph data
                            $og_data = parent::aesirx_analytics_fetch_open_graph_data($second->url);
                            
                            if (!empty($og_data)) {
                                $og_title = isset($og_data['og:title']) ? $og_data['og:title'] : null;
                                $og_description = isset($og_data['og:description']) ? $og_data['og:description'] : null;
                                $og_image = isset($og_data['og:image']) ? $og_data['og:image'] : null;
                            }
                        }

                        $second->og_title = $og_title;
                        $second->og_description = $og_description;
                        $second->og_image = $og_image;

                        if (!filter_var($second->url, FILTER_VALIDATE_URL)) {
                            $status_code = 404;
                        } else {
                            $response = wp_remote_head($second->url);

                            if (is_wp_error($response)) {
                                $status_code = 500;
                            } else {
                                $status_code = wp_remote_retrieve_response_code($response);
                            }
                        }

                        $second->status_code = $status_code;
                        $second->attribute = $hash_attributes[$second->uuid] ?? [];

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
                            'og_title' => $og_title,
                            'og_description' => $og_description,
                            'og_image' => $og_image,
                            'status_code' => $status_code
                        ];

                        if (!isset($hash_map[$second->flow_uuid])) {
                            $hash_map[$second->flow_uuid] = [$second->uuid => $visitor_event];
                        } else {
                            $hash_map[$second->flow_uuid][$second->uuid] = $visitor_event;
                        }
                    }

                    if (!empty($events) && $params[1] == "flow") {
                        if ($events[0]->start == $events[0]->end) {
                            $consents = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                                $wpdb->prepare(
                                    "SELECT * FROM {$wpdb->prefix}analytics_visitor_consent WHERE visitor_uuid = %s AND UNIX_TIMESTAMP(datetime) > %d",
                                    $events[0]->visitor_uuid,
                                    strtotime($events[0]->start)
                                )
                            );
                        } else {
                            $consents = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                                $wpdb->prepare(
                                    "SELECT * FROM {$wpdb->prefix}analytics_visitor_consent WHERE visitor_uuid = %s AND UNIX_TIMESTAMP(datetime) > %d AND UNIX_TIMESTAMP(datetime) < %d",
                                    $events[0]->visitor_uuid,
                                    strtotime($events[0]->start),
                                    strtotime($events[0]->end)
                                )
                            );
                        }
    
                        foreach ($consents as $consent) {
                            $consent_data = $events[0];
    
                            if ($consent->consent_uuid != null) {
                                $consent_detail = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                                    $wpdb->prepare(
                                        "SELECT * FROM {$wpdb->prefix}analytics_consent WHERE uuid = %s",
                                        $consent->consent_uuid
                                    )
                                );

                                if (!isset($consent_detail->consent) || $consent_detail->consent != 1) {
                                    continue;
                                }
    
                                if (!empty($consent_detail)) {
                                    $consent_attibute = [
                                        "web3id" => $consent_detail->web3id,
                                        "network" => $consent_detail->network,
                                        "datetime" => $consent_detail->datetime,
                                        "expiration" => $consent_detail->expiration,
                                        "tier" => 1,
                                    ];
    
                                    $wallet_detail = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                                        $wpdb->prepare(
                                            "SELECT * FROM {$wpdb->prefix}analytics_wallet WHERE uuid = %s",
                                            $consent_detail->wallet_uuid
                                        )
                                    );
    
                                    if (!empty($wallet_detail)) {
                                        $consent_attibute["wallet"] = $wallet_detail->address;
                                    }
    
                                    if ($consent_detail->web3id) {
                                        $consent_attibute["tier"] = 2;
                                    }
    
                                    if ($wallet_detail->address) {
                                        $consent_attibute["tier"] = 3;
                                    }
    
                                    if ($consent_detail->web3id && $wallet_detail->address) {
                                        $consent_attibute["tier"] = 4;
                                    }
    
                                    $consent_data->attributes = $consent_attibute;
                                }
    
                                $consent_data->uuid = $consent->consent_uuid;
                                $consent_data->start = $consent_detail->datetime;
                                $consent_data->end = $consent_detail->expiration;
                            } else {

                                if ($consent->consent != 1) {
                                    continue;
                                }

                                $consent_data->start = $consent->datetime;
                                $consent_data->end = $consent->expiration;
                            }
    
                            $consent_data->event_name = 'Consent';
                            $consent_data->event_type = 'consent';
    
                            $hash_map[$consent_data->flow_uuid][] = $consent_data;
                        }
                    }
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

                if ( $params[1] == 'flows') {

                    $bad_url_count = 0;

                    if (!empty($events)) {
                        $bad_url_count = count(array_filter($events, fn($item) => $item->status_code !== 200));
                    }

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
                        'duration' => $item->duration,
                        'action' => $item->action,
                        'event' => $item->event,
                        'conversion' => $item->conversion,
                        'url' => $item->url,
                        'ux_percent' => $item->ux_percent,
                        'pageview' => $item->pageview,
                        'sop_id' => $item->sop_id,
                        'visit_actions' => $item->visit_actions,
                        'event_actions' => $item->event_actions,
                        'conversion_actions' => $item->conversion_actions,
                        'bad_user' => $bad_url_count > 1 ? true : false,
                    ];
                }
                elseif ( $params[1] == 'flow' ) {
                    $collection = [
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
                        'duration' => $item->duration,
                        'action' => $item->action,
                        'event' => $item->event,
                        'conversion' => $item->conversion,
                        'url' => $item->url,
                        'ux_percent' => $item->ux_percent,
                        'pageview' => $item->pageview,
                        'sop_id' => $item->sop_id,
                        'visit_actions' => $item->visit_actions,
                        'event_actions' => $item->event_actions,
                        'conversion_actions' => $item->conversion_actions,
                    ];
                }
            }
        }

        if ( $params[1] == 'flows') {
            return [
                'collection' => $collection,
                'page' => $list_response['page'],
                'page_size' => $list_response['page_size'],
                'total_pages' => $list_response['total_pages'],
                'total_elements' => $list_response['total_elements'],
            ];
        }
        elseif ( $params[1] == 'flow' ) {
            return $collection;
        }
    }
}
