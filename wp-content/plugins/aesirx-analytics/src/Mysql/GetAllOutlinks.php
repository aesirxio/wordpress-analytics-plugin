<?php


use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Get_All_Outlinks extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        global $wpdb;

        $where_clause = ["#__analytics_events.referer LIKE '%//%'"];

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

        self::aesirx_analytics_add_filters($params, $where_clause);

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

        $sql = str_replace("#__", "wp_", $sql);
        $total_sql = str_replace("#__", "wp_", $total_sql);

        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 20;
        $skip = ($page - 1) * $pageSize;

        $sql .= " LIMIT " . $skip . ", " . $pageSize;

        $total_elements = (int) $wpdb->get_var($total_sql);
        $total_pages = ceil($total_elements / $pageSize);

        $list = $wpdb->get_results($sql, ARRAY_A);

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

            $query = str_replace("#__", "wp_", $query);

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
