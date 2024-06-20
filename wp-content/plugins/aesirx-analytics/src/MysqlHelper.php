<?php

namespace AesirxAnalytics;

Class MysqlHelper
{
    public function get_list($sql, $total_sql, $params) {
        global $wpdb; // WordPress database object
    
        // Calculate the LIMIT clause
        $sql .= " LIMIT 0, 5";
    
        // // Join SQL strings with spaces and apply prefixing (if needed)
        // $sql_list_string = implode(" ", $sql);
        // $total_sql_string = implode(" ", $total_sql);
    
        // try {
        //     // Execute list query
        //     $list = $wpdb->get_results($sql_list_string);
    
        //     // Execute total query
        //     $total_result = $wpdb->get_row($total_sql_string);
    
        //     // Extract total elements
        //     $total_elements = isset($total_result->total) ? intval($total_result->total) : 0;
        //     $total_pages = 1;
        //     $limit = $params->calcPageSize();
    
        //     // Calculate total pages
        //     if ($limit > 0) {
        //         $total_pages = ceil($total_elements / $limit);
        //     }
    
        //     // Prepare response array
        //     $response = [
        //         'collection' => $list,
        //         'page' => $params->calcPage(),
        //         'pageSize' => $params->calcPageSize(),
        //         'totalPages' => $total_pages,
        //         'totalElements' => $total_elements
        //     ];
    
        //     return $response;
    
        // } catch (Exception $e) {
        //     // Handle exceptions (customize as per your needs)
        //     error_log("Error: " . $e->getMessage());
        //     return []; // Or handle differently based on your plugin's requirements
        // }

        $sql = str_replace("#__", "wp_", $sql);

        // echo $sql;

        $collection = $wpdb->get_results($sql, ARRAY_A);

        // Execute queries
        try {
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

            if (count($list_response['collection']) == 1) {
                $list_response = $list_response['collection'][0];
            }

            return $list_response;

        } catch (Exception $e) {
            // Handle exceptions
            error_log("Error: " . $e->getMessage());
            return new WP_Error('database_error', 'Database error occurred.', ['status' => 500]);
        }
    }

    // Define a function to mimic the Rust method
    public function get_statistics_per_field_wp($groups = [], $selects = [], $params = []) {
        global $wpdb; // WordPress database object

        // Initialize SQL parts
        $select = [
            "coalesce(COUNT(DISTINCT (#__analytics_events.visitor_uuid)), 0) as number_of_visitors",
            "coalesce(COUNT(#__analytics_events.visitor_uuid), 0) as total_number_of_visitors",
            "COUNT(#__analytics_events.uuid) as number_of_page_views",
            "COUNT(DISTINCT (#__analytics_events.url)) AS number_of_unique_page_views",
            "coalesce(SUM(TIMESTAMPDIFF(SECOND, #__analytics_events.start, #__analytics_events.end)) / count(distinct #__analytics_visitors.uuid), 0) DIV 1 as average_session_duration",
            "coalesce((COUNT(#__analytics_events.uuid) / COUNT(DISTINCT (#__analytics_events.flow_uuid))), 0) DIV 1 as average_number_of_pages_per_session",
            "coalesce((count(DISTINCT CASE WHEN #__analytics_flows.multiple_events = 0 THEN #__analytics_flows.uuid END) * 100) / count(DISTINCT (#__analytics_flows.uuid)), 0) DIV 1 as bounce_rate",
        ];

        $total_select = ["COUNT(uuid) AS total"];

        // Handle grouping
        if (!empty($groups)) {
            foreach ($groups as $one_group) {
                $select[] = $one_group;
            }

            // $total_select[] = "COUNT(DISTINCT " . implode(', COALESCE(', $groups->result) . ") AS total";
        }
        // else {
        //     $total_select = ["COUNT(uuid) AS total"];
        // }

        // Handle additional select fields
        foreach ($selects as $additional_result) {
            $select[] = $additional_result["select"] . " AS " . $additional_result["result"];
        }

        // // Handle where clause and bindings
        // $where_clause = [
        //     "event_name = %s",
        //     "event_type = %s",
        // ];
        // $bind = ["visit", "action"];

        // // Function to add filters (assuming it's defined elsewhere)
        // add_filters($params, $where_clause, $bind);

        // // Handle acquisition filter
        // $acquisition = false;
        // foreach ($params->filter as $key => $vals) {
        //     if ($key === "acquisition") {
        //         $list = get_parameters_as_array($vals);
        //         if ($list[0] === "true") {
        //             $acquisition = true;
        //         }
        //         break;
        //     }
        // }

        // if ($acquisition) {
        //     $where_clause[] = "flows.multiple_events = 0";
        // }

        // Build SQL queries
        $total_sql = "SELECT " . implode(", ", $total_select) . " FROM #__analytics_events
                    LEFT JOIN #__analytics_visitors ON #__analytics_visitors.uuid = #__analytics_events.visitor_uuid
                    LEFT JOIN #__analytics_flows ON #__analytics_flows.uuid = #__analytics_events.flow_uuid";
                    // WHERE " . implode(" AND ", $where_clause);

        $sql = "SELECT " . implode(", ", $select) . " FROM #__analytics_events
                LEFT JOIN #__analytics_visitors ON #__analytics_visitors.uuid = #__analytics_events.visitor_uuid
                LEFT JOIN #__analytics_flows ON #__analytics_flows.uuid = #__analytics_events.flow_uuid";
                // WHERE " . implode(" AND ", $where_clause);

        // Handle grouping
        if (!empty($groups)) {
            $sql .= " GROUP BY " . implode(", ", $groups);
        }

        // Handle sorting
        // $allowed = [
        //     "number_of_visitors",
        //     "number_of_page_views",
        //     "number_of_unique_page_views",
        //     "average_session_duration",
        //     "average_number_of_pages_per_session",
        //     "bounce_rate",
        // ];
        // $default = reset($allowed); // Get the first element

        // foreach ($groups as $one_group) {
        //     $allowed[] = $one_group->result;
        //     $default = $one_group->result;
        // }

        // foreach ($group_field->get_select() as $additional_result) {
        //     $allowed[] = $additional_result->result;
        // }

        // $sort = add_sort($params, $allowed, $default);
        // if (!empty($sort)) {
        //     $sql .= " ORDER BY " . implode(", ", $sort);
        // }

        // $this.get_list($sql, $total_sql, $params);
        return self::get_list($sql, $total_sql, $params);
    }

    function add_filters($params, &$where_clause, &$bind) {
        // Iterate through filters and filter_not arrays
        foreach ([$params['filter'], $params['filter_not']] as $filter_array) {
            // Skip iteration if current filter array is empty or not set
            if (empty($filter_array)) {
                continue;
            }
    
            // Iterate through each filter key-value pair
            foreach ($filter_array as $key => $vals) {
                // Attempt to retrieve filter values as array
                $list = get_parameters_as_array($vals);
    
                // Handle different filter keys
                switch ($key) {
                    case 'start':
                        // Validate and handle start date filter
                        try {
                            $date = new DateTime($list[0]);
                            $where_clause[] = '#__analytics_events.' . $key . ' >= ?';
                            $bind[] = $date->format('Y-m-d');
                        } catch (Exception $e) {
                            return new WP_Error('validation_error', '"start" filter is not correct', ['status' => 400]);
                        }
                        break;
                    case 'end':
                        // Validate and handle end date filter
                        try {
                            $date = new DateTime($list[0]);
                            $date->modify('+1 day'); // Add one day to include end date
                            $where_clause[] = '#__analytics_events.' . $key . ' < ?';
                            $bind[] = $date->format('Y-m-d');
                        } catch (Exception $e) {
                            return new WP_Error('validation_error', '"end" filter is not correct', ['status' => 400]);
                        }
                        break;
                    case 'url':
                    case 'domain':
                    case 'browser_name':
                    case 'browser_version':
                    case 'device':
                    case 'lang':
                    case 'event_name':
                    case 'event_type':
                        // Handle other filter keys for IN clause
                        $where_clause[] = '#__analytics_events.' . $key . ' ' . ($is_not ? 'NOT ' : '') . 'IN (' . rtrim(str_repeat('?, ', count($list)), ', ') . ')';
                        $bind = array_merge($bind, $list);
                        break;
                    case 'city':
                    case 'isp':
                    case 'country_code':
                    case 'country_name':
                        // Handle city, isp, country_code, country_name filters
                        foreach ($list as $list_val) {
                            if ($list_val === 'null') {
                                $bind[] = 'NULL';
                            } else {
                                $bind[] = $list_val;
                            }
                        }
                        break;
                    default:
                        // Handle any other custom filter keys if needed
                        break;
                }
            }
        }
    
        return null; // Success
    }
}