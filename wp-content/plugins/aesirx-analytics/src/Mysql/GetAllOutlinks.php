<?php

// namespace AesirxAnalytics\Mysql;

use AesirxAnalytics\MysqlHelper;

Class AesirX_Analytics_Get_All_Outlinks extends MysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        global $wpdb;

        // let mut where_clause: Vec<String> =
        //     vec!["#__analytics_events.referer LIKE '%//%'".to_string()];

        // let mut acquisition = false;

        // for (key, vals) in params.clone().filter.as_ref().unwrap().iter() {
        //     if key == "acquisition" {
        //         if let Some(list) = get_parameters_as_array(vals) {
        //             if list.first().unwrap() == "true" {
        //                 acquisition = true;
        //             }
        //         }

        //         break;
        //     }
        // }

        // if acquisition {
        //     where_clause.push("#__analytics_events.referer LIKE '%google.%'".to_string());
        //     where_clause.push("#__analytics_events.referer LIKE '%bing.%'".to_string());
        //     where_clause.push("#__analytics_events.referer LIKE '%yandex.%'".to_string());
        //     where_clause.push("#__analytics_events.referer LIKE '%yahoo.%'".to_string());
        //     where_clause.push("#__analytics_events.referer LIKE '%duckduckgo.%'".to_string());
        // }

        // let mut bind: Vec<String> = vec![];

        // add_filters(params, &mut where_clause, &mut bind)?;

        $sql =
            "SELECT
            SUBSTRING_INDEX(SUBSTRING_INDEX(referer, '://', -1), '/', 1) AS referer,
            COUNT(#__analytics_events.visitor_uuid) as total_number_of_visitors,
            COUNT(DISTINCT #__analytics_events.visitor_uuid) as number_of_visitors,
            COUNT(referer) as total_urls
            from `#__analytics_events`
            left join `#__analytics_visitors` on #__analytics_visitors.uuid = #__analytics_events.visitor_uuid
            GROUP BY referer";

        // let total_sql: Vec<String> = vec![
        //     "SELECT
        //     "COUNT(DISTINCT SUBSTRING_INDEX(SUBSTRING_INDEX(referer, '://', -1), '/', 1)) as total
        //     "from `#__analytics_events`
        //     "left join `#__analytics_visitors` on #__analytics_visitors.uuid = #__analytics_events.visitor_uuid
        //     "WHERE
        //     where_clause.join(" AND "),
        // ];

        // let sort = add_sort(
        //     params,
        //     vec![
        //         "referer",
        //         "number_of_visitors",
        //         "total_number_of_visitors",
        //         "urls",
        //         "total_urls",
        //     ],
        //     "referer",
        // );

        // if !sort.is_empty() {
        //     sql.push("ORDER BY".to_string());
        //     sql.push(sort.join(","));
        // }

        // let list = self
        //     .get_list::<OutgoingMysqlOutlinks>(sql, total_sql, bind.clone(), params)
        //     .await?;
        // let mut collection: Vec<OutgoingOutlinksSummary> = vec![];

        $sql = str_replace("#__", "wp_", $sql);

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

                // echo $query;

                $second = $wpdb->get_results($query, ARRAY_A);

                // let second = query
                //     .fetch_all(&self.sqlx_conn)
                //     .await?
                //     .iter()
                //     .map(|e| OutgoingOutlinksSummaryUrl {
                //         url: e.get("url"),
                //         total_number_of_visitors: e.get("total_number_of_visitors"),
                //         number_of_visitors: e.get("number_of_visitors"),
                //     })
                //     .collect::<Vec<OutgoingOutlinksSummaryUrl>>();

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
