<?php
/**
 * WooCommerce Checkout & Order Received Module for GhorbaniKids (100% Modular)
 */

if (!defined('ABSPATH')) {
    exit;
}

class GK_Checkout {

    public static function init() {
        $instance = new self();

        add_action('wp_enqueue_scripts', [$instance, 'enqueue_assets'], 20);
        add_action('wp_head', function() { echo '<style id=\"gk-stepper-inline-fix\">.gk-text-mobile{display:none!important}@media(max-width:768px){.gk-text-desktop{display:none!important}.gk-text-mobile{display:block!important;font-size:0.76rem!important;font-weight:900!important;line-height:1.2!important;color:inherit!important}}</style>'; }, 1);

        // نوار مراحل خرید و هدر تمیز ثبت سفارش
        add_action('woocommerce_before_checkout_form', [$instance, 'render_checkout_stepper'], 5);
        add_action('woocommerce_before_thankyou', [$instance, 'render_thankyou_stepper'], 5);
        add_action('woocommerce_before_thankyou', [$instance, 'render_clean_success_header'], 6);
        add_action('woocommerce_before_thankyou', [$instance, 'render_bale_receipt_banner'], 7);
        add_action('woocommerce_thankyou_bacs', [$instance, 'render_luxury_bank_card'], 5);

        // ساده‌سازی فیلدهای تسویه‌حساب
        add_filter('woocommerce_checkout_fields', [$instance, 'simplify_checkout_fields'], 9999);
        add_filter('woocommerce_enable_order_notes_field', '__return_false');

        add_filter('woocommerce_order_button_text', function() {
            return 'پرداخت امن و فعال‌سازی آنی اشتراک';
        });

        remove_action('woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10);
        add_action('woocommerce_after_checkout_billing_form', [$instance, 'render_inline_coupon_box'], 20);

        // فعال‌سازی آنی اشتراک
        add_action('woocommerce_order_status_completed', [$instance, 'handle_subscription_order']);
        add_action('woocommerce_order_status_processing', [$instance, 'handle_subscription_order']);
        add_action('woocommerce_order_status_changed', [$instance, 'handle_subscription_order_on_change'], 10, 3);
    }

    public function enqueue_assets() {
        $assets_url = content_url('mu-plugins/ghorbanikids-games-manager/assets/css');
        $chk_file = WPMU_PLUGIN_DIR . '/ghorbanikids-games-manager/assets/css/gk-checkout.css';
        $ver = file_exists($chk_file) ? filemtime($chk_file) : time();

        if (is_wc_endpoint_url('order-received') || (function_exists('is_order_received_page') && is_order_received_page()) || strpos($_SERVER['REQUEST_URI'] ?? '', 'order-received') !== false) {
            $stp_file = WPMU_PLUGIN_DIR . '/ghorbanikids-games-manager/assets/css/gk-stepper.css';
        $stp_ver = file_exists($stp_file) ? filemtime($stp_file) : time();
        wp_enqueue_style('gk-stepper', $assets_url . '/gk-stepper.css', [], $stp_ver);
            wp_enqueue_style('gk-order-received', $assets_url . '/gk-order-received.css', [], $ver);
        } elseif (is_checkout() || is_page('checkout')) {
            $stp_file = WPMU_PLUGIN_DIR . '/ghorbanikids-games-manager/assets/css/gk-stepper.css';
        $stp_ver = file_exists($stp_file) ? filemtime($stp_file) : time();
        wp_enqueue_style('gk-stepper', $assets_url . '/gk-stepper.css', [], $stp_ver);
            wp_enqueue_style('gk-checkout', $assets_url . '/gk-checkout.css', [], $ver);
        }
    }

    public function render_checkout_stepper() {
        $cart_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/');
        ?>
        <div class="gk-checkout-stepper">
            <a href="<?php echo esc_url($cart_url); ?>" class="gk-step completed gk-step-link" title="بازگشت به سبد خرید">
                <span class="gk-step-num">✓</span>
                <span class="gk-step-text">
                    <span class="gk-text-desktop">🛒 سبد خرید</span>
                    <span class="gk-text-mobile">سبد خرید</span>
                </span>
            </a>
            <div class="gk-step-line filled"></div>
            <div class="gk-step active">
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

    public function render_thankyou_stepper($order_id = 0) {
        $cart_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/');
        $checkout_url = function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : home_url('/checkout/');
        ?>
        <div class="gk-checkout-stepper">
            <a href="<?php echo esc_url($cart_url); ?>" class="gk-step completed gk-step-link" title="مشاهده سبد خرید">
                <span class="gk-step-num">✓</span>
                <span class="gk-step-text">
                    <span class="gk-text-desktop">🛒 سبد خرید</span>
                    <span class="gk-text-mobile">سبد خرید</span>
                </span>
            </a>
            <div class="gk-step-line filled"></div>
            <a href="<?php echo esc_url($checkout_url); ?>" class="gk-step completed gk-step-link" title="مشاهده تسویه‌حساب">
                <span class="gk-step-num">✓</span>
                <span class="gk-step-text">
                    <span class="gk-text-desktop">💳 تسویه‌حساب</span>
                    <span class="gk-text-mobile">پرداخت</span>
                </span>
            </a>
            <div class="gk-step-line filled"></div>
            <div class="gk-step active">
                <span class="gk-step-num">۳</span>
                <span class="gk-step-text">
                    <span class="gk-text-desktop">🎉 ثبت موفق سفارش</span>
                    <span class="gk-text-mobile">تأیید سفارش</span>
                </span>
            </div>
        </div>
        <?php
    }

    public function render_clean_success_header($order_id = 0) {
        $order = wc_get_order($order_id);
        $is_school_order = false;
        if ($order) {
            foreach ($order->get_items() as $item) {
                $name = $item->get_name();
                if (strpos($name, 'مهدکودک') !== false || strpos($name, 'مدرسه') !== false || strpos($name, 'سازمانی') !== false) {
                    $is_school_order = true;
                    break;
                }
            }
        }
        ?>
        <div class="gk-order-success-header">
            <div class="gk-success-icon-badge">
                <svg viewBox="0 0 24 24" width="36" height="36" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            </div>
            <div class="gk-success-title-box">
                <h1 class="gk-success-title">سفارش شما با موفقیت ثبت شد! 🎉</h1>
                <p class="gk-success-subtitle">
                    <?php if ($is_school_order): ?>
                        اشتراک سازمانی مدارس و مهدکودک شما ثبت گردید. پس از تأیید واریز، پنل کلاسی شما فعال خواهد بود.
                    <?php else: ?>
                        از اعتماد و همراهی شما سپاسگزاریم. لطفاً جهت فعال‌سازی آنی، تصویر فیش واریزی را ارسال فرمایید.
                    <?php endif; ?>
                </p>
            </div>
        </div>
        <?php
    }

    public function render_bale_receipt_banner($order_id = 0) {
        $order = wc_get_order($order_id);
        $order_num = $order ? $order->get_order_number() : $order_id;
        $order_total = $order ? wc_price($order->get_total()) : '';
        $ble_url = class_exists('GK_Utils') ? GK_Utils::get_bale_support_url() : 'https://ble.ir/ghorbanikids';
        ?>
        <div class="gk-bale-receipt-banner">
            <div class="gk-bale-banner-header">
                <div class="gk-bale-banner-icon">
                    <svg viewBox="0 0 24 24" width="28" height="28" fill="none">
                        <circle cx="12" cy="12" r="11" fill="#10b981"/>
                        <path d="M7 12.5C7 9.5 9.2 7 12 7C14.8 7 17 9.5 17 12.5C17 15.5 14.8 17.5 12 17.5C10.8 17.5 9.7 17 8.9 16.2L7 17L7.6 15.2C7.2 14.4 7 13.5 7 12.5Z" fill="#ffffff"/>
                        <circle cx="10" cy="12" r="1" fill="#10b981"/>
                        <circle cx="14" cy="12" r="1" fill="#10b981"/>
                    </svg>
                </div>
                <div class="gk-bale-banner-text">
                    <h3 class="gk-bale-banner-title">📲 ارسال تصویر فیش واریزی به پشتیبانی در بله</h3>
                    <p class="gk-bale-banner-desc">برای تأیید فوری و فعال‌سازی دسترسی بدون معطلی، روی دکمه زیر کلیک کرده و عکس فیش را در بله ارسال فرمایید:</p>
                </div>
            </div>

            <div class="gk-bale-action-wrap">
                <a href="<?php echo esc_url($ble_url); ?>" target="_blank" rel="noopener noreferrer" class="gk-btn-bale-send-receipt">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none">
                        <circle cx="12" cy="12" r="11" fill="#ffffff"/>
                        <path d="M7 12.5C7 9.5 9.2 7 12 7C14.8 7 17 9.5 17 12.5C17 15.5 14.8 17.5 12 17.5C10.8 17.5 9.7 17 8.9 16.2L7 17L7.6 15.2C7.2 14.4 7 13.5 7 12.5Z" fill="#10b981"/>
                        <circle cx="10" cy="12" r="1" fill="#ffffff"/>
                        <circle cx="14" cy="12" r="1" fill="#ffffff"/>
                    </svg>
                    <span>ارسال فیش سفارش #<?php echo esc_html($order_num); ?> در پیام‌رسان بله</span>
                </a>
            </div>
        </div>
        <?php
    }

    public function render_luxury_bank_card($order_id = 0) {
        $card_num = GK_Utils::get_bank_card_formatted();
        $card_digits_clean = GK_Utils::get_bank_card_digits();
        $bank_name = GK_Utils::get_bank_name();
        $bank_sub = GK_Utils::get_bank_card_sub();
        $card_holder = GK_Utils::get_bank_card_holder();
        ?>
        <div class="gk-bank-card-container">
            <div class="gk-bank-card-visual">
                <div class="gk-card-top-row">
                    <div class="gk-card-bank-info">
                        <span class="gk-card-bank-name"><?php echo esc_html($bank_name); ?></span>
                        <span class="gk-card-bank-sub"><?php echo esc_html($bank_sub); ?></span>
                    </div>
                    <div class="gk-card-chip">
                        <div class="gk-chip-line"></div>
                        <div class="gk-chip-line"></div>
                    </div>
                </div>

                <div class="gk-card-number-box">
                    <span class="gk-card-number" id="gkCardNumber"><?php echo esc_html($card_num); ?></span>
                    <button type="button" class="gk-btn-copy-card" id="gkCopyCardBtn" data-copy="<?php echo esc_attr($card_digits_clean); ?>">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                        <span id="gkCopyText">کپی شماره کارت</span>
                    </button>
                </div>

                <div class="gk-card-bottom-row">
                    <div>
                        <span class="gk-card-label">صاحب حساب:</span>
                        <span class="gk-card-holder"><?php echo esc_html($card_holder); ?></span>
                    </div>
                    <div class="gk-card-verified-badge">
                        <span>✓ حساب تجاری تأیید‌شده</span>
                    </div>
                </div>
            </div>
        </div>

        <script data-no-optimize="1">
            document.addEventListener("DOMContentLoaded", function() {
                var copyBtn = document.getElementById("gkCopyCardBtn");
                if (copyBtn) {
                    copyBtn.addEventListener("click", function(e) {
                        e.preventDefault();
                        var text = copyBtn.getAttribute("data-copy");
                        navigator.clipboard.writeText(text).then(function() {
                            var copyText = document.getElementById("gkCopyText");
                            if (copyText) {
                                copyText.textContent = "کپی شد! ✓";
                                setTimeout(function() { copyText.textContent = "کپی شماره کارت"; }, 2500);
                            }
                        });
                    });
                }
            });
        </script>
        <?php
    }

    public function simplify_checkout_fields($fields) {
        unset($fields['billing']['billing_company']);
        unset($fields['billing']['billing_country']);
        unset($fields['billing']['billing_address_1']);
        unset($fields['billing']['billing_address_2']);
        unset($fields['billing']['billing_city']);
        unset($fields['billing']['billing_state']);
        unset($fields['billing']['billing_postcode']);
        unset($fields['shipping']);
        unset($fields['order']['order_comments']);

        if (isset($fields['billing']['billing_first_name'])) {
            $fields['billing']['billing_first_name']['label'] = 'نام';
            $fields['billing']['billing_first_name']['placeholder'] = 'مثال: علی';
            $fields['billing']['billing_first_name']['class'] = ['form-row-first', 'gk-field-half'];
            $fields['billing']['billing_first_name']['required'] = true;
        }
        if (isset($fields['billing']['billing_last_name'])) {
            $fields['billing']['billing_last_name']['label'] = 'نام خانوادگی';
            $fields['billing']['billing_last_name']['placeholder'] = 'مثال: محمدی';
            $fields['billing']['billing_last_name']['class'] = ['form-row-last', 'gk-field-half'];
            $fields['billing']['billing_last_name']['required'] = true;
        }
        if (isset($fields['billing']['billing_phone'])) {
            $fields['billing']['billing_phone']['label'] = 'شماره موبایل';
            $fields['billing']['billing_phone']['placeholder'] = '۰۹۱۲۳۴۵۶۷۸۹';
            $fields['billing']['billing_phone']['class'] = ['form-row-wide', 'gk-field-full', 'gk-mobile-half'];
            $fields['billing']['billing_phone']['required'] = true;
        }
        if (isset($fields['billing']['billing_email'])) {
            $fields['billing']['billing_email']['label'] = 'آدرس ایمیل';
            $fields['billing']['billing_email']['placeholder'] = 'name@example.com';
            $fields['billing']['billing_email']['class'] = ['form-row-wide', 'gk-field-full', 'gk-mobile-half'];
            $fields['billing']['billing_email']['required'] = false;
        }

        return $fields;
    }

    public function render_inline_coupon_box() {
        ?>
        <div class="gk-inline-coupon-box">
            <div class="gk-coupon-header">
                <span class="gk-coupon-badge-icon">🎁</span>
                <span class="gk-coupon-title-text">کد تخفیف دارید؟</span>
            </div>
            <div class="gk-coupon-input-group">
                <input type="text" name="gk_coupon_code" class="input-text gk-coupon-input" placeholder="کد تخفیف را وارد کنید..." id="gk_coupon_code" />
                <button type="button" class="button gk-btn-apply-coupon" id="gk_apply_coupon_btn">اعمال کد</button>
            </div>
            <div id="gk_coupon_message" class="gk-coupon-msg"></div>
        </div>
        <script>
        jQuery(function($) {
            $('#gk_apply_coupon_btn').on('click', function(e) {
                e.preventDefault();
                var code = $('#gk_coupon_code').val().trim();
                if (!code) return;
                var btn = $(this);
                btn.text('...').prop('disabled', true);
                $.ajax({
                    type: 'POST',
                    url: wc_checkout_params.wc_ajax_url.toString().replace('%%endpoint%%', 'apply_coupon'),
                    data: { security: wc_checkout_params.apply_coupon_nonce, coupon_code: code },
                    success: function(response) {
                        btn.text('اعمال کد').prop('disabled', false);
                        $('body').trigger('update_checkout');
                        $('#gk_coupon_message').html(response);
                    },
                    error: function() { btn.text('اعمال کد').prop('disabled', false); }
                });
            });
        });
        </script>
        <?php
    }

    public function handle_subscription_order($order_id) {
        $this->process_subscription_activation($order_id);
    }

    public function handle_subscription_order_on_change($order_id, $old_status, $new_status) {
        if (in_array($new_status, ['processing', 'completed'])) {
            $this->process_subscription_activation($order_id);
        }
    }

    private function process_subscription_activation($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) return;
        $user_id = $order->get_user_id();
        if (!$user_id) {
            $email = $order->get_billing_email();
            $phone = $order->get_billing_phone();
            $user = get_user_by('email', $email);
            if (!$user && $phone) $user = get_user_by('login', $phone);
            if ($user) $user_id = $user->ID;
        }
        if (!$user_id) return;

        $total_days = 0;
        foreach ($order->get_items() as $item) {
            $sku = get_post_meta($item->get_product_id(), '_sku', true);
            $title = $item->get_name();
            if (strpos($sku, 'VIP-1Y') !== false || strpos($title, '۱ سال') !== false || strpos($title, '1 سال') !== false) {
                $total_days += 365;
            } elseif (strpos($sku, 'VIP-3M') !== false || strpos($title, '۳ ماه') !== false || strpos($title, '3 ماه') !== false) {
                $total_days += 93;
            } else {
                $total_days += 31;
            }
        }

        if ($total_days > 0) {
            $cur = get_user_meta($user_id, 'gk_vip_expires_at', true);
            $now = time();
            $new_exp = ($cur && $cur > $now) ? ($cur + ($total_days * DAY_IN_SECONDS)) : ($now + ($total_days * DAY_IN_SECONDS));
            update_user_meta($user_id, 'gk_vip_expires_at', $new_exp);
            update_user_meta($user_id, 'gk_is_vip', 1);
            $order->add_order_note("✅ اشتراک VIP به مدت {$total_days} روز تا تاریخ " . date_i18n('Y/m/d H:i', $new_exp) . " فعال شد.");
        }
    }
}