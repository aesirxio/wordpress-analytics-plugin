<?php

namespace AesirxAnalytics;

use WP_Error;

Class AesirxAnalyticsMysqlHelper
{
    public function aesirx_analytics_get_list($sql, $total_sql, $params) {
        global $wpdb;

        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 20;
        $skip = ($page - 1) * $pageSize;
    
        $sql .= " LIMIT " . $skip . ", " . $pageSize;

        $sql = str_replace("#__", "wp_", $sql);
        $total_sql = str_replace("#__", "wp_", $total_sql);

        $total_elements = (int) $wpdb->get_var($total_sql);
        $total_pages = ceil($total_elements / $pageSize);

        try {
            $collection = $wpdb->get_results($sql, ARRAY_A);

            $list_response = [
                'collection' => $collection,
                'page' => (int) $page,
                'page_size' => (int) $pageSize,
                'total_pages' => $total_pages,
                'total_elements' => $total_elements,
            ];

            if ($params[1] == "metrics") {
                $list_response = $list_response['collection'][0];
            }

            return $list_response;

        } catch (Exception $e) {
            error_log("Error: " . $e->getMessage());
            return new WP_Error('database_error', 'Database error occurred.', ['status' => 500]);
        }
    }

    public function aesirx_analytics_get_statistics_per_field($groups = [], $selects = [], $params = []) {
        global $wpdb;

        $select = [
            "coalesce(COUNT(DISTINCT (#__analytics_events.visitor_uuid)), 0) as number_of_visitors",
            "coalesce(COUNT(#__analytics_events.visitor_uuid), 0) as total_number_of_visitors",
            "COUNT(#__analytics_events.uuid) as number_of_page_views",
            "COUNT(DISTINCT (#__analytics_events.url)) AS number_of_unique_page_views",
            "coalesce(SUM(TIMESTAMPDIFF(SECOND, #__analytics_events.start, #__analytics_events.end)) / count(distinct #__analytics_visitors.uuid), 0) DIV 1 as average_session_duration",
            "coalesce((COUNT(#__analytics_events.uuid) / COUNT(DISTINCT (#__analytics_events.flow_uuid))), 0) DIV 1 as average_number_of_pages_per_session",
            "coalesce((count(DISTINCT CASE WHEN #__analytics_flows.multiple_events = 0 THEN #__analytics_flows.uuid END) * 100) / count(DISTINCT (#__analytics_flows.uuid)), 0) DIV 1 as bounce_rate",
        ];

        $total_select = [];

        if (!empty($groups)) {
            foreach ($groups as $one_group) {
                $select[] = $one_group;
            }

            $total_select[] = "COUNT(DISTINCT " . implode(', COALESCE(', $groups) . ") AS total";
        }
        else {
            $total_select[] = "COUNT(#__analytics_events.uuid) AS total";
        }

        foreach ($selects as $additional_result) {
            $select[] = $additional_result["select"] . " AS " . $additional_result["result"];
        }
        
        $where_clause = [
            "#__analytics_events.event_name = 'visit'",
            "#__analytics_events.event_type = 'action'",
        ];

        self::aesirx_analytics_add_filters($params, $where_clause);

        $acquisition = false;
        foreach ($params['filter'] as $key => $vals) {
            if ($key === "acquisition") {
                $list = is_array($vals) ? $vals : [$vals];
                if ($list[0] === "true") {
                    $acquisition = true;
                }
                break;
            }
        }

        if ($acquisition) {
            $where_clause[] = "#__analytics_flows.multiple_events = 0";
        }

        $total_sql = "SELECT " . implode(", ", $total_select) . " FROM #__analytics_events
                    LEFT JOIN #__analytics_visitors ON #__analytics_visitors.uuid = #__analytics_events.visitor_uuid
                    LEFT JOIN #__analytics_flows ON #__analytics_flows.uuid = #__analytics_events.flow_uuid
                    WHERE " . implode(" AND ", $where_clause);

        $sql = "SELECT " . implode(", ", $select) . " FROM #__analytics_events
                LEFT JOIN #__analytics_visitors ON #__analytics_visitors.uuid = #__analytics_events.visitor_uuid
                LEFT JOIN #__analytics_flows ON #__analytics_flows.uuid = #__analytics_events.flow_uuid
                WHERE " . implode(" AND ", $where_clause);

        if (!empty($groups)) {
            $sql .= " GROUP BY " . implode(", ", $groups);
        }

        $allowed = [
            "number_of_visitors",
            "number_of_page_views",
            "number_of_unique_page_views",
            "average_session_duration",
            "average_number_of_pages_per_session",
            "bounce_rate",
        ];
        $default = reset($allowed);

        foreach ($groups as $one_group) {
            $allowed[] = $one_group;
            $default = $one_group;
        }

        foreach ($groups as $additional_result) {
            $allowed[] = $additional_result;
        }

        $sort = self::aesirx_analytics_add_sort($params, $allowed, $default);

        if (!empty($sort)) {
            $sql .= " ORDER BY " . implode(", ", $sort);
        }

        return self::aesirx_analytics_get_list($sql, $total_sql, $params, $allowed);
    }

    function aesirx_analytics_add_sort($params, $allowed, $default) {
        $ret = [];
        $dirs = [];

        if (isset($params['sort_direction'])) {
            foreach ($params['sort_direction'] as $pos => $value) {
                $dirs[$pos] = $value;
            }
        }

        if (!isset($params['sort'])) {
            $ret[] = sprintf("%s ASC", $default);
        } else {
            foreach ($params['sort'] as $pos => $value) {
                if (!in_array($value, $allowed)) {
                    continue;
                }

                $dir = "ASC";
                if (isset($dirs[$pos]) && $dirs[$pos] === "desc") {
                    $dir = "DESC";
                }

                $ret[] = sprintf("%s %s", $value, $dir);
            }

            if (empty($ret)) {
                $ret[] = sprintf("%s ASC", $default);
            }
        }

        return $ret;
    }

    function aesirx_analytics_add_filters($params, &$where_clause) {
        foreach ([$params['filter'], $params['filter_not']] as $filter_array) {
            if (empty($filter_array)) {
                continue;
            }
    
            foreach ($filter_array as $key => $vals) {
                $list = is_array($vals) ? $vals : [$vals];

                switch ($key) {
                    case 'start':
                        try {
                            $date = new DateTime($list[0]);
                            $where_clause[] = '#__analytics_events.' . $key . ' >= ' . $date->format('Y-m-d');
                        } catch (Exception $e) {
                            return new WP_Error('validation_error', '"start" filter is not correct', ['status' => 400]);
                        }
                        break;
                    case 'end':
                        try {
                            $date = new DateTime($list[0]);
                            $date->modify('+1 day');
                            $where_clause[] = '#__analytics_events.' . $key . ' < ' . $date->format('Y-m-d');
                        } catch (Exception $e) {
                            return new WP_Error('validation_error', '"end" filter is not correct', ['status' => 400]);
                        }
                        break;
                    case 'event_name':
                    case 'event_type':
                        $where_clause[] = '#__analytics_events.' . $key . ' ' . ($is_not ? 'NOT ' : '') . 'IN ("' . implode(', ', $list) . '")';
                        break;
                    case 'city':
                    case 'isp':
                    case 'country_code':
                    case 'country_name':
                    case 'url':
                    case 'domain':
                    case 'browser_name':
                    case 'browser_version':
                    case 'device':
                    case 'lang':
                        $where_clause[] = '#__analytics_visitors.' . $key . ' ' . ($is_not ? 'NOT ' : '') . 'IN ("' . implode(', ', $list) . '")';
                        break;
                    default:
                        break;
                }
            }
        }
    }

    function aesirx_analytics_add_attribute_filters($params, &$where_clause) {
        foreach ([$params['filter'], $params['filter_not']] as $filter_array) {
            if (empty($filter_array)) {
                continue;
            }
    
            foreach ($filter_array as $key => $val) {
                switch ($val) {
                    case ParameterValue::List($hash_map):
                        if ($key == "attribute") {
                            foreach ($hash_map as $key => $vals) {
                                $parameters_array = is_array($vals) ? $vals : [$vals];
                                if ($parameters_array == null) {
                                    continue;
                                }
    
                               $where_clause[] = '#__analytics_event_attributes.name = ' . $key . ' ' . ($is_not ? 'NOT ' : '') . 'IN ("' . implode(', ', $parameters_array) . '")';
                            }
                        }
                        break;
                        case ParameterValue::Primitive($value):
                        case ParameterValue::Array($array):
                            $list = is_array($val) ? $val : [$val];
                            if ($list == null) {
                                continue;
                            }

                            switch ($key) {
                                case "attribute_name":
                                    if ($is_not) {
                                        $where_clause[] = '#__analytics_event_attributes.event_uuid IS NULL OR #__analytics_event_attributes.name NOT IN ("' . implode(', ', $list) . '")';
                                    } else {
                                        $where_clause[] = '#__analytics_event_attributes.name IN ("' . implode(', ', $list) . '")';
                                    }
                                    break;
                                case "attribute_value":
                                    if ($is_not) {
                                        $where_clause[] = '#__analytics_event_attributes.event_uuid IS NULL OR #__analytics_event_attributes.value NOT IN ("' . implode(', ', $list) . '")';
                                    } else {
                                        $where_clause[] = '#__analytics_event_attributes.value IN ("' . implode(', ', $list) . '")';
                                    }
                                    break;
                            }
                        break;
                }
            }
        }
    }

    function aesirx_analytics_validate_domain($url) {
        $parsed_url = parse_url($url);

        if ($parsed_url === false || !isset($parsed_url['host'])) {
            return new WP_Error('validation_error', 'Domain not found', ['status' => 400]);
        }

        $domain = $parsed_url['host'];

        if (strpos($domain, 'www.') === 0) {
            $domain = substr($domain, 4);
        }

        $transient_name = 'domain_list';
        $data = get_transient($transient_name);

        if ($data === false) {
            $data = [
                'domain_list' => [],
                'limit_domain' => 10,
            ];
        }

        $passed = false;
        $domain_list = $data['domain_list'];
        $limit_domain = $data['limit_domain'];

        if (in_array($domain, $domain_list) || count($domain_list) < $limit_domain) {
            if (!in_array($domain, $domain_list)) {
                $domain_list[] = $domain;
                $data['domain_list'] = $domain_list;
                set_transient($transient_name, $data, 12 * HOUR_IN_SECONDS); // Save the updated state
            }
            $passed = true;
        }

        if ($passed) {
            return $domain;
        } else {
            return new WP_Error('rejected', 'Your domain name has exceeded the allowed number', ['status' => 403]);
        }
    }

    function aesirx_analytics_find_visitor_by_fingerprint_and_domain($fingerprint, $domain) {

        global $wpdb;

        // Prefix your table names
        $visitors_table = $wpdb->prefix . 'analytics_visitors';
        $flows_table = $wpdb->prefix . 'analytics_flows';

        // Query to fetch the visitor
        $sql = $wpdb->prepare("SELECT * FROM $visitors_table WHERE fingerprint = %s AND domain = %s", $fingerprint, $domain);
        $visitor = $wpdb->get_row($sql);

        if ($visitor) {
            $res = [
                'fingerprint' => $visitor->fingerprint,
                'uuid' => $visitor->uuid,
                'ip' => $visitor->ip,
                'user_agent' => $visitor->user_agent,
                'device' => $visitor->device,
                'browser_name' => $visitor->browser_name,
                'browser_version' => $visitor->browser_version,
                'domain' => $visitor->domain,
                'lang' => $visitor->lang,
                'visitor_flows' => null,
                'geo' => null,
                'visitor_consents' => [],
            ];

            if ($visitor->geo_created_at) {
                $res['geo'] = [
                    'country' => [
                        'name' => $visitor->country_name,
                        'code' => $visitor->country_code,
                    ],
                    'city' => $visitor->city,
                    'region' => $visitor->region,
                    'isp' => $visitor->isp,
                    'created_at' => $visitor->geo_created_at,
                ];
            }

            // Query to fetch the visitor flows
            $sql = $wpdb->prepare("SELECT * FROM $flows_table WHERE visitor_uuid = %s ORDER BY id", $visitor->uuid);
            $flows = $wpdb->get_results($sql);

            if ($flows) {
                $ret_flows = [];
                foreach ($flows as $flow) {
                    $ret_flows[] = [
                        'uuid' => $flow->uuid,
                        'start' => $flow->start,
                        'end' => $flow->end,
                        'multiple_events' => $flow->multiple_events,
                    ];
                }
                $res['visitor_flows'] = $ret_flows;
            }

            return $res;
        }

        return null;
    }

    function aesirx_analytics_create_visitor($visitor) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'analytics_visitors';

        if (empty($visitor['geo'])) {
            $wpdb->insert(
                $table_name,
                [
                    'fingerprint'      => $visitor['fingerprint'],
                    'uuid'             => $visitor['uuid'],
                    'ip'               => $visitor['ip'],
                    'user_agent'       => $visitor['user_agent'],
                    'device'           => $visitor['device'],
                    'browser_name'     => $visitor['browser_name'],
                    'browser_version'  => $visitor['browser_version'],
                    'domain'           => $visitor['domain'],
                    'lang'             => $visitor['lang']
                ],
                [
                    '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'
                ]
            );
        } else {
            $geo = $visitor['geo'];
            $wpdb->insert(
                $table_name,
                [
                    'fingerprint'      => $visitor['fingerprint'],
                    'uuid'             => $visitor['uuid'],
                    'ip'               => $visitor['ip'],
                    'user_agent'       => $visitor['user_agent'],
                    'device'           => $visitor['device'],
                    'browser_name'     => $visitor['browser_name'],
                    'browser_version'  => $visitor['browser_version'],
                    'domain'           => $visitor['domain'],
                    'lang'             => $visitor['lang'],
                    'country_code'     => $geo['country']['code'],
                    'country_name'     => $geo['country']['name'],
                    'city'             => $geo['city'],
                    'isp'              => $geo['isp'],
                    'geo_created_at'   => $geo['created_at']
                ],
                [
                    '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'
                ]
            );
        }

        if (!empty($visitor['visitor_flows'])) {
            foreach ($visitor['visitor_flows'] as $flow) {
                self::aesirx_analytics_create_visitor_flow($visitor['uuid'], $flow);
            }
        }

        return true;
    }

    function aesirx_analytics_create_visitor_event($visitor_event) {
        global $wpdb;
        $table_name_events = $wpdb->prefix . 'analytics_events';
        $table_name_event_attributes = $wpdb->prefix . 'analytics_event_attributes';

        // Insert event
        $wpdb->insert(
            $table_name_events,
            [
                'uuid'         => $visitor_event['uuid'],
                'visitor_uuid' => $visitor_event['visitor_uuid'],
                'flow_uuid'    => $visitor_event['flow_uuid'],
                'url'          => $visitor_event['url'],
                'referer'      => $visitor_event['referer'],
                'start'        => $visitor_event['start'],
                'end'          => $visitor_event['end'],
                'event_name'   => $visitor_event['event_name'],
                'event_type'   => $visitor_event['event_type']
            ],
            [
                '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'
            ]
        );

        // Insert event attributes
        if (!empty($visitor_event['attributes'])) {
            $values = [];
            $placeholders = [];
            $types = [];

            foreach ($visitor_event['attributes'] as $attribute) {
                $values[] = $new_doc->uuid;
                $values[] = $attribute->name;
                $values[] = $attribute->value;

                $placeholders[] = "(%s, %s, %s)";
                $types = array_merge($types, ['%s', '%s', '%s']);
            }

            $sql = "INSERT INTO $table_name_event_attributes (event_uuid, name, value) VALUES " . implode(", ", $placeholders);

            $wpdb->query($wpdb->prepare($sql, $values));
        }

        return true;
    }

    function aesirx_analytics_create_visitor_flow($visitor_uuid, $visitor_flow) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'analytics_flows';

        $wpdb->insert(
            $table_name,
            [
                'visitor_uuid'    => $visitor_uuid,
                'uuid'            => $visitor_flow['uuid'],
                'start'           => $visitor_flow['start'],
                'end'             => $visitor_flow['end'],
                'multiple_events' => $visitor_flow['multiple_events'] ? 1 : 0
            ],
            [
                '%s', '%s', '%s', '%s', '%d'
            ]
        );

        return true;
    }

    function aesirx_analytics_mark_visitor_flow_as_multiple($visitor_flow_uuid) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'visitor';

        // Ensure UUID is properly formatted for the database
        $uuid_str = (string)$uuid;

        // Prepare the SQL query to update the `multiple_events` field
        $sql = $wpdb->prepare(
            "UPDATE $table_name
            SET visitor_flows = JSON_SET(visitor_flows, CONCAT('$[', JSON_UNQUOTE(JSON_SEARCH(visitor_flows, 'one', %s)), '].multiple_events'), true)
            WHERE JSON_CONTAINS(visitor_flows, JSON_OBJECT('uuid', %s))",
            $uuid_str, $uuid_str
        );

        // Execute the query
        $wpdb->query($sql);
    }

    function aesirx_analytics_add_consent_filters($params, &$where_clause) {
        foreach ([$params['filter'], $params['filter_not']] as $filter_array) {
            if (empty($filter_array)) {
                continue;
            }

            foreach ($filter_array as $key => $vals) {
                $list = is_array($vals) ? $vals : [$vals];

                switch ($key) {
                    case 'start':
                        try {
                            $dates = new DateTime($list[0]);
                            foreach($dates as $date) {
                                $where_clause[] = 'visitor_consent.datetime >= ' . $date->format('Y-m-d');
                            }
                        } catch (Exception $e) {
                            return new WP_Error('validation_error', '"start" filter is not correct', ['status' => 400]);
                        }
                        break;
                    case 'end':
                        try {
                            $dates = new DateTime($list[0]);
                            $dates->modify('+1 day');
                            foreach($dates as $date) {
                                $where_clause[] = 'visitor_consent.datetime < ' . $date->format('Y-m-d');
                            }
                        } catch (Exception $e) {
                            return new WP_Error('validation_error', '"end" filter is not correct', ['status' => 400]);
                        }
                        break;
                    case 'domain':
                        $where_clause[] = 'visitors ' . ($is_not ? 'NOT ' : '') . 'IN ("' . implode(', ', $list) . '")';
                        break;
                    default:
                        break;
                }
            }
        }
    }

    function aesirx_analytics_add_conversion_filters($params, &$where_clause) {
        foreach ($params as $key => $vals) {
            $list = is_array($vals) ? $vals : [$vals];

            switch ($key) {
                case 'start':
                    try {
                        $date = new DateTime($list[0]);
                        $where_clause[] = '#__analytics_flows.' . $key . ' >= ' . $date->format('Y-m-d');
                    } catch (Exception $e) {
                        return new WP_Error('validation_error', '"start" filter is not correct', ['status' => 400]);
                    }
                    break;
                case 'end':
                    try {
                        $date = new DateTime($list[0]);
                        $date->modify('+1 day');
                        $where_clause[] = '#__analytics_flows.' . $key . ' < ' . $date->format('Y-m-d');
                    } catch (Exception $e) {
                        return new WP_Error('validation_error', '"end" filter is not correct', ['status' => 400]);
                    }
                    break;
                default:
                    break;
            }
        }
    }

    function aesirx_analytics_find_wallet($network, $address) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'analytics_wallet';

        $sql = $wpdb->prepare("SELECT * FROM $table_name WHERE network = %s AND address = %s", $network, $address);
        $wallet = $wpdb->get_row($sql);
    }

    function aesirx_analytics_add_wallet($uuid, $network, $address, $nonce) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'analytics_wallet';

        $wpdb->insert(
            $table_name,
            [
                'uuid'     => $uuid,
                'network'  => $network,
                'address'  => $address,
                'nonce'    => $nonce
            ],
            [
                '%s', '%s', '%s', '%s'
            ]
        );
    }

    function aesirx_analytics_update_nonce($network, $address, $nonce) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'analytics_wallet';

        $sql = $wpdb->prepare("UPDATE $table_name SET nonce = %s WHERE network = %s AND address = %s", $nonce, $network, $address);
        $wpdb->query($sql);
    }

    function add_consent($uuid, $consent, $datetime, $web3id = null, $wallet_uuid = null, $expiration = null) {
        global $wpdb;

        // Prepare the base query and values
        $query = "INSERT INTO {$wpdb->prefix}analytics_consent (uuid, consent, datetime";
        $values = "VALUES (%s, %s, %s";
        $bind = [
            sanitize_text_field($uuid),
            sanitize_text_field($consent),
            date('Y-m-d H:i:s'),
        ];

        // Conditionally add wallet_uuid
        if (!empty($wallet_uuid)) {
            $query .= ", wallet_uuid";
            $values .= ", %s";
            $bind[] = sanitize_text_field($wallet_uuid);
        }

        // Conditionally add web3id
        if (!empty($web3id)) {
            $query .= ", web3id";
            $values .= ", %s";
            $bind[] = sanitize_text_field($web3id);
        }

        // Conditionally add expiration
        if (!empty($expiration)) {
            $query .= ", expiration";
            $values .= ", %s";
            $bind[] = $expiration;
        }

        // Complete the query
        $query .= ") " . $values . ")";
        $prepared_query = $wpdb->prepare($query, $bind);

        // Execute the query
        try {
            $result = $wpdb->query($prepared_query);
            if ($result === false) {
                return new WP_Error($wpdb->last_error);
            }
            return true;
        } catch (Exception $e) {
            error_log($e->getMessage());
            return new WP_Error('db_insert_error', __('There was a problem inserting the data into the database.'), $e->getMessage());
        }
    }

    function add_visitor_consent($visitor_uuid, $consent_uuid = null, $consent = null, $datetime = null, $expiration = null) {
        global $wpdb;

        $table = $wpdb->prefix . 'analytics_visitor_consent';

        $bind = [];
        $columns = ['uuid', 'visitor_uuid'];
        $values = ['%s', '%s'];

        $bind[] = wp_generate_uuid4();
        $bind[] = $visitor_uuid;

        if (!empty($consent_uuid)) {
            $columns[] = 'consent_uuid';
            $values[] = '%s';
            $bind[] = $consent_uuid;
        }

        if (!empty($consent)) {
            $columns[] = 'consent';
            $values[] = '%d';
            $bind[] = $consent;
        }

        if (!empty($datetime)) {
            $columns[] = 'datetime';
            $values[] = '%s';
            $bind[] = $datetime;
        }

        if (!empty($expiration)) {
            $columns[] = 'expiration';
            $values[] = '%s';
            $bind[] = $expiration;
        }

        $query = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $table,
            implode(', ', $columns),
            implode(', ', $values)
        );

        $prepared_query = $wpdb->prepare($query, $bind);

        $result = $wpdb->query($prepared_query);

        if ($result === false) {
            return new WP_Error('db_insert_error', 'Could not insert consent', ['status' => 500]);
        }

        return true;
    }

    function aesirx_analytics_list_consent_common() {
        $list = new OutgoingConsentList();
        $list_visitors = [];
        $list_flows = [];

        // Assuming $flows is an array of flow data
        foreach ($flows as $flow) {
            $visitor_uuid = $flow['visitor_uuid'];
            $visitor_vec = isset($list_flows[$visitor_uuid]) ? $list_flows[$visitor_uuid] : [];
            $visitor_vec[] = new VisitorFlow(
                parse_uuid($flow['uuid']),
                parse_datetime($flow['start']),
                parse_datetime($flow['end']),
                $flow['multiple_events']
            );
            $list_flows[$visitor_uuid] = $visitor_vec;
        }

        // Assuming $visitors is an array of visitor data
        foreach ($visitors as $visitor) {
            $consent_uuid = $visitor['consent_uuid'];
            $visitor_vec = isset($list_visitors[$consent_uuid]) ? $list_visitors[$consent_uuid] : [];
            $geo_created_at = isset($visitor['geo_created_at']) ? parse_datetime($visitor['geo_created_at']) : null;
            $visitor_vec[] = [
                'fingerprint' => isset($visitor['fingerprint']) ? $visitor['fingerprint'] : null,
                'uuid' => parse_uuid($visitor['uuid']),
                'ip' => $visitor['ip'],
                'user_agent' => $visitor['user_agent'],
                'device' => $visitor['device'],
                'browser_name' => $visitor['browser_name'],
                'browser_version' => $visitor['browser_version'],
                'domain' => $visitor['domain'],
                'lang' => $visitor['lang'],
                'visitor_flows' => isset($list_flows[$visitor['uuid']]) ? $list_flows[$visitor['uuid']] : null,
                'geo' => $geo_created_at ? new VisitorGeo(
                    $visitor['code'] ?? null,
                    $visitor['name'] ?? null,
                    $visitor['city'] ?? null,
                    $visitor['region'] ?? null,
                    $visitor['isp'] ?? null,
                    $geo_created_at
                ) : null
            ];

            $list_visitors[$consent_uuid] = $visitor_vec;
        }

        // Assuming $consents is an array of consent data
        foreach ($consents as $consent) {
            $uuid_string = $consent['uuid'];
            $outgoing_consent = new OutgoingConsent();
            $outgoing_consent->uuid = parse_uuid($uuid_string);
            $outgoing_consent->wallet_uuid = isset($consent['wallet_uuid']) ? parse_uuid($consent['wallet_uuid']) : null;
            $outgoing_consent->address = $consent['address'] ?? null;
            $outgoing_consent->network = $consent['network'] ?? null;
            $outgoing_consent->web3id = $consent['web3id'] ?? null;
            $outgoing_consent->consent = $consent['consent'];
            $outgoing_consent->datetime = parse_datetime($consent['datetime']);
            $outgoing_consent->expiration = isset($consent['expiration']) ? parse_datetime($consent['expiration']) : null;
            $outgoing_consent->visitor = isset($list_visitors[$uuid_string]) ? $list_visitors[$uuid_string] : [];

            $list->consents[] = $outgoing_consent;
        }

        $list->consents_by_domain = self::aesirx_analytics_group_consents_by_domains($list->consents);

        return $list;
    }

    function aesirx_analytics_group_consents_by_domains($consents) {
        $consent_domain_list = [];

        foreach ($consents as $consent) {
            if (empty($consent['visitor'])) {
                continue;
            }

            $visitor_domain = isset($consent['visitor'][0]['domain']) ? $consent['visitor'][0]['domain'] : null;
            if ($visitor_domain === null) {
                continue;
            }

            if (!isset($consent_domain_list[$visitor_domain])) {
                $consent_domain_list[$visitor_domain] = [];
            }

            $consent_domain_list[$visitor_domain][] = [
                'uuid' => $consent['uuid'],
                'wallet_uuid' => $consent['wallet_uuid'],
                'address' => $consent['address'],
                'network' => $consent['network'],
                'web3id' => $consent['web3id'],
                'consent' => $consent['consent'],
                'datetime' => $consent['datetime'],
                'expiration' => $consent['expiration']
            ];
        }

        $consents_by_domain = [];

        foreach ($consent_domain_list as $domain => $domain_consents) {
            $consents_by_domain[] = [
                'domain' => $domain,
                'consents' => $domain_consents
            ];
        }

        return $consents_by_domain;

    }

    function validate_signature($decoded) {
        return true;
    }

    function validate_network($network, $wallet_address, $nonce, $decoded, $jwt_payload, $version) {
        return true;
    }

    function expired_consent($consent_uuid, $expiration) {
        global $wpdb;

        // Prepare the query
        $query = "UPDATE {$wpdb->prefix}analytics_consent SET expiration = %s WHERE uuid = %s";
        
        // Format the expiration date if it is set
        $expiration_formatted = $expiration ? $expiration : null;

        // Prepare and execute the query
        $prepared_query = $wpdb->prepare($query, $expiration_formatted, sanitize_text_field($consent_uuid));

        try {
            $result = $wpdb->query($prepared_query);
            if ($result === false) {
                return new WP_Error($wpdb->last_error);
            }
            return true;
        } catch (Exception $e) {
            error_log($e->getMessage());
            return new WP_Error('db_update_error', __('There was a problem updating the data in the database.'), $e->getMessage());
        }
    }

    function find_visitor_by_uuid($uuid) {
        global $wpdb;
        // Prepare the SQL queries
        $sql_visitor = $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}analytics_visitors WHERE uuid = %s",
            $uuid
        );
        $sql_flows = $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}analytics_flows WHERE visitor_uuid = %s ORDER BY id",
            $uuid
        );

        try {
            // Execute the queries
            $visitor_result = $wpdb->get_row($sql_visitor);
            $flows_result = $wpdb->get_results($sql_flows);

            if ($visitor_result) {
                // Create the visitor object
                $visitor = (object)[
                    'fingerprint' => $visitor_result->fingerprint,
                    'uuid' => $visitor_result->uuid,
                    'ip' => $visitor_result->ip,
                    'user_agent' => $visitor_result->user_agent,
                    'device' => $visitor_result->device,
                    'browser_name' => $visitor_result->browser_name,
                    'browser_version' => $visitor_result->browser_version,
                    'domain' => $visitor_result->domain,
                    'lang' => $visitor_result->lang,
                    'visitor_flows' => null,
                    'geo' => null,
                    'visitor_consents' => [],
                ];

                if ($visitor_result->geo_created_at) {
                    $visitor->geo = (object)[
                        'country' => (object)[
                            'name' => $visitor_result->country_name,
                            'code' => $visitor_result->country_code,
                        ],
                        'city' => $visitor_result->city,
                        'region' => $visitor_result->region,
                        'isp' => $visitor_result->isp,
                        'created_at' => $visitor_result->geo_created_at,
                    ];
                }

                if (!empty($flows_result)) {
                    $visitor_flows = array_map(function($flow) {
                        return (object)[
                            'uuid' => $flow->uuid,
                            'start' => $flow->start,
                            'end' => $flow->end,
                            'multiple_events' => $flow->multiple_events,
                        ];
                    }, $flows_result);

                    $visitor->visitor_flows = $visitor_flows;
                }

                return $visitor;
            } else {
                return null;
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
            return new WP_Error('db_query_error', __('There was a problem with the database query.'), $e->getMessage());
        }
    }
}