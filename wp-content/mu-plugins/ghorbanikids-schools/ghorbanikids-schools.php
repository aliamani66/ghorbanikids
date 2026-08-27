<?php
/**
 * Module Name: Ghorbani Kids Schools & Kindergartens (B2B)
 * Description: Complete School/Kindergarten Management, Assessments, Game Scores & Branded Reports
 * Version: 1.0.0
 * Author: Ghorbani Kids
 */
if (!defined('ABSPATH')) exit;

define('GK_SCHOOLS_DIR', plugin_dir_path(__FILE__));
define('GK_SCHOOLS_URL', plugin_dir_url(__FILE__));

// بارگذاری فایل‌های ماژول
require_once GK_SCHOOLS_DIR . 'includes/class-school-db.php';
require_once GK_SCHOOLS_DIR . 'includes/class-school-orders.php';
require_once GK_SCHOOLS_DIR . 'includes/class-school-dashboard.php';
require_once GK_SCHOOLS_DIR . 'includes/class-teacher-portal.php';
require_once GK_SCHOOLS_DIR . 'includes/class-school-reports.php';
require_once GK_SCHOOLS_DIR . 'includes/class-school-admin.php';
require_once GK_SCHOOLS_DIR . 'includes/class-school-landing.php';
require_once GK_SCHOOLS_DIR . 'includes/class-school-qr.php';
require_once GK_SCHOOLS_DIR . 'includes/class-school-leagues.php';

if (file_exists(GK_SCHOOLS_DIR . 'includes/class-school-exams.php')) {
    require_once GK_SCHOOLS_DIR . 'includes/class-school-exams.php';
}
if (file_exists(GK_SCHOOLS_DIR . 'includes/class-school-games.php')) {
    require_once GK_SCHOOLS_DIR . 'includes/class-school-games.php';
}

add_action('plugins_loaded', function() {
    if (class_exists('GK_School_DB')) GK_School_DB::create_tables();
    if (class_exists('GK_School_Orders')) GK_School_Orders::init();
    if (class_exists('GK_School_Dashboard')) GK_School_Dashboard::init();
    if (class_exists('GK_Teacher_Portal')) GK_Teacher_Portal::init();
    if (class_exists('GK_School_Reports')) GK_School_Reports::init();
    if (class_exists('GK_School_Admin')) GK_School_Admin::init();
    if (class_exists('GK_School_Landing')) GK_School_Landing::init();
    if (class_exists('GK_School_QR')) GK_School_QR::init();
    if (class_exists('GK_School_Leagues')) GK_School_Leagues::init();
    if (class_exists('GK_School_Exams')) GK_School_Exams::init();
    if (class_exists('GK_School_Games')) GK_School_Games::init();
});