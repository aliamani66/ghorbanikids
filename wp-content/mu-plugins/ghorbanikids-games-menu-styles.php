<?php
/**
 * Plugin Name: Ghorbani Kids Clean Typography & Global Site Styles
 * Description: Clean top navbar and completely removes bulky page title bars across the entire website
 */
if (!defined('ABSPATH')) exit;

add_action('wp_head', function() {
    ?>
    <style id="gk-global-clean-theme-styles">
        /* حذف کامل نوار عنوان قدیمی در سراسر تمام صفحات سایت */
        .fusion-page-title-bar,
        .fusion-page-title-bar-wrapper,
        #main > .fusion-page-title-bar {
            display: none !important;
            height: 0 !important;
            min-height: 0 !important;
            padding: 0 !important;
            margin: 0 !important;
            border: none !important;
            visibility: hidden !important;
        }

        /* فونت و فاصله‌بندی استاندارد منوی بالای سایت */
        .fusion-main-menu > ul > li > a,
        .fusion-main-menu .fusion-menu > li > a,
        .fusion-main-menu .fusion-top-level-link,
        .fusion-header .fusion-main-menu a {
            font-size: 14.5px !important;
            font-weight: 800 !important;
            letter-spacing: 0 !important;
            padding-left: 13px !important;
            padding-right: 13px !important;
        }

        .fusion-main-menu .sub-menu a,
        .fusion-main-menu .fusion-dropdown-menu a {
            font-size: 13.5px !important;
            font-weight: 700 !important;
            padding: 10px 16px !important;
        }
    </style>
    <?php
}, 999);