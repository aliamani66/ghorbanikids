<?php
/**
 * Plugin Name: Ghorbani Kids Assessments Loader
 * Description: Loads the modular assessments plugin from subdirectory.
 */
if (!defined('ABSPATH')) exit;

$plugin_path = WPMU_PLUGIN_DIR . '/ghorbanikids-assessments/ghorbanikids-assessments.php';
if (file_exists($plugin_path)) {
    require_once $plugin_path;
}