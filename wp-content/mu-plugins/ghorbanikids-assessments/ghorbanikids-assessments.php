<?php
/**
 * Plugin Name: Ghorbani Kids Assessments & Talent Discovery Module
 * Description: Modular psychological & cognitive assessments, Gardner Multiple Intelligences, ADHD screening, and learning styles.
 * Version: 1.0.3
 * Author: Ghorbani Kids
 */

if (!defined('ABSPATH')) exit;

define('GK_ASSESSMENTS_DIR', plugin_dir_path(__FILE__));
define('GK_ASSESSMENTS_URL', plugin_dir_url(__FILE__));

// Require modular components
require_once GK_ASSESSMENTS_DIR . 'includes/class-assessment-cpt.php';
require_once GK_ASSESSMENTS_DIR . 'includes/class-scoring-engine.php';
require_once GK_ASSESSMENTS_DIR . 'includes/class-report-renderer.php';
require_once GK_ASSESSMENTS_DIR . 'includes/class-account-dashboard.php';
require_once GK_ASSESSMENTS_DIR . 'includes/class-admin-manager.php';

// Initialize components
add_action('plugins_loaded', function() {
    GK_Assessment_CPT::init();
    GK_Scoring_Engine::init();
    GK_Account_Dashboard::init();
    GK_Admin_Manager::init();
});

// Disable LiteSpeed Cache and browser cache on /tests/ page so it is ALWAYS 100% real-time and fresh
add_action('template_redirect', function() {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($uri, '/tests') !== false || strpos($uri, '/assessments') !== false) {
        if (defined('LSCWP_V')) {
            do_action('litespeed_control_set_nocache', 'Disable cache for live assessments');
        }
        nocache_headers();
    }
});

// Clean redirect from /assessments/ to /tests/
add_action('template_redirect', function() {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (preg_match('#^/assessments(/.*)?$#i', $uri, $matches)) {
        $subpath = isset($matches[1]) ? $matches[1] : '';
        wp_redirect(home_url('/tests/' . ltrim($subpath, '/')), 301);
        exit;
    }
});