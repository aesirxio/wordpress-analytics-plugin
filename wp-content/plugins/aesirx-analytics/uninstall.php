<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
  exit();
}

$timestamp = wp_next_scheduled('analytics_cron_geo');
wp_unschedule_event($timestamp, 'analytics_cron_geo');
