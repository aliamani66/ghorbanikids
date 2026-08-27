<?php
/**
 * User Auth & My Account Dashboard Module for GhorbaniKids (100% Modular)
 */

if (!defined('ABSPATH')) {
    exit;
}

class GK_Auth {

    public static function init() {
        $instance = new self();
        add_action('wp_enqueue_scripts', [$instance, 'enqueue_assets'], 20);
        add_action('woocommerce_account_dashboard', [$instance, 'render_dashboard_overview'], 1);
        add_action('woocommerce_before_customer_login_form', [$instance, 'render_auth_tabs_header'], 5);
        add_filter('woocommerce_account_menu_items', [__CLASS__, 'format_account_menu_items'], 99);
    }

    public function enqueue_assets() {
        if (is_account_page() || is_page('my-account')) {
            $assets_url = content_url('mu-plugins/ghorbanikids-games-manager/assets/css');
            wp_enqueue_style('gk-account', $assets_url . '/gk-account.css', [], time());
        }
    }

    public function render_auth_tabs_header() {
        if (is_user_logged_in()) return;
        ?>
        <style id="gk-auth-critical-styles">
            .woocommerce-account #customer_login,
            #customer_login {
                max-width: 520px !important;
                width: 100% !important;
                margin: 0 auto 50px auto !important;
                background: #ffffff !important;
                border: 2px solid #bae6fd !important;
                border-radius: 28px !important;
                padding: 28px 22px !important;
                box-shadow: 0 16px 45px rgba(2, 132, 199, 0.1) !important;
                box-sizing: border-box !important;
                float: none !important;
                display: block !important;
            }
            .gk-auth-tabs-wrapper {
                max-width: 520px !important;
                width: 100% !important;
                margin: 25px auto 16px auto !important;
                padding: 0 4px !important;
                box-sizing: border-box !important;
                direction: rtl !important;
            }
            .gk-auth-tabs-header {
                display: flex !important;
                background: #f0f9ff !important;
                border: 1.5px solid #bae6fd !important;
                border-radius: 20px !important;
                padding: 5px !important;
                gap: 6px !important;
                box-shadow: 0 4px 14px rgba(2, 132, 199, 0.08) !important;
                box-sizing: border-box !important;
            }
            .gk-auth-tab-btn {
                flex: 1 !important;
                height: 48px !important;
                border-radius: 16px !important;
                border: none !important;
                outline: none !important;
                -webkit-appearance: none !important;
                appearance: none !important;
                font-family: 'IRANSansXFaNum', 'IRANSansX', 'Vazirmatn', Tahoma, sans-serif !important;
                font-size: 0.95rem !important;
                font-weight: 800 !important;
                cursor: pointer !important;
                transition: all 0.22s ease !important;
                color: #475569 !important;
                background: transparent !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                gap: 6px !important;
                padding: 0 10px !important;
                text-decoration: none !important;
                box-sizing: border-box !important;
            }
            .gk-auth-tab-btn:hover {
                color: #0284c7 !important;
            }
            .gk-auth-tab-btn.active {
                background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%) !important;
                color: #ffffff !important;
                box-shadow: 0 4px 14px rgba(2, 132, 199, 0.35) !important;
            }
            #customer_login h2 {
                display: none !important;
            }
            #customer_login .u-column1, #customer_login .col-1,
            #customer_login .u-column2, #customer_login .col-2 {
                width: 100% !important;
                max-width: 100% !important;
                float: none !important;
                padding: 0 !important;
                margin: 0 !important;
                border: none !important;
                box-sizing: border-box !important;
            }
            @media (max-width: 600px) {
                .woocommerce-account #customer_login, #customer_login {
                    padding: 20px 14px !important;
                    border-radius: 22px !important;
                }
                .gk-auth-tab-btn {
                    height: 44px !important;
                    font-size: 0.88rem !important;
                    border-radius: 14px !important;
                }
            }
        </style>

        <div class="gk-auth-tabs-wrapper">
            <div class="gk-auth-tabs-header" id="gkAuthTabsHeader">
                <button type="button" class="gk-auth-tab-btn active" id="gkTabBtnLogin">🔑 ورود به حساب</button>
                <button type="button" class="gk-auth-tab-btn" id="gkTabBtnRegister">✨ عضویت و ثبت‌نام</button>
            </div>
        </div>

        <script data-no-optimize="1">
            function gkSwitchAuthTab(target) {
                var loginBtn = document.getElementById("gkTabBtnLogin");
                var registerBtn = document.getElementById("gkTabBtnRegister");
                var customerLogin = document.getElementById("customer_login");
                if (!customerLogin) return;

                var col1 = customerLogin.querySelector(".col-1") || customerLogin.querySelector(".u-column1") || customerLogin.querySelector(".woocommerce-form-login");
                var col2 = customerLogin.querySelector(".col-2") || customerLogin.querySelector(".u-column2") || customerLogin.querySelector(".woocommerce-form-register");

                if (target === "register") {
                    if (loginBtn) loginBtn.classList.remove("active");
                    if (registerBtn) registerBtn.classList.add("active");
                    customerLogin.classList.remove("show-login");
                    customerLogin.classList.add("show-register");
                    if (col1) col1.style.setProperty("display", "none", "important");
                    if (col2) col2.style.setProperty("display", "block", "important");
                } else {
                    if (registerBtn) registerBtn.classList.remove("active");
                    if (loginBtn) loginBtn.classList.add("active");
                    customerLogin.classList.remove("show-register");
                    customerLogin.classList.add("show-login");
                    if (col2) col2.style.setProperty("display", "none", "important");
                    if (col1) col1.style.setProperty("display", "block", "important");
                }
            }

            function gkBindAuthTabs() {
                var loginBtn = document.getElementById("gkTabBtnLogin");
                var registerBtn = document.getElementById("gkTabBtnRegister");

                if (loginBtn) {
                    loginBtn.onclick = function(e) { e.preventDefault(); gkSwitchAuthTab("login"); };
                }
                if (registerBtn) {
                    registerBtn.onclick = function(e) { e.preventDefault(); gkSwitchAuthTab("register"); };
                }
                gkSwitchAuthTab("login");
            }

            if (document.readyState === "loading") {
                document.addEventListener("DOMContentLoaded", gkBindAuthTabs);
            } else {
                gkBindAuthTabs();
            }
        </script>
        <?php
    }

    public function render_dashboard_overview() {
        $current_user = wp_get_current_user();
        if (!$current_user || !$current_user->ID) return;

        $user_id = $current_user->ID;
        $display_name = $current_user->display_name ?: $current_user->user_login;
        $is_vip = get_user_meta($user_id, 'gk_is_vip', true);
        $vip_expires_at = (int) get_user_meta($user_id, 'gk_vip_expires_at', true);
        $now = time();
        $is_active_vip = ($is_vip && $vip_expires_at && $vip_expires_at > $now);
        ?>
        <div class="gk-account-dashboard">
            <?php if ($is_active_vip): 
                $days_left = max(0, ceil(($vip_expires_at - $now) / DAY_IN_SECONDS));
            ?>
                <div class="gk-dashboard-vip-banner active">
                    <div class="gk-vip-banner-right">
                        <div class="gk-vip-banner-icon">👑</div>
                        <div class="gk-vip-banner-texts">
                            <h3>اشتراک ویژه شما فعال است! 🎉</h3>
                            <p>
                                شما به تمامی بازی‌های فکری و مهارتی قربانی کیدز دسترسی نامحدود دارید.
                                (<strong><?php echo esc_html($days_left); ?> روز</strong> باقیمانده — اعتبار تا <?php echo date_i18n('Y/m/d', $vip_expires_at); ?>)
                            </p>
                        </div>
                    </div>
                    <a href="<?php echo esc_url(home_url('/games/')); ?>" class="gk-vip-banner-btn play">
                        <span>🎮 ورود به سالن بازی‌ها ←</span>
                    </a>
                </div>
            <?php else: ?>
                <div class="gk-dashboard-vip-banner inactive">
                    <div class="gk-vip-banner-right">
                        <div class="gk-vip-banner-icon">🔒</div>
                        <div class="gk-vip-banner-texts">
                            <h3>هنوز اشتراک ویژه‌ای فعال ندارید</h3>
                            <p>با تهیه اشتراک VIP، قفل تمامی بازی‌های فکری، سنجش هوش و گزارش‌های تحلیلی را باز کنید.</p>
                        </div>
                    </div>
                    <a href="<?php echo esc_url(home_url('/pricing/')); ?>" class="gk-vip-banner-btn buy">
                        <span>⚡ خرید اشتراک ویژه (VIP) ←</span>
                    </a>
                </div>
            <?php endif; ?>

            <div class="gk-acc-hero-card">
                <div class="gk-acc-user-info">
                    <div class="gk-acc-avatar">
                        <?php echo strtoupper(mb_substr($display_name, 0, 1)); ?>
                    </div>
                    <div>
                        <h2 class="gk-acc-name">سلام، <?php echo esc_html($display_name); ?> عزیز 👋</h2>
                        <span class="gk-acc-phone"><?php echo esc_html($current_user->user_email ?: $current_user->user_login); ?></span>
                    </div>
                </div>
                <div class="gk-acc-vip-badge-box">
                    <?php if ($is_active_vip): ?>
                        <div class="gk-vip-pill active">
                            <span>👑 اشتراک ویژه فعال</span>
                            <small>اعتبار تا: <?php echo date_i18n('Y/m/d', $vip_expires_at); ?></small>
                        </div>
                    <?php else: ?>
                        <div class="gk-vip-pill inactive">
                            <span>👤 کاربر عادی</span>
                            <a href="<?php echo esc_url(home_url('/pricing/')); ?>" class="gk-btn-buy-vip">خرید اشتراک VIP ⚡</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="gk-acc-grid">
                <a href="<?php echo esc_url(home_url('/games/')); ?>" class="gk-acc-card card-games">
                    <div class="gk-acc-card-icon">🎮</div>
                    <h3>سالن بازی‌های هوش</h3>
                    <p>ورود به ده‌ها بازی مهارتی، حافظه و تمرکز</p>
                    <span class="gk-acc-card-link">ورود به بازی‌ها ←</span>
                </a>

                <a href="<?php echo esc_url(home_url('/tests/')); ?>" class="gk-acc-card card-tests">
                    <div class="gk-acc-card-icon">🧠</div>
                    <h3>تست‌ها و استعدادیابی</h3>
                    <p>سنجش هوش، دقت و روانشناسی کودکان</p>
                    <span class="gk-acc-card-link">مشاهده آزمون‌ها ←</span>
                </a>

                <a href="<?php echo esc_url(wc_get_endpoint_url('orders', '', wc_get_page_permalink('myaccount'))); ?>" class="gk-acc-card card-orders">
                    <div class="gk-acc-card-icon">📦</div>
                    <h3>سفارش‌ها و فاکتورها</h3>
                    <p>مشاهده سوابق خرید و فاکتورهای پرداخت‌شده</p>
                    <span class="gk-acc-card-link">لیست سفارش‌ها ←</span>
                </a>

                <a href="<?php echo esc_url(home_url('/pricing/')); ?>" class="gk-acc-card card-pricing">
                    <div class="gk-acc-card-icon">👑</div>
                    <h3>ارتقا و تمدید اشتراک</h3>
                    <p>دسترسی نامحدود به تمامی بازی‌ها و امکانات</p>
                    <span class="gk-acc-card-link">مشاهده تعرفه‌ها ←</span>
                </a>
            </div>
        </div>
        <?php
    }

    public static function format_account_menu_items($items) {
        $icon_map = [
            'dashboard'       => '🏠 پیشخوان',
            'school-panel'    => '🏫 پنل مدارس',
            'my-assessments'  => '🧠 آزمون‌های من',
            'orders'          => '📦 سفارش‌ها',
            'downloads'       => '💾 دانلودها',
            'edit-address'    => '📍 نشانی‌ها',
            'edit-account'    => '👤 جزئیات حساب',
            'customer-logout' => '🚪 خروج از حساب'
        ];

        $formatted = [];
        foreach ($items as $endpoint => $label) {
            if (isset($icon_map[$endpoint])) {
                $formatted[$endpoint] = $icon_map[$endpoint];
            } else {
                $clean_label = preg_replace('/^[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]\s*/u', '', $label);
                $formatted[$endpoint] = '🔹 ' . trim($clean_label);
            }
        }
        return $formatted;
    }
}
