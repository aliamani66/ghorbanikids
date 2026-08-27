<?php
/**
 * WooCommerce Cart & Empty Cart Module for GhorbaniKids (100% Modular)
 */

if (!defined('ABSPATH')) {
    exit;
}

class GK_Cart {

    public static function init() {
        $instance = new self();
        add_action('wp_enqueue_scripts', [$instance, 'enqueue_assets'], 20);
        add_action('wp_head', function() { 
            echo '<style id="gk-stepper-inline-fix">.gk-text-mobile{display:none!important}@media(max-width:768px){.gk-text-desktop{display:none!important}.gk-text-mobile{display:block!important;font-size:0.76rem!important;font-weight:900!important;line-height:1.2!important;color:inherit!important}}</style>'; 
        }, 1);
        add_action('woocommerce_before_cart', [$instance, 'render_stepper'], 5);
        remove_action('woocommerce_cart_is_empty', 'wc_empty_cart_message', 10);
        add_action('woocommerce_cart_is_empty', [$instance, 'render_luxury_empty_cart'], 10);

        add_action('template_redirect', [$instance, 'clear_cart_page_notices']);
        add_filter('wc_add_to_cart_message_html', [$instance, 'suppress_cart_message_on_cart_page'], 10, 2);
    }

    public function clear_cart_page_notices() {
        if (is_cart() || is_page('cart')) {
            wc_clear_notices();
        }
    }

    public function suppress_cart_message_on_cart_page($message, $products) {
        if (is_cart() || is_page('cart')) {
            return '';
        }
        return $message;
    }

    public function enqueue_assets() {
        if (is_cart() || is_page('cart')) {
            $cart_css_path = get_stylesheet_directory() . '/assets/css/gk-cart.css';
            $stepper_css_path = get_stylesheet_directory() . '/assets/css/gk-stepper.css';
            
            $ver_cart = file_exists($cart_css_path) ? filemtime($cart_css_path) : time();
            $ver_step = file_exists($stepper_css_path) ? filemtime($stepper_css_path) : time();
            
            wp_enqueue_style('gk-stepper', get_theme_file_uri('/assets/css/gk-stepper.css'), [], $ver_step);
            wp_enqueue_style('gk-cart', get_theme_file_uri('/assets/css/gk-cart.css'), [], $ver_cart);
        }
    }

    public function render_stepper() {
        $cart_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/');
        $checkout_url = function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : home_url('/checkout/');
        ?>
        <div class="gk-checkout-stepper">
            <div class="gk-step active">
                <span class="gk-step-num">۱</span>
                <span class="gk-step-text">
                    <span class="gk-text-desktop">🛒 سبد خرید</span>
                    <span class="gk-text-mobile">سبد خرید</span>
                </span>
            </div>
            <div class="gk-step-line"></div>
            <div class="gk-step">
                <span class="gk-step-num">۲</span>
                <span class="gk-step-text">
                    <span class="gk-text-desktop">💳 تسویه‌حساب و پرداخت</span>
                    <span class="gk-text-mobile">پرداخت</span>
                </span>
            </div>
            <div class="gk-step-line"></div>
            <div class="gk-step">
                <span class="gk-step-num">۳</span>
                <span class="gk-step-text">
                    <span class="gk-text-desktop">🎉 شروع بازی‌ها</span>
                    <span class="gk-text-mobile">شروع بازی</span>
                </span>
            </div>
        </div>
        <?php
    }

    public function render_luxury_empty_cart() {
        ?>
        <div class="gk-luxury-empty-cart">
            <div class="gk-empty-cart-icon">🛒</div>
            <h2 class="gk-empty-cart-title">سبد خرید شما خالی است!</h2>
            <p class="gk-empty-cart-desc">هنوز هیچ محصول یا اشتراکی به سبد خرید خود اضافه نکرده‌اید. برای شروع می‌توانید از پلن‌های اشتراک ویژه یا بازی‌های هوش دیدن فرمایید.</p>
            <div class="gk-empty-cart-actions">
                <a href="<?php echo esc_url(home_url('/pricing/')); ?>" class="gk-btn-cart-primary">
                    <span>👑 خرید اشتراک ویژه (VIP)</span>
                </a>
                <a href="<?php echo esc_url(home_url('/games/')); ?>" class="gk-btn-cart-secondary">
                    <span>🎮 سالن بازی‌های هوش</span>
                </a>
            </div>
        </div>
        <?php
    }
}