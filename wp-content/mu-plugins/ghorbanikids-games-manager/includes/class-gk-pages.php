<?php
/**
 * Informational Pages Module (About Us, Contact Us, Latest News / Blog) for GhorbaniKids
 */

if (!defined('ABSPATH')) {
    exit;
}

class GK_Pages {

    public static function init() {
        $instance = new self();
        add_action('wp_enqueue_scripts', [$instance, 'enqueue_assets'], 10);
    }

    public function enqueue_assets() {
        $assets_url = plugins_url('assets', dirname(__FILE__));
        if (is_page(['about-us', 'contact-us', 'latest-news', 'resources', 'our-classes']) || is_singular('post') || is_home()) {
            wp_enqueue_style('gk-pages', $assets_url . '/css/gk-pages.css', [], GK_GAMES_MANAGER_VERSION);
        }
    }
}