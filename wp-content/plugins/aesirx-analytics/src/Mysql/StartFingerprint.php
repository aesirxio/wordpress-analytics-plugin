<?php

use AesirxAnalytics\AesirxAnalyticsMysqlHelper;

Class AesirX_Analytics_Start_Fingerprint extends AesirxAnalyticsMysqlHelper
{
    function aesirx_analytics_mysql_execute($params = [])
    {
        $start = gmdate('Y-m-d H:i:s');
        $domain = parent::aesirx_analytics_validate_domain($params['request']['url']);

        if (is_wp_error($domain)) {
            return $domain;
        }

        $visitor = parent::aesirx_analytics_find_visitor_by_fingerprint_and_domain($params['request']['fingerprint'], $domain);

        if (is_wp_error($visitor)) {
            return $visitor;
        }

        if (!$visitor) {
            $new_visitor_flow = [
                'uuid' => wp_generate_uuid4(),
                'start' => $start,
                'end' => $start,
                'multiple_events' => false,
            ];
    
            $new_visitor = [
                'fingerprint' => $params['request']['fingerprint'],
                'uuid' => wp_generate_uuid4(),
                'ip' => $params['request']['ip'],
                'user_agent' => $params['request']['user_agent'],
                'device' => $params['request']['device'],
                'browser_name' => $params['request']['browser_name'],
                'browser_version' => $params['request']['browser_version'],
                'domain' => $domain,
                'lang' => $params['request']['lang'],
                'visitor_flows' => [$new_visitor_flow],
            ];
    
            $new_visitor_event = [
                'uuid' => wp_generate_uuid4(),
                'visitor_uuid' => $new_visitor['uuid'],
                'flow_uuid' => $new_visitor_flow['uuid'],
                'url' => $params['request']['url'],
                'referer' => $params['request']['referer'],
                'start' => $start,
                'end' => $start,
                'event_name' => $params['request']['event_name'] ?? 'visit',
                'event_type' => $params['request']['event_type'] ?? 'action',
                'attributes' => isset($params['request']['attributes']) ? $params['request']['attributes'] : '',
            ];
    
            parent::aesirx_analytics_create_visitor($new_visitor);
            parent::aesirx_analytics_create_visitor_event($new_visitor_event);
    
            return [
                'visitor_uuid' => $new_visitor['uuid'],
                'event_uuid' => $new_visitor_event['uuid'],
                'flow_uuid' => $new_visitor_event['flow_uuid'],
            ];
        } else {
            $url = wp_parse_url($params['request']['url']);
            if (!$url || !isset($url['host'])) {
                return new WP_Error('validation_error', esc_html__('Wrong URL format, domain not found', 'aesirx-analytics'));
            }
    
            if ($url['host'] != $visitor['domain']) {
                return new WP_Error('validation_error', esc_html__('The domain sent in the new URL does not match the domain stored in the visitor document', 'aesirx-analytics'));
            }
    
            $create_flow = true;
            $visitor_flow = [
                'uuid' => wp_generate_uuid4(),
                'start' => $start,
                'end' => $start,
                'multiple_events' => false,
            ];
            $is_already_multiple = false;
    
            if ($params['request']['referer']) {
                $referer = wp_parse_url($params['request']['referer']);

                if ($referer && $referer['host'] == $url['host'] && $visitor['visitor_flows']) {

                    $list = $visitor['visitor_flows'];
    
                    if (!empty($list)) {
                        $first = $list[0];
                        $max = $first['start'];
                        $visitor_flow['uuid'] = $first['uuid'];
                        $is_already_multiple = $first['multiple_events'];
                        $create_flow = false;

                        foreach ($list as $val) {
                            if ($max < $val['start']) {
                                $max = $val['start'];
                                $visitor_flow['uuid'] = $val['uuid'];
                            }
                        }
                    }
                }
            }
    
            if ($create_flow) {
                parent::aesirx_analytics_create_visitor_flow($visitor['uuid'], $visitor_flow);
            }
    
            $visitor_event = [
                'uuid' => wp_generate_uuid4(),
                'visitor_uuid' => $visitor['uuid'],
                'flow_uuid' => $visitor_flow['uuid'],
                'url' => $params['request']['url'],
                'referer' => $params['request']['referer'],
                'start' => $start,
                'end' => $start,
                'event_name' => $params['request']['event_name'] ?? 'visit',
                'event_type' => $params['request']['event_type'] ?? 'action',
                'attributes' => $params['request']['attributes'] ?? '',
            ];
    
            parent::aesirx_analytics_create_visitor_event($visitor_event);

            if (!$create_flow && !$is_already_multiple) {
                parent::aesirx_analytics_mark_visitor_flow_as_multiple($visitor_flow['uuid']);
            }
    
            return [
                'visitor_uuid' => $visitor['uuid'],
                'event_uuid' => $visitor_event['uuid'],
                'flow_uuid' => $visitor_event['flow_uuid'],
            ];
        }
    }
}
