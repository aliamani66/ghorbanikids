<?php
/**
 * Real-time Cache Control & Stale Cache Killer for GhorbaniKids
 */
if (!defined('ABSPATH')) exit;

class GK_Cache_Control {
    public static function init() {
        add_action('send_headers', [__CLASS__, 'send_fresh_headers'], 999);
    }

    public static function send_fresh_headers() {
        if (!is_admin()) {
            header('X-LiteSpeed-Cache-Control: no-cache');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
        }
    }
}
GK_Cache_Control::init();