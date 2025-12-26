<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
  exit();
}

$aesirx_analytics_freemium_timestamp = wp_next_scheduled('analytics_cron_geo');
wp_unschedule_event($aesirx_analytics_freemium_timestamp, 'analytics_cron_geo');
