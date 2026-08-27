<?php
/**
 * Header & Top Navbar Module for GhorbaniKids Custom Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

class GK_Header {

    public function render_smart_menu($location, $menu_class, $fallback_callback) {
        $menu_to_use = null;

        if (has_nav_menu($location)) {
            $menu_to_use = ['theme_location' => $location];
        } elseif (has_nav_menu('primary')) {
            $menu_to_use = ['theme_location' => 'primary'];
        } elseif (has_nav_menu('sidebar')) {
            $menu_to_use = ['theme_location' => 'sidebar'];
        } else {
            // If no location is checked in WP admin, automatically find the first available menu
            $all_menus = wp_get_nav_menus();
            if (!empty($all_menus)) {
                $menu_to_use = ['menu' => $all_menus[0]->term_id];
            }
        }

        if ($menu_to_use) {
            wp_nav_menu(array_merge($menu_to_use, [
                'container'   => false,
                'menu_class'  => $menu_class,
                'depth'       => 2,
                'fallback_cb' => $fallback_callback
            ]));
        } else {
            if (is_callable($fallback_callback)) {
                call_user_func($fallback_callback);
            }
        }
    }


    public static function init() {
        $instance = new self();

        // لود استایل هدر و ناوبری با ورژن جدید جهت بای‌پس کش
        add_action('wp_enqueue_scripts', [$instance, 'enqueue_assets'], 10);

        // تزریق هدر اختصاصی
        add_action('gk_before_header', [$instance, 'inject_top_navbar'], 10);
        add_filter('nav_menu_item_title', [$instance, 'enrich_mobile_menu_icons'], 10, 4);
    }

    public function enqueue_assets() {
        $assets_url = plugins_url('assets', dirname(__FILE__));
        $css_ver = file_exists(dirname(dirname(__FILE__)) . '/assets/css/gk-header.css') ? filemtime(dirname(dirname(__FILE__)) . '/assets/css/gk-header.css') : time();
        wp_enqueue_style('gk-header', $assets_url . '/css/gk-header.css', [], time());
    }

    public function inject_top_navbar() {
        static $rendered = false;
        if ($rendered) return;
        $rendered = true;

        $cart_count = 0;
        if (isset($_COOKIE['woocommerce_items_in_cart']) && function_exists('WC') && WC()->cart) {
            $cart_count = WC()->cart->get_cart_contents_count();
        }
        $is_logged_in = is_user_logged_in();
        $account_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/my-account/');
        $cart_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('cart') : home_url('/cart/');
        $login_url = $account_url;
        $logout_url = wp_logout_url(home_url('/'));
        $home_url = home_url('/');
        ?>
        <header id="gkTopNavbar" class="gk-top-navbar" data-no-optimize="1">
            <div class="gk-navbar-inner">
                <!-- ۱. لوگو و نام برند -->
                <a href="<?php echo esc_url($home_url); ?>" class="gk-nav-brand">
                    <img src="https://ghorbanikids.ir/wp-content/uploads/2026/08/ghorbanikids_brand_logo.png" alt="قربانی کیدز" class="gk-nav-logo" width="48" height="48" data-no-optimize="1" loading="eager" decoding="async" />
                    <div>
                        <span class="gk-nav-brand-title">قربانی کیدز</span>
                        
                    </div>
                </a>

                <!-- ۲. منوی ناوبری داینامیک متصل به فهرست‌های وردپرس -->
                <nav class="gk-nav-main-menu">
                    <?php
                    $this->render_smart_menu('primary', 'gk-nav-links', [$this, 'fallback_top_menu']);
                    ?>
                </nav>

                <!-- ۳. دکمه‌ها و ابزارهای کاربر -->
                <div class="gk-nav-actions">
                    <a href="<?php echo esc_url($cart_url); ?>" class="gk-action-icon-btn" title="سبد خرید">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="9" cy="21" r="1"></circle>
                            <circle cx="20" cy="21" r="1"></circle>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                        </svg>
                        <?php if ($cart_count > 0): ?>
                            <span class="gk-cart-badge"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </a>

                    <?php if ($is_logged_in): ?>
                        <a href="<?php echo esc_url($account_url); ?>" class="gk-action-icon-btn" title="حساب کاربری">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </a>
                        <a href="<?php echo esc_url($logout_url); ?>" class="gk-action-icon-btn" title="خروج از حساب">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                <polyline points="16 17 21 12 16 7"></polyline>
                                <line x1="21" y1="12" x2="9" y2="12"></line>
                            </svg>
                        </a>
                    <?php else: ?>
                        <a href="<?php echo esc_url($login_url); ?>" class="gk-nav-btn-login">
                            <span>🔑 ورود / ثبت‌نام</span>
                        </a>
                    <?php endif; ?>

                    <button type="button" class="gk-mobile-toggle-btn" id="gkMobileMenuToggle" aria-label="منوی موبایل">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="3" y1="12" x2="21" y2="12"></line>
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <line x1="3" y1="18" x2="21" y2="18"></line>
                        </svg>
                    </button>
                </div>
            </div>
        </header>

        <!-- لایه تاریک پشت دراور موبایل -->
        <div id="gkMobileDrawerOverlay" class="gk-mobile-drawer-overlay"></div>

        <!-- کشوی منوی موبایل (ایزوله و مدرن) -->
        <div id="gkMobileDrawer" class="gk-mobile-drawer">
            <div class="gk-drawer-header">
                <a href="<?php echo esc_url($home_url); ?>" class="gk-drawer-brand">
                    <img src="https://ghorbanikids.ir/wp-content/uploads/2026/08/ghorbanikids_brand_logo.png" alt="قربانی کیدز" class="gk-drawer-logo" width="38" height="38" data-no-optimize="1" loading="eager" decoding="async" />
                    <span class="gk-drawer-title">قربانی کیدز</span>
                </a>
                <button type="button" class="gk-drawer-close-btn" id="gkMobileDrawerClose" aria-label="بستن منو">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="gk-drawer-content">
                <?php
                $this->render_smart_menu('mobile', 'gk-mobile-menu-list', [$this, 'fallback_mobile_menu']);
                ?>
            </div>
            <div class="gk-drawer-footer">
                <?php if ($is_logged_in): ?>
                    <a href="<?php echo esc_url($account_url); ?>" class="gk-btn-drawer-account">
                        <span>👤 حساب کاربری من</span>
                    </a>
                <?php else: ?>
                    <a href="<?php echo esc_url($login_url); ?>" class="gk-btn-drawer-account">
                        <span>🔑 ورود / عضویت در سایت</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <script data-no-optimize="1">
            document.addEventListener("DOMContentLoaded", function() {
                var btn = document.getElementById("gkMobileMenuToggle");
                var drawer = document.getElementById("gkMobileDrawer");
                var overlay = document.getElementById("gkMobileDrawerOverlay");
                var closeBtn = document.getElementById("gkMobileDrawerClose");

                function openMenu() {
                    if (drawer) drawer.classList.add("is-open");
                    if (overlay) overlay.classList.add("is-open");
                    document.body.style.overflow = "hidden";
                }

                function closeMenu() {
                    if (drawer) drawer.classList.remove("is-open");
                    if (overlay) overlay.classList.remove("is-open");
                    document.body.style.overflow = "";
                }

                if (btn) {
                    btn.addEventListener("click", function(e) {
                        e.stopPropagation();
                        if (drawer && drawer.classList.contains("is-open")) {
                            closeMenu();
                        } else {
                            openMenu();
                        }
                    });
                }

                if (closeBtn) {
                    closeBtn.addEventListener("click", function(e) {
                        e.preventDefault();
                        closeMenu();
                    });
                }

                if (overlay) {
                    overlay.addEventListener("click", function(e) {
                        e.preventDefault();
                        closeMenu();
                    });
                }

                // هندل کلیک زیرمنوها در دراور موبایل
                if (drawer) {
                    var itemsWithSub = drawer.querySelectorAll(".menu-item-has-children > a");
                    itemsWithSub.forEach(function(item) {
                        item.addEventListener("click", function(e) {
                            var parentLi = item.parentElement;
                            var sub = parentLi.querySelector(".sub-menu");
                            if (sub) {
                                e.preventDefault();
                                parentLi.classList.toggle("is-open");
                            }
                        });
                    });
                }
            });
        </script>
        <?php
    }

        /**
     * افزودن آیکون‌های شاداب به منوی بالای سایت (Header Dropdown)
     */
    public function enrich_top_menu_icons($title, $item, $args, $depth) {
        if (!isset($args->menu_class) || strpos($args->menu_class, 'gk-nav-links') === false) {
            return $title;
        }
        $clean_title = preg_replace('/^[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]\s*/u', '', $title);
        $clean_title = trim($clean_title);
        $t_lower = mb_strtolower($clean_title);

        $icon = '';
        if (strpos($t_lower, 'دیداری') !== false || strpos($t_lower, 'بصری') !== false) {
            $icon = '👁️ ';
        } elseif (strpos($t_lower, 'حافظه') !== false || strpos($t_lower, 'تمرکز') !== false) {
            $icon = '🧠 ';
        } elseif (strpos($t_lower, 'ریاضی') !== false || strpos($t_lower, 'هوش و ریاضی') !== false) {
            $icon = '🔢 ';
        } elseif (strpos($t_lower, 'سرگرمی') !== false || strpos($t_lower, 'تفریحی') !== false) {
            $icon = '🎪 ';
        } elseif (strpos($t_lower, 'شنیداری') !== false) {
            $icon = '🎧 ';
        } elseif (strpos($t_lower, 'تست') !== false || strpos($t_lower, 'آزمون') !== false) {
            $icon = '📊 ';
        } elseif (strpos($t_lower, 'اشتراک') !== false || strpos($t_lower, 'تعرفه') !== false) {
            $icon = '💎 ';
        } elseif (strpos($t_lower, 'مدارس') !== false || strpos($t_lower, 'مدرسه') !== false) {
            $icon = '🏫 ';
        }

        if ($depth > 0 && !empty($icon)) {
            return $icon . esc_html($clean_title);
        }
        return $title;
    }

    public function fallback_top_menu() {
        ?>
        <ul class="gk-nav-links">
            <li><a href="<?php echo esc_url(home_url('/')); ?>">🏠 صفحه اصلی</a></li>
            <li><a href="<?php echo esc_url(home_url('/games/')); ?>">🎮 بازی‌ها</a></li>
            <li><a href="<?php echo esc_url(home_url('/tests/')); ?>">🧠 تست‌های هوش</a></li>
            <li><a href="<?php echo esc_url(home_url('/schools/')); ?>">🏫 مدارس و مهدها</a></li>
            <li><a href="<?php echo esc_url(home_url('/about-us/')); ?>">📖 درباره ما</a></li>
            <li><a href="<?php echo esc_url(home_url('/contact-us/')); ?>">📞 تماس با ما</a></li>
        </ul>
        <?php
    }

    public function fallback_mobile_menu() {
        ?>
        <ul class="gk-mobile-menu-list">
            <li><a href="<?php echo esc_url(home_url('/')); ?>">🏠 صفحه اصلی</a></li>
            <li><a href="<?php echo esc_url(home_url('/games/')); ?>">🎮 بازی‌های مهارتی و فکری</a></li>
            <li><a href="<?php echo esc_url(home_url('/tests/')); ?>">🧠 تست‌های هوش و استعدادیابی</a></li>
            <li><a href="<?php echo esc_url(home_url('/schools/')); ?>">🏫 سامانه مدارس و مهدکودک‌ها</a></li>
            <li><a href="<?php echo esc_url(home_url('/about-us/')); ?>">📖 درباره قربانی کیدز</a></li>
            <li><a href="<?php echo esc_url(home_url('/contact-us/')); ?>">📞 تماس با ما و پشتیبانی</a></li>
            <li><a href="<?php echo esc_url(home_url('/latest-news/')); ?>">📰 آخرین خبرها و آموزش‌ها</a></li>
        </ul>
        <?php
    }

    public function enrich_mobile_menu_icons($title, $item, $args, $depth) {
        if (!isset($args->menu_class) || strpos($args->menu_class, 'gk-mobile-menu-list') === false) {
            return $title;
        }

        $clean_title = preg_replace('/^[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]\s*/u', '', $title);
        $clean_title = trim($clean_title);

        $icon = '🌟';
        $t_lower = mb_strtolower($clean_title);
        $url = $item->url ?? '';

        if (strpos($t_lower, 'خانه') !== false || strpos($t_lower, 'اصلی') !== false || $url === home_url('/') || $url === home_url()) {
            $icon = '🏠';
        } elseif (strpos($t_lower, 'بازی') !== false) {
            $icon = '🎮';
        } elseif (strpos($t_lower, 'اشتراک') !== false || strpos($t_lower, 'vip') !== false || strpos($t_lower, 'تعرفه') !== false) {
            $icon = '👑';
        } elseif (strpos($t_lower, 'تست') !== false || strpos($t_lower, 'آزمون') !== false || strpos($t_lower, 'هوش') !== false) {
            $icon = '🧠';
        } elseif (strpos($t_lower, 'مدرسه') !== false || strpos($t_lower, 'مدارس') !== false || strpos($t_lower, 'مهد') !== false) {
            $icon = '🏫';
        } elseif (strpos($t_lower, 'درباره') !== false) {
            $icon = '📖';
        } elseif (strpos($t_lower, 'تماس') !== false || strpos($t_lower, 'ارتباط') !== false || strpos($t_lower, 'پشتیبانی') !== false) {
            $icon = '📞';
        } elseif (strpos($t_lower, 'خبر') !== false || strpos($t_lower, 'مقاله') !== false || strpos($t_lower, 'بلاگ') !== false) {
            $icon = '📰';
        } elseif (strpos($t_lower, 'لیگ') !== false || strpos($t_lower, 'مسابقه') !== false) {
            $icon = '🏆';
        }

        return '<span class="gk-m-icon-badge">' . $icon . '</span><span class="gk-m-text">' . esc_html($clean_title) . '</span>';
    }
}
