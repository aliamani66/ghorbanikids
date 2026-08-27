<?php
/**
 * Plugin Name: Ghorbani Kids Schools & Organizations Loader
 * Description: Root loader for the Ghorbani Kids B2B School & Kindergarten Module
 * Version: 1.0.0
 * Author: Ghorbani Kids
 */
if (!defined('ABSPATH')) exit;

$main_module = WPMU_PLUGIN_DIR . '/ghorbanikids-schools/ghorbanikids-schools.php';
if (file_exists($main_module)) {
    require_once $main_module;
}