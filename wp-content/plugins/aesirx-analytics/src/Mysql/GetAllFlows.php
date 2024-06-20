<?php

// namespace AesirxAnalytics\Mysql;

use AesirxAnalytics\MysqlHelper;

Class AesirX_Analytics_Get_All_Flows extends MysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        // let mut where_clause: Vec<String> = vec![];
        // let mut where_clause_event: Vec<String> = vec![];
        // let mut bind: Vec<String> = vec![];
        // let mut bind_event: Vec<String> = vec![];
        // let mut detail_page = false;
        // add_filters(params, &mut where_clause, &mut bind)?;

        // for (filter, is_not) in [(&params.filter, false), (&params.filter_not, true)] {
        //     if filter.is_none() {
        //         continue;
        //     }

        //     for (key, val) in filter.clone().unwrap().iter() {
        //         match val {
        //             ParameterValue::Primitive(_) | ParameterValue::Array(_) => {
        //                 match get_parameters_as_array(val) {
        //                     None => {}
        //                     Some(list) => {
        //                         if key.as_str() == "flow_uuid" {
        //                             where_clause.push(format!(
        //                                 "#__analytics_flows.uuid {} IN ({})",
        //                                 if is_not { "NOT" } else { "" },
        //                                 format_args!("?{}", ", ?".repeat(list.len() - 1))
        //                             ));
        //                             bind.extend(list.clone());
        //                             detail_page = true;
        //                         }

        //                         if key.as_str() == "event_type" {
        //                             where_clause_event.push(format!(
        //                                 "#__analytics_events.event_type {} IN ({})",
        //                                 if is_not { "NOT" } else { "" },
        //                                 format_args!("?{}", ", ?".repeat(list.len() - 1))
        //                             ));
        //                             bind_event.extend(list.clone());
        //                         }

        //                         if key.as_str() == "event_name" {
        //                             where_clause_event.push(format!(
        //                                 "#__analytics_events.event_name {} IN ({})",
        //                                 if is_not { "NOT" } else { "" },
        //                                 format_args!("?{}", ", ?".repeat(list.len() - 1))
        //                             ));
        //                             bind_event.extend(list.clone());
        //                         }
        //                     }
        //                 }
        //             }
        //             _ => {}
        //         }
        //     }
        // }

        // let total_sql: Vec<String> = vec![
        //     "SELECT COUNT(DISTINCT #__analytics_flows.uuid) as total".to_string(),
        //     "from `#__analytics_flows`".to_string(),
        //     "left join `#__analytics_visitors` on #__analytics_visitors.uuid = #__analytics_flows.visitor_uuid".to_string(),
        //     "left join `#__analytics_events` on #__analytics_events.flow_uuid = #__analytics_flows.uuid".to_string(),
        //     "WHERE".to_string(),
        //     where_clause.join(" AND "),
        // ];

        // let mut sql: Vec<String> = vec![
        //     "SELECT #__analytics_flows.*, ip, user_agent, device, browser_name, browser_name, browser_version, domain, lang, city, isp, country_name, country_code, geo_created_at, #__analytics_visitors.uuid AS visitor_uuid, ".to_string(),
        //     "COUNT(DISTINCT #__analytics_events.uuid) AS action, ".to_string(),
        //     "CAST(SUM(CASE WHEN #__analytics_events.event_type = 'conversion' THEN 1 ELSE 0 END) as INT) AS conversion, ".to_string(),
        //     "CAST(SUM(CASE WHEN #__analytics_events.event_name = 'visit' THEN 1 ELSE 0 END) as INT) AS pageview, ".to_string(),
        //     "CAST(SUM(CASE WHEN #__analytics_events.event_name != 'visit' THEN 1 ELSE 0 END) as INT) AS event, ".to_string(),
        //     "MAX(CASE WHEN #__analytics_event_attributes.name = 'sop_id' THEN #__analytics_event_attributes.value ELSE NULL END) AS sop_id, ".to_string(),
        //     "TIMESTAMPDIFF(SECOND, #__analytics_flows.start, #__analytics_flows.end) AS duration, ".to_string(),
        //     "#__analytics_events.url AS url, ".to_string(),
        //     "CAST(
        //         SUM(CASE WHEN #__analytics_events.event_name = 'visit' THEN 1 ELSE 0 END) * 2 +
        //         SUM(CASE WHEN #__analytics_events.event_name != 'visit' THEN 1 ELSE 0 END) * 5 +
        //         SUM(CASE WHEN #__analytics_events.event_type = 'conversion' THEN 1 ELSE 0 END) * 10
        //     as FLOAT) AS ux_percent, ".to_string(),
        //     "CAST(SUM(CASE WHEN #__analytics_events.event_name = 'visit' THEN 1 ELSE 0 END) * 2 as FLOAT) AS visit_actions, ".to_string(),
        //     "CAST(SUM(CASE WHEN #__analytics_events.event_name != 'visit' THEN 1 ELSE 0 END) * 5 as FLOAT) AS event_actions, ".to_string(),
        //     "CAST(SUM(CASE WHEN #__analytics_events.event_type = 'conversion' THEN 1 ELSE 0 END) * 10 as FLOAT) AS conversion_actions ".to_string(),
        //     "from `#__analytics_flows`".to_string(),
        //     "left join `#__analytics_visitors` on #__analytics_visitors.uuid = #__analytics_flows.visitor_uuid".to_string(),
        //     "left join `#__analytics_events` on #__analytics_events.flow_uuid = #__analytics_flows.uuid".to_string(),
        //     "left join `#__analytics_event_attributes` on #__analytics_events.uuid = #__analytics_event_attributes.event_uuid".to_string(),
        //     "WHERE".to_string(),
        //     where_clause.join(" AND "),
        //     "GROUP BY #__analytics_flows.uuid".to_string(),
        // ];

        // let mut sort = add_sort(
        //     params,
        //     vec![
        //         "start",
        //         "end",
        //         "geo.country.name",
        //         "geo.country.code",
        //         "ip",
        //         "device",
        //         "browser_name",
        //         "browser_version",
        //         "domain",
        //         "lang",
        //         "action",
        //         "event",
        //         "conversion",
        //         "url",
        //         "ux_percent",
        //         "pageview",
        //         "bounce_rate",
        //         "sop_id",
        //         "duration",
        //     ],
        //     "start",
        // );

        // sort.push("id ASC".to_string());
        // sql.push("ORDER BY".to_string());
        // sql.push(sort.join(","));

        // let list = self
        //     .get_list::<OutgoingMysqlListFlow>(sql, total_sql, bind.clone(), params)
        //     .await?;
        // let mut collection: Vec<OutgoingListFlow> = vec![];
        // let mut hash_map: HashMap<String, HashMap<String, VisitorEvent>> = HashMap::new();

        // if !list.collection.is_empty() {
        //     if params.with.is_some() {
        //         let with = params.with.as_ref().unwrap();
        //         if with.contains(&"events".to_string()) {
        //             let bind: Vec<String> =
        //                 list.collection.iter().map(|e| e.uuid.clone()).collect();
        //             let mut sql: Vec<String> = vec![
        //                 "SELECT *".to_string(),
        //                 "from #__analytics_events".to_string(),
        //                 "WHERE".to_string(),
        //                 format!(
        //                     "flow_uuid IN ({})",
        //                     format_args!("?{}", ", ?".repeat(list.collection.len() - 1))
        //                 ),
        //             ];

        //             if !where_clause_event.is_empty() {
        //                 sql.push("AND".to_string());
        //                 sql.push(where_clause_event.join(" AND "));
        //             }

        //             let sql_list_string = self.prefix(sql.clone().join(" "));
        //             let mut sql_list = sqlx::query_as::<_, MysqlVisitorEvent>(&sql_list_string);
        //             for one_bind in bind.iter() {
        //                 sql_list = sql_list.bind(one_bind);
        //             }
        //             if !bind_event.is_empty() {
        //                 for one_bind in bind_event.iter() {
        //                     sql_list = sql_list.bind(one_bind);
        //                 }
        //             }
        //             let events = sql_list.fetch_all(&self.sqlx_conn);

        //             let mut sql: Vec<String> = vec![
        //                 "SELECT *".to_string(),
        //                 "from #__analytics_event_attributes".to_string(),
        //                 "LEFT JOIN #__analytics_events on #__analytics_events.uuid = #__analytics_event_attributes.event_uuid".to_string(),
        //                 "WHERE".to_string(),
        //                 format!(
        //                     "#__analytics_events.flow_uuid IN ({})",
        //                     format_args!("?{}", ", ?".repeat(list.collection.len() - 1))
        //                 ),
        //             ];

        //             if !where_clause_event.is_empty() {
        //                 sql.push("AND".to_string());
        //                 sql.push(where_clause_event.join(" AND "));
        //             }

        //             let sql_list_string = self.prefix(sql.clone().join(" "));
        //             let mut sql_list =
        //                 sqlx::query_as::<_, MysqlVisitorEventAttribute>(&sql_list_string);
        //             for one_bind in bind.iter() {
        //                 sql_list = sql_list.bind(one_bind);
        //             }
        //             if !bind_event.is_empty() {
        //                 for one_bind in bind_event.iter() {
        //                     sql_list = sql_list.bind(one_bind);
        //                 }
        //             }
        //             let attributes = sql_list.fetch_all(&self.sqlx_conn);
        //             let mut hash_attributes: HashMap<String, Vec<VisitorEventAttribute>> =
        //                 HashMap::new();

        //             match try_join!(events, attributes) {
        //                 Ok((events, attributes)) => {
        //                     for second in attributes.iter() {
        //                         let attr = VisitorEventAttribute {
        //                             name: second.name.clone(),
        //                             value: second.value.clone(),
        //                         };
        //                         match hash_attributes.get_mut(second.event_uuid.clone().as_str()) {
        //                             None => {
        //                                 hash_attributes
        //                                     .insert(second.event_uuid.clone(), vec![attr]);
        //                             }
        //                             Some(some) => {
        //                                 some.push(attr);
        //                             }
        //                         };
        //                     }
        //                     for second in events.iter() {
        //                         let mut og_title: Option<String> = None;
        //                         let mut og_description: Option<String> = None;
        //                         let mut og_image: Option<String> = None;

        //                         if detail_page && !second.url.clone().is_empty() {
        //                             // // Try to fetch and parse the Open Graph data
        //                             let og_data = match Webpage::from_url(
        //                                 second.url.clone().as_str(),
        //                                 WebpageOptions::default(),
        //                             ) {
        //                                 Ok(page) => {
        //                                     let mut og_data: HashMap<String, String> =
        //                                         HashMap::new();

        //                                     if let Some(title) = page.html.title {
        //                                         og_data.insert("og:title".to_string(), title);
        //                                     }
        //                                     if let Some(description) = page.html.description {
        //                                         og_data.insert(
        //                                             "og:description".to_string(),
        //                                             description,
        //                                         );
        //                                     }
        //                                     if let Some(image) = page.html.meta.get("og:image") {
        //                                         og_data.insert(
        //                                             "og:image".to_string(),
        //                                             image.to_string(),
        //                                         );
        //                                     }

        //                                     Some(og_data)
        //                                 }
        //                                 Err(e) => {
        //                                     error!("Failed to fetch the page: {}", e);
        //                                     None
        //                                 }
        //                             };

        //                             if let Some(data) = og_data {
        //                                 og_title = data.get("og:title").map(|s| s.to_string());
        //                                 og_description =
        //                                     data.get("og:description").map(|s| s.to_string());
        //                                 og_image = data.get("og:image").map(|s| s.to_string());
        //                             }
        //                         }

        //                         let visitor_event = VisitorEvent {
        //                             uuid: Uuid::parse_str(second.uuid.clone())?,
        //                             visitor_uuid: Uuid::parse_str(second.visitor_uuid.clone())?,
        //                             flow_uuid: Uuid::parse_str(second.flow_uuid.clone())?,
        //                             url: second.url.clone(),
        //                             referer: second.referer.clone(),
        //                             start: Utc.from_utc_datetime(&second.start),
        //                             end: Utc.from_utc_datetime(&second.end),
        //                             event_name: second.event_name.clone(),
        //                             event_type: second.event_type.clone(),
        //                             attributes: hash_attributes.get(&second.uuid).cloned(),
        //                             og_title,
        //                             og_description,
        //                             og_image,
        //                         };
        //                         match hash_map.get_mut(second.flow_uuid.clone().as_str()) {
        //                             None => {
        //                                 let mut sub_hash: HashMap<String, VisitorEvent> =
        //                                     HashMap::new();
        //                                 sub_hash.insert(second.uuid.clone(), visitor_event);
        //                                 hash_map.insert(second.flow_uuid.clone(), sub_hash);
        //                             }
        //                             Some(some) => {
        //                                 some.insert(second.uuid.clone(), visitor_event);
        //                             }
        //                         };
        //                     }
        //                 }
        //                 Err(e) => return Err(ApiError::from(e)),
        //             };
        //         }
        //     }

        //     for item in list.collection.iter() {
        //         if !collection.is_empty()
        //             && collection.last().unwrap().uuid.to_string() == item.uuid.clone()
        //         {
        //             continue;
        //         }

        //         collection.push(OutgoingListFlow {
        //             uuid: Uuid::parse_str(item.uuid.clone())?,
        //             visitor_uuid: Uuid::parse_str(item.visitor_uuid.clone())?,
        //             ip: item.ip.clone(),
        //             user_agent: item.user_agent.clone(),
        //             device: item.device.clone(),
        //             browser_name: item.browser_name.clone(),
        //             browser_version: item.browser_version.clone(),
        //             domain: item.domain.clone(),
        //             lang: item.lang.clone(),
        //             start: Utc.from_utc_datetime(&item.start),
        //             end: Utc.from_utc_datetime(&item.end),
        //             geo: item.geo_created_at.map(|e| OutgoingListVisitorVisitorGeo {
        //                 country: VisitorGeoCountry {
        //                     name: item.country_name.clone(),
        //                     code: item.country_code.clone(),
        //                 },
        //                 city: item.city.clone(),
        //                 isp: item.isp.clone(),
        //                 created_at: Utc.from_utc_datetime(&e),
        //             }),
        //             events: match hash_map.get(&item.uuid) {
        //                 None => None,
        //                 Some(some) => {
        //                     let mut events: Vec<VisitorEvent> = vec![];
        //                     for (_, val) in some.iter() {
        //                         events.push(val.clone());
        //                     }

        //                     Some(events)
        //                 }
        //             },
        //             duration: item.duration,
        //             action: item.action,
        //             event: item.event,
        //             conversion: item.conversion,
        //             url: item.url.clone(),
        //             ux_percent: item.ux_percent,
        //             pageview: item.pageview,
        //             sop_id: item.sop_id.clone(),
        //             visit_actions: item.visit_actions,
        //             event_actions: item.event_actions,
        //             conversion_actions: item.conversion_actions,
        //         });
        //     }
        // }

        // Ok(ListResponse {
        //     collection,
        //     page: list.page,
        //     page_size: list.page_size,
        //     total_pages: list.total_pages,
        //     total_elements: list.total_elements,
        // })
    }
}
