<?php

namespace AesirxAnalytics;

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
}