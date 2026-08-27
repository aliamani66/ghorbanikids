<?php
/**
 * Plugin Name: Ghorbani Kids Games & Subscriptions Manager Loader
 * Description: Root loader for the modular Ghorbani Kids Games Manager v2
 * Version: 6.0.0
 * Author: Ghorbani Kids
 */

if (!defined('ABSPATH')) {
    exit;
}

$main_module = WPMU_PLUGIN_DIR . '/ghorbanikids-games-manager/ghorbanikids-games-manager.php';
if (file_exists($main_module) && !class_exists('GhorbaniKids_Games')) {
    require_once $main_module;
    ghorbanikids_games_manager_init();
}