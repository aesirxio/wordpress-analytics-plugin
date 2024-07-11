<?php


use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Get_All_Outlinks extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        global $wpdb;

        $where_clause = ["#__analytics_events.referer LIKE '%//%'"];
        $bind = [];

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
            $where_clause[] = "#__analytics_events.referer LIKE '%google.%'";
            $where_clause[] = "#__analytics_events.referer LIKE '%bing.%'";
            $where_clause[] = "#__analytics_events.referer LIKE '%yandex.%'";
            $where_clause[] = "#__analytics_events.referer LIKE '%yahoo.%'";
            $where_clause[] = "#__analytics_events.referer LIKE '%duckduckgo.%'";
        }

        parent::aesirx_analytics_add_filters($params, $where_clause, $bind);

        $sql =
            "SELECT
            SUBSTRING_INDEX(SUBSTRING_INDEX(referer, '://', -1), '/', 1) AS referer,
            COUNT(#__analytics_events.visitor_uuid) as total_number_of_visitors,
            COUNT(DISTINCT #__analytics_events.visitor_uuid) as number_of_visitors,
            COUNT(referer) as total_urls
            from `#__analytics_events`
            left join `#__analytics_visitors` on #__analytics_visitors.uuid = #__analytics_events.visitor_uuid
            WHERE " . implode(" AND ", $where_clause) .
            " GROUP BY referer";

        $total_sql =
            "SELECT
            COUNT(DISTINCT SUBSTRING_INDEX(SUBSTRING_INDEX(referer, '://', -1), '/', 1)) as total
            from `#__analytics_events`
            left join `#__analytics_visitors` on #__analytics_visitors.uuid = #__analytics_events.visitor_uuid
            WHERE " . implode(" AND ", $where_clause);

        $sort = self::aesirx_analytics_add_sort(
            $params,
            [
                "referer",
                "number_of_visitors",
                "total_number_of_visitors",
                "urls",
                "total_urls",
            ],
            "referer"
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
            $query = 
                "SELECT 
                #__analytics_events.referer AS url, 
                COUNT(#__analytics_events.visitor_uuid) as total_number_of_visitors, 
                COUNT(DISTINCT #__analytics_events.visitor_uuid) as number_of_visitors 
                from #__analytics_events
                left join #__analytics_visitors on #__analytics_visitors.uuid = #__analytics_events.visitor_uuid 
                WHERE #__analytics_events.referer LIKE '%?%'
                GROUP BY url ";

            $query = str_replace("#__", $wpdb->prefix, $query);

            foreach ($list as $vals) {

                if ($vals['referer'] == null) {
                    continue;
                }

                $query = str_replace("?", $vals['referer'], $query);

                $second = $wpdb->get_results($query, ARRAY_A);

                $collection[] = [
                    "referer" => $vals['referer'],
                    "urls" => $second,
                    "total_number_of_visitors" => $vals['total_number_of_visitors'],
                    "number_of_visitors" => $vals['number_of_visitors'],
                    "total_urls" => $vals['total_urls'],
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
