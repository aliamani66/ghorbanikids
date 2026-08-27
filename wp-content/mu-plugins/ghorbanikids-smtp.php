<?php
/**
 * Plugin Name: Ghorbani Kids Authenticated SMTP
 * Description: Routes all WordPress and WooCommerce emails through the official MizbanFa SPF-authorized gateway (mail.mizbanfalocal.com) with support@ghorbanikids.ir.
 * Version: 2.1.0
 * Author: Ghorbani Kids
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('phpmailer_init', function($phpmailer) {
    $phpmailer->isSMTP();
    $phpmailer->Host        = 'mail.mizbanfalocal.com';
    $phpmailer->SMTPAuth    = true;
    $phpmailer->Username    = 'support@ghorbanikids.ir';
    $phpmailer->Password    = 'Email@12345678';
    $phpmailer->SMTPSecure  = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS; // TLS
    $phpmailer->Port        = 587;
    $phpmailer->CharSet     = 'UTF-8';
    
    // Set official sender
    $phpmailer->From        = 'support@ghorbanikids.ir';
    $phpmailer->FromName    = 'قربانی کیدز';
}, 999);

// Ensure default from address is always support@ghorbanikids.ir
add_filter('wp_mail_from', function($original_email) {
    return 'support@ghorbanikids.ir';
}, 999);

add_filter('wp_mail_from_name', function($original_name) {
    return 'قربانی کیدز';
}, 999);

// Set appropriate WooCommerce sender options
add_filter('woocommerce_email_from_address', function($from_email) {
    return 'support@ghorbanikids.ir';
}, 999);

add_filter('woocommerce_email_from_name', function($from_name) {
    return 'قربانی کیدز';
}, 999);