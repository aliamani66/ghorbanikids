<?php
/**
 * Homepage Layout, Container & Hero Styling Module for GhorbaniKids
 */

if (!defined('ABSPATH')) {
    exit;
}

class GK_Homepage {

    public static function init() {
        $instance = new self();
        add_action('wp_enqueue_scripts', [$instance, 'enqueue_assets'], 10);
    }

    public function enqueue_assets() {
        $assets_url = plugins_url('assets', dirname(__FILE__));
        if (is_front_page() || is_home() || is_page('daycare-home')) {
            wp_enqueue_style('gk-homepage', $assets_url . '/css/gk-homepage.css', [], '6.0.0');
        }
    }
}