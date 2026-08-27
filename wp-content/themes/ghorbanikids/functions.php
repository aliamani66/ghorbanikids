<?php
/**
 * GhorbaniKids Theme Functions (100% Modular Native Theme Assets with Auto-Cache-Busting)
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('after_setup_theme', function() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('woocommerce');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption']);
    add_theme_support('align-wide');
    add_theme_support('responsive-embeds');

    register_nav_menus([
        'primary' => 'منوی بالای صفحه (Top Header Menu)',
        'sidebar' => 'منوی بغل سایت (Desktop Sidebar Menu)',
        'mobile'  => 'منوی کشویی موبایل (Mobile Drawer Menu)'
    ]);
});

// لود کاملاً ماژولار، شرطی و با آنتی‌کش خودکار (Cache-Busting via filemtime)
add_action('wp_enqueue_scripts', function() {
    $theme_dir = get_stylesheet_directory();
    $theme_uri = get_stylesheet_directory_uri();

    $style_path = $theme_dir . '/style.css';
    $main_ver = file_exists($style_path) ? filemtime($style_path) : '1.0.0';
    wp_enqueue_style('ghorbanikids-theme-style', get_stylesheet_uri(), [], $main_ver);

    // ۱. صفحه اصلی
    if (is_front_page() || is_home()) {
        $hp_css = $theme_dir . '/assets/css/gk-homepage.css';
        $v = file_exists($hp_css) ? filemtime($hp_css) : '1.0.0';
        wp_enqueue_style('gk-homepage', $theme_uri . '/assets/css/gk-homepage.css', [], $v);
    }

    // ۲. صفحه سبد خرید
    $cart_id = function_exists('wc_get_page_id') ? wc_get_page_id('cart') : 0;
    if (is_cart() || is_page('cart') || ($cart_id && is_page($cart_id))) {
        $st_css = $theme_dir . '/assets/css/gk-stepper.css';
        $c_css  = $theme_dir . '/assets/css/gk-cart.css';
        wp_enqueue_style('gk-stepper', $theme_uri . '/assets/css/gk-stepper.css', [], file_exists($st_css) ? filemtime($st_css) : '1.0.0');
        wp_enqueue_style('gk-cart', $theme_uri . '/assets/css/gk-cart.css', [], file_exists($c_css) ? filemtime($c_css) : '1.0.0');
    }

    // ۳. صفحه تشکر و تایید سفارش
    elseif (function_exists('is_wc_endpoint_url') && is_wc_endpoint_url('order-received')) {
        $st_css = $theme_dir . '/assets/css/gk-stepper.css';
        $ord_css = $theme_dir . '/assets/css/gk-order-received.css';
        wp_enqueue_style('gk-stepper', $theme_uri . '/assets/css/gk-stepper.css', [], file_exists($st_css) ? filemtime($st_css) : '1.0.0');
        wp_enqueue_style('gk-order-received', $theme_uri . '/assets/css/gk-order-received.css', [], file_exists($ord_css) ? filemtime($ord_css) : '1.0.0');
    }

    // ۴. صفحه تسویه‌حساب
    elseif (is_checkout() || is_page('checkout') || (function_exists('wc_get_page_id') && is_page(wc_get_page_id('checkout')))) {
        $st_css = $theme_dir . '/assets/css/gk-stepper.css';
        $chk_css = $theme_dir . '/assets/css/gk-checkout.css';
        wp_enqueue_style('gk-stepper', $theme_uri . '/assets/css/gk-stepper.css', [], file_exists($st_css) ? filemtime($st_css) : '1.0.0');
        wp_enqueue_style('gk-checkout', $theme_uri . '/assets/css/gk-checkout.css', [], file_exists($chk_css) ? filemtime($chk_css) : '1.0.0');
    }

    // ۵. صفحه حساب کاربری
    elseif (is_account_page() || is_page('my-account') || (function_exists('wc_get_page_id') && is_page(wc_get_page_id('myaccount')))) {
        $acc_css = $theme_dir . '/assets/css/gk-account.css';
        wp_enqueue_style('gk-account', $theme_uri . '/assets/css/gk-account.css', [], time());
    }
}, 20);

add_filter('the_content', function($content) {
    if (empty($content) || strpos($content, '[fusion_') === false) {
        return $content;
    }
    return preg_replace('/\[\/?fusion_[^\]]+\]/is', '', $content);
}, 1);

function gk_enqueue_page_specific_assets() {
    $theme_dir = get_stylesheet_directory();
    $theme_uri = get_stylesheet_directory_uri();

    if (is_page('about-us') || is_page('about') || is_page_template('page-about-us.php')) {
        $ab_css = $theme_dir . '/assets/css/gk-about.css';
        wp_enqueue_style('gk-about', $theme_uri . '/assets/css/gk-about.css', [], file_exists($ab_css) ? filemtime($ab_css) : '1.0.0');
    } elseif (is_page('contact-us') || is_page('contact') || is_page_template('page-contact-us.php')) {
        $ct_css = $theme_dir . '/assets/css/gk-contact.css';
        wp_enqueue_style('gk-contact', $theme_uri . '/assets/css/gk-contact.css', [], file_exists($ct_css) ? filemtime($ct_css) : '1.0.0');
    }
}
add_action('wp_enqueue_scripts', 'gk_enqueue_page_specific_assets', 25);
// Direct checkout redirect
add_filter('woocommerce_add_to_cart_redirect', function() {
    return wc_get_checkout_url();
});
