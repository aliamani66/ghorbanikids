<?php
/**
 * Layout, Header, Footer & Avada Theme Bridge for GhorbaniKids
 */

if (!defined('ABSPATH')) {
    exit;
}

class GK_Theme_Bridge {

    public static function init() {
        $instance = new self();

        // لود استایل‌های یکپارچه و بهینه‌شده
        add_action('wp_enqueue_scripts', [$instance, 'enqueue_frontend_assets'], 20);

        // پیش‌لود فونت‌ها و منابع ضروری
        add_action('wp_head', [$instance, 'inject_resource_preloads'], 1);

        // هدر و نوار ناوبری اختصاصی
        add_action('avada_before_body_content', [$instance, 'inject_top_navbar'], 1);
        add_action('wp_body_open', [$instance, 'inject_top_navbar'], 1);
        add_action('wp_footer', [$instance, 'inject_top_navbar_fallback'], 1);

        // تب‌های ورود و اسکریپت آیکون‌های سایدبار/فوتر
        add_action('wp_footer', [$instance, 'replace_avada_social_icons_script']);
        // duplicate auth tabs handled in GK_Auth

        // فوتر و کپی‌رایت اختصاصی
        add_action('avada_footer_copyright_content', [$instance, 'render_custom_footer_copyright'], 1);

        // کلاس‌های بدنه قالب
        add_filter('body_class', [$instance, 'enforce_right_side_header_body_class'], 999);
    }

    public function enqueue_frontend_assets() {
        $assets_url = plugins_url('assets', dirname(__FILE__));
        
        // استایل جامع ماژول بازی‌ها و تسویه‌حساب
        if (!is_page('games') && !is_tax(['game_age_group', 'game_category'])) { wp_enqueue_style('gk-games-main', $assets_url . '/css/gk-games-main.css', [], '6.0.0'); }

        // اسکریپت‌های کمکی
        if (is_page(['games', 'games-catalog']) || is_tax(['game_age_group', 'game_category'])) {
            wp_enqueue_style('gk-catalog', $assets_url . '/css/gk-catalog.css', [], '6.1.0');
            wp_enqueue_script('gk-catalog-filter', $assets_url . '/js/gk-catalog-filter.js', [], '5.7.1', true);
        }
        if (is_singular('gk_game')) {
            wp_enqueue_script('gk-player', $assets_url . '/js/gk-player.js', [], '5.7.1', true);
        }
        if (is_account_page() && !is_user_logged_in()) {
            wp_enqueue_script('gk-auth-tabs', $assets_url . '/js/gk-auth-tabs.js', [], '5.7.1', true);
        }
        wp_enqueue_script('gk-social-icons', $assets_url . '/js/gk-social-icons.js', [], '5.7.1', true);
    }

    public function inject_resource_preloads() {
        ?>
        <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="dns-prefetch" href="//fonts.googleapis.com">
        <link rel="dns-prefetch" href="//fonts.gstatic.com">
        <?php
    }

    public function enforce_right_side_header_body_class($classes) {
        $classes[] = 'gk-custom-layout-active';
        return $classes;
    }

    public function inject_auth_tabs_script() {
        if (is_account_page() && !is_user_logged_in()) {
            ?>
            <script data-no-optimize="1">
                document.addEventListener("DOMContentLoaded", function() {
                    var customerLogin = document.getElementById("customer_login");
                    if (!customerLogin) return;

                    var col1 = customerLogin.querySelector(".col-1") || customerLogin.querySelector(".u-column1");
                    var col2 = customerLogin.querySelector(".col-2") || customerLogin.querySelector(".u-column2");

                    if (col1 && col2) {
                        if (!document.getElementById("gkAuthTabsHeader")) {
                            var tabsHeader = document.createElement("div");
                            tabsHeader.id = "gkAuthTabsHeader";
                            tabsHeader.className = "gk-auth-tabs-header";
                            tabsHeader.innerHTML = `
                                <button type="button" class="gk-auth-tab-btn active" data-target="login">🔑 ورود به حساب</button>
                                <button type="button" class="gk-auth-tab-btn" data-target="register">✨ عضویت و ثبت‌نام</button>
                            `;
                            customerLogin.parentNode.insertBefore(tabsHeader, customerLogin);

                            var tabBtns = tabsHeader.querySelectorAll(".gk-auth-tab-btn");
                            tabBtns.forEach(function(btn) {
                                btn.addEventListener("click", function(e) {
                                    e.preventDefault();
                                    tabBtns.forEach(function(b) { b.classList.remove("active"); });
                                    btn.classList.add("active");

                                    var target = btn.getAttribute("data-target");
                                    if (target === "login") {
                                        customerLogin.classList.remove("show-register");
                                        customerLogin.classList.add("show-login");
                                    } else {
                                        customerLogin.classList.remove("show-login");
                                        customerLogin.classList.add("show-register");
                                    }
                                });
                            });
                        }

                        // پیش‌فرض نمایش ورود
                        customerLogin.classList.remove("show-register");
                        customerLogin.classList.add("show-login");
                    }
                });
            </script>
            <?php
        }
    }

    /**
     * جایگزینی دقیق و شیک آیکون‌های قدیمی سایدبار و منوی موبایل با آیکون‌های اینستاگرام، تلگرام، بله و شماره تماس موبایل
     */
    public function replace_avada_social_icons_script() {
        ?>
        <script data-no-optimize="1">
            document.addEventListener("DOMContentLoaded", function() {
                var socialHtml = `
                    <div class="gk-social-replacement-box">
                        <div class="gk-social-links-row">
                            <a href="https://instagram.com/ghorbanikids" target="_blank" rel="noopener" class="gk-s-btn gk-s-instagram" title="اینستاگرام: @ghorbanikids">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                                    <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                                    <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                                </svg>
                            </a>
                            <a href="https://t.me/ghorbanikids" target="_blank" rel="noopener" class="gk-s-btn gk-s-telegram" title="تلگرام: @ghorbanikids">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69.01-.03.01-.14-.07-.19-.08-.05-.19-.02-.27 0-.12.03-1.99 1.27-5.61 3.72-.53.36-1.01.54-1.44.53-.47-.01-1.38-.27-2.05-.49-.83-.27-1.49-.42-1.43-.88.03-.24.37-.49 1.02-.75 3.98-1.73 6.64-2.88 7.97-3.44 3.8-1.58 4.59-1.86 5.1-1.87.11 0 .37.03.54.17.14.12.18.28.2.45-.02.07-.02.21-.04.38z"/>
                                </svg>
                            </a>
                            <a href="https://ble.ir/ghorbanikids" target="_blank" rel="noopener" class="gk-s-btn gk-s-bale" title="پیام‌رسان بله: @ghorbanikids">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
                                    <rect width="24" height="24" rx="6" fill="#14b8a6"/>
                                    <path d="M7 6h10a1 1 0 0 1 1 1v6a5 5 0 0 1-5 5H7a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1zm3 8h4a2 2 0 0 0 2-2V9H10v5z" fill="#ffffff"/>
                                </svg>
                            </a>
                        </div>
                        <div class="gk-social-phone-row">
                            <a href="tel:09306197877" class="gk-s-phone-box" title="تماس مستقیم و پشتیبانی">
                                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                                </svg>
                                <span>۰۹۳۰ ۶۱۹ ۷۸۷۷</span>
                            </a>
                        </div>
                    </div>
                `;

                // جایگزینی در سایدبار دسکتاپ
                var target = document.querySelector("#side-header .fusion-social-links-header") || 
                             document.querySelector("#side-header .fusion-social-networks") ||
                             document.querySelector("#side-header .fusion-social-networks-wrapper") ||
                             document.querySelector("#side-header .fusion-builder-column:last-child .fusion-column-wrapper");
                if (target) {
                    target.innerHTML = socialHtml;
                }

                // درج در منوی موبایل
                var mobileNav = document.querySelector(".fusion-mobile-nav-holder") || document.querySelector(".awb-menu__mobile-container");
                if (mobileNav && !document.getElementById("gkMobileSocialInjected")) {
                    var mobileDiv = document.createElement("div");
                    mobileDiv.id = "gkMobileSocialInjected";
                    mobileDiv.style.padding = "15px 0";
                    mobileDiv.innerHTML = socialHtml;
                    mobileNav.appendChild(mobileDiv);
                }

                // حذف باکس‌های اشتراک‌گذاری قدیمی
                var shareBoxes = document.querySelectorAll(".fusion-sharing-box, .fusion-social-sharing, .sharing-box");
                shareBoxes.forEach(function(box) {
                    box.remove();
                });
            });
        </script>
        <?php
    }

    /**
     * چاپ آیکون‌های شبکه‌های اجتماعی و متن کپی‌رایت اختصاصی قربانی کیدز در فوتر
     */
    public function render_custom_footer_copyright() {
        remove_action('avada_footer_copyright_content', 'avada_render_footer_copyright_notice', 10);
        ?>
        <div class="gk-footer-content-box" style="text-align: center; padding: 20px 10px; direction: rtl;">
            <div class="gk-footer-social-row" style="display: flex; justify-content: center; align-items: center; gap: 14px; margin-bottom: 14px;">
                <a href="https://instagram.com/ghorbanikids" target="_blank" rel="noopener" class="gk-s-btn gk-s-instagram" title="اینستاگرام: @ghorbanikids">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                        <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                        <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                    </svg>
                </a>
                <a href="https://t.me/ghorbanikids" target="_blank" rel="noopener" class="gk-s-btn gk-s-telegram" title="تلگرام: @ghorbanikids">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69.01-.03.01-.14-.07-.19-.08-.05-.19-.02-.27 0-.12.03-1.99 1.27-5.61 3.72-.53.36-1.01.54-1.44.53-.47-.01-1.38-.27-2.05-.49-.83-.27-1.49-.42-1.43-.88.03-.24.37-.49 1.02-.75 3.98-1.73 6.64-2.88 7.97-3.44 3.8-1.58 4.59-1.86 5.1-1.87.11 0 .37.03.54.17.14.12.18.28.2.45-.02.07-.02.21-.04.38z"/>
                    </svg>
                </a>
                <a href="https://ble.ir/ghorbanikids" target="_blank" rel="noopener" class="gk-s-btn gk-s-bale" title="پیام‌رسان بله: @ghorbanikids">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                        <rect width="24" height="24" rx="6" fill="#14b8a6"/>
                        <path d="M7 6h10a1 1 0 0 1 1 1v6a5 5 0 0 1-5 5H7a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1zm3 8h4a2 2 0 0 0 2-2V9H10v5z" fill="#ffffff"/>
                    </svg>
                </a>
            </div>
            <div class="gk-footer-copyright-text" style="color: #cbd5e1; font-size: 0.95rem; font-weight: 700; text-align: center; width: 100%; margin: 0 auto; display: block;">
                © کلیه حقوق مادی و معنوی این سایت متعلق به قربانی کیدز (GhorbaniKids) می‌باشد.
            </div>
        </div>
        
        <?php
    }

    public function inject_top_navbar() {
        static $rendered = false;
        if ($rendered) return;
        $rendered = true;

        $cart_count = 0;
        if (function_exists('WC') && WC()->cart) {
            $cart_count = WC()->cart->get_cart_contents_count();
        }
        $is_logged_in = is_user_logged_in();
        $account_url = wc_get_page_permalink('myaccount') ?: home_url('/my-account/');
        $cart_url = wc_get_page_permalink('cart') ?: home_url('/cart/');
        $logo_url = 'https://ghorbanikids.ir/wp-content/uploads/2026/08/ghorbanikids_brand_logo.png';

        // تشخیص وضعیت صفحه جاری برای هایلایت منوها
        $cur_uri = urldecode($_SERVER['REQUEST_URI'] ?? '/');
        $cur_path = untrailingslashit(strtok($cur_uri, '?'));
        $cur_cat = isset($_GET['cat']) ? sanitize_text_field(urldecode($_GET['cat'])) : '';
        $cur_age = isset($_GET['age']) ? sanitize_text_field(urldecode($_GET['age'])) : '';
        $is_games_page = is_page('games') || (strpos($cur_path, '/games') !== false) || is_singular('gk_game') || is_tax(['game_category', 'game_age_group']) || !empty($cur_cat) || !empty($cur_age);

        // ساخت درخت منو از منوی وردپرس
        $menu_tree = [];
        $locations = get_nav_menu_locations();
        if (!empty($locations['main_navigation'])) {
            $wp_items = wp_get_nav_menu_items($locations['main_navigation']);
            if (!empty($wp_items)) {
                $items_by_id = [];
                foreach ($wp_items as $item) {
                    $items_by_id[$item->ID] = [
                        'id' => $item->ID,
                        'title' => $item->title,
                        'url' => $item->url,
                        'parent' => $item->menu_item_parent,
                        'badge' => (stripos($item->url, 'pricing') !== false) ? 'ویژه' : '',
                        'children' => [],
                    ];
                }
                foreach ($items_by_id as $id => &$item_ref) {
                    if ($item_ref['parent'] == 0) {
                        $menu_tree[$id] = &$item_ref;
                    } else if (isset($items_by_id[$item_ref['parent']])) {
                        $items_by_id[$item_ref['parent']]['children'][] = &$item_ref;
                    }
                }
            }
        }

        if (empty($menu_tree)) {
            $menu_tree = [
                ['title' => 'خانه', 'url' => home_url('/'), 'children' => []],
                ['title' => 'بازی‌ها', 'url' => home_url('/games/'), 'children' => [
                    ['title' => 'همه بازی‌ها', 'url' => home_url('/games/')],
                    ['title' => 'دیداری و بصری', 'url' => home_url('/games/?cat=دیداری-و-بصری')],
                    ['title' => 'حافظه و تمرکز', 'url' => home_url('/games/?cat=حافظه-و-تمرکز')],
                    ['title' => 'هوش و ریاضی', 'url' => home_url('/games/?cat=هوش-و-ریاضی')],
                    ['title' => 'سرگرمی و تفریحی', 'url' => home_url('/games/?cat=سرگرمی-و-تفریحی')],
                    ['title' => 'شنیداری', 'url' => home_url('/games/?cat=شنیداری')],
                ]],
                ['title' => 'تست‌ها و استعدادیابی', 'url' => home_url('/tests/'), 'badge' => 'جدید', 'children' => []],
                ['title' => 'خرید اشتراک', 'url' => home_url('/pricing/'), 'badge' => 'ویژه', 'children' => []],
                ['title' => 'آخرین خبرها', 'url' => home_url('/latest-news/'), 'children' => []],
                ['title' => 'درباره ما', 'url' => home_url('/about-us/'), 'children' => []],
                ['title' => 'تماس با ما', 'url' => home_url('/contact-us/'), 'children' => []],
            ];
        }
        ?>
        
        <header id="gkTopNavbar" class="gk-top-navbar">
            <div class="gk-navbar-container">
                <!-- ۱. لوگو و برند (راست) -->
                <div class="gk-navbar-brand-col">
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="gk-navbar-brand" title="قربانی کیدز - صفحه اصلی">
                        <img src="<?php echo esc_url($logo_url); ?>" alt="قربانی کیدز" class="gk-navbar-logo" width="46" height="46" />
                        <span class="gk-navbar-brand-title">قربانی کیدز</span>
                    </a>
                </div>

                <!-- ۲. منوی اصلی دسکتاپ (وسط) -->
                <nav class="gk-navbar-nav-col" aria-label="منوی ناوبری اصلی">
                    <ul class="gk-nav-list">
                        <?php foreach ($menu_tree as $item): 
                            $item_url_clean = urldecode(untrailingslashit($item['url']));
                            $home_url_clean = urldecode(untrailingslashit(home_url('/')));
                            $is_home_item = ($item_url_clean === $home_url_clean);

                            $is_active = false;
                            if ($is_home_item && (is_front_page() || is_home())) {
                                $is_active = true;
                            } else if ($item['title'] === 'بازی‌ها' && $is_games_page) {
                                $is_active = true;
                            } else if ($item_url_clean === urldecode(untrailingslashit(home_url($cur_path)))) {
                                $is_active = true;
                            }

                            $has_children = !empty($item['children']);
                            $child_rendered_active = false;
                        ?>
                            <li class="gk-nav-item <?php echo $is_active ? 'current-menu-item' : ''; ?> <?php echo $has_children ? 'has-dropdown' : ''; ?>">
                                <a href="<?php echo esc_url($item['url']); ?>" class="gk-nav-link">
                                    <span class="gk-nav-text"><?php echo esc_html($item['title']); ?></span>
                                    <?php if (!empty($item['badge'])): ?>
                                        <span class="gk-nav-badge"><?php echo esc_html($item['badge']); ?></span>
                                    <?php endif; ?>
                                    <?php if ($has_children): ?>
                                        <svg class="gk-dropdown-arrow" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="6 9 12 15 18 9"></polyline>
                                        </svg>
                                    <?php endif; ?>
                                </a>

                                <?php if ($has_children): ?>
                                    <ul class="gk-dropdown-menu">
                                        <?php foreach ($item['children'] as $child): 
                                            $child_url_clean = urldecode(untrailingslashit($child['url']));
                                            $is_child_active = false;
                                            
                                            if (!empty($cur_cat) && (stripos($child['title'], $cur_cat) !== false || stripos($child['url'], $cur_cat) !== false)) {
                                                $is_child_active = true;
                                            } else if (empty($cur_cat) && $is_games_page && ($child['title'] === 'همه بازی‌ها' || $child_url_clean === urldecode(home_url('/games')))) {
                                                $is_child_active = true;
                                            }
                                        ?>
                                            <li class="gk-dropdown-item <?php echo $is_child_active ? 'current-menu-item' : ''; ?>">
                                                <a href="<?php echo esc_url($child['url']); ?>" class="gk-dropdown-link">
                                                    <span><?php echo esc_html($child['title']); ?></span>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </nav>

                <!-- ۳. ابزارهای کاربری، سبد خرید و ورود (چپ) -->
                <div class="gk-navbar-tools-col">


                    <!-- سبد خرید -->
                    <a href="<?php echo esc_url($cart_url); ?>" class="gk-tool-btn gk-tool-cart" title="سبد خرید">
                        <svg class="gk-svg-icon" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="9" cy="21" r="1"></circle>
                            <circle cx="20" cy="21" r="1"></circle>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                        </svg>
                        <?php if ($cart_count > 0): ?>
                            <span class="gk-cart-count-badge"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </a>

                    <!-- حساب کاربری / خروج / ورود -->
                    <?php if ($is_logged_in): ?>
                        <a href="<?php echo esc_url($account_url); ?>" class="gk-tool-btn gk-tool-user" title="حساب کاربری من">
                            <svg class="gk-svg-icon" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </a>
                        <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="gk-tool-btn gk-tool-logout" title="خروج از حساب">
                            <svg class="gk-svg-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                <polyline points="16 17 21 12 16 7"></polyline>
                                <line x1="21" y1="12" x2="9" y2="12"></line>
                            </svg>
                        </a>
                    <?php else: ?>
                        <a href="<?php echo esc_url($account_url); ?>" class="gk-tool-btn gk-tool-login" title="ورود یا عضویت در سایت">
                            <svg class="gk-svg-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            <span class="gk-login-label">ورود / ثبت‌نام</span>
                        </a>
                    <?php endif; ?>

                    <!-- دکمه همبرگری موبایل -->
                    <button type="button" id="gkMobileMenuToggle" class="gk-mobile-toggle-btn" aria-label="منوی موبایل">
                        <span class="gk-hamburger-icon">
                            <span></span>
                            <span></span>
                            <span></span>
                        </span>
                    </button>
                </div>
            </div>

            <!-- منوی دراور موبایل -->
            <div id="gkMobileMenuDrawer" class="gk-mobile-drawer">
                <div class="gk-mobile-drawer-inner">
                    <ul class="gk-mobile-nav-list">
                        <?php foreach ($menu_tree as $item): 
                            $item_url_clean = urldecode(untrailingslashit($item['url']));
                            $home_url_clean = urldecode(untrailingslashit(home_url('/')));
                            $is_home_item = ($item_url_clean === $home_url_clean);

                            $is_active = false;
                            if ($is_home_item && (is_front_page() || is_home())) {
                                $is_active = true;
                            } else if ($item['title'] === 'بازی‌ها' && $is_games_page) {
                                $is_active = true;
                            } else if ($item_url_clean === urldecode(untrailingslashit(home_url($cur_path)))) {
                                $is_active = true;
                            }

                            $has_children = !empty($item['children']);
                        ?>
                            <li class="gk-mobile-nav-item <?php echo $is_active ? 'active' : ''; ?> <?php echo $has_children ? 'has-mobile-sub' : ''; ?>">
                                <div class="gk-mobile-item-head">
                                    <a href="<?php echo esc_url($item['url']); ?>" class="gk-mobile-nav-link">
                                        <span class="gk-mobile-nav-text"><?php echo esc_html($item['title']); ?></span>
                                        <?php if (!empty($item['badge'])): ?>
                                            <span class="gk-nav-badge"><?php echo esc_html($item['badge']); ?></span>
                                        <?php endif; ?>
                                    </a>
                                    <?php if ($has_children): ?>
                                        <button type="button" class="gk-mobile-sub-toggle" aria-label="نمایش زیرمنو">
                                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="6 9 12 15 18 9"></polyline>
                                            </svg>
                                        </button>
                                    <?php endif; ?>
                                </div>

                                <?php if ($has_children): ?>
                                    <ul class="gk-mobile-sub-list">
                                        <?php foreach ($item['children'] as $child): 
                                            $child_url_clean = urldecode(untrailingslashit($child['url']));
                                            $is_child_active = false;
                                            if (!empty($cur_cat) && (stripos($child['title'], $cur_cat) !== false || stripos($child['url'], $cur_cat) !== false)) {
                                                $is_child_active = true;
                                            } else if (empty($cur_cat) && $is_games_page && ($child['title'] === 'همه بازی‌ها' || $child_url_clean === urldecode(home_url('/games')))) {
                                                $is_child_active = true;
                                            }
                                        ?>
                                            <li class="gk-mobile-sub-item <?php echo $is_child_active ? 'active' : ''; ?>">
                                                <a href="<?php echo esc_url($child['url']); ?>" class="gk-mobile-sub-link">
                                                    <span><?php echo esc_html($child['title']); ?></span>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <div class="gk-mobile-drawer-footer">
                        <a href="tel:09306197877" class="gk-mobile-phone-btn">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                            </svg>
                            <span>۰۹۳۰ ۶۱۹ ۷۸۷۷</span>
                        </a>
                    </div>
                </div>
            </div>
        </header>
        <script data-no-optimize="1">
            document.addEventListener('DOMContentLoaded', function() {
                var toggleBtn = document.getElementById('gkMobileMenuToggle');
                var drawer = document.getElementById('gkMobileMenuDrawer');
                if (toggleBtn && drawer) {
                    toggleBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        toggleBtn.classList.toggle('is-open');
                        drawer.classList.toggle('is-open');
                    });
                }

                var subToggles = document.querySelectorAll('.gk-mobile-sub-toggle');
                subToggles.forEach(function(btn) {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        var parentItem = btn.closest('.gk-mobile-nav-item');
                        if (parentItem) {
                            parentItem.classList.toggle('open-sub');
                        }
                    });
                });
            });
        </script>
        <?php
    }

    public function inject_top_navbar_fallback() {
        // Fallback: If not already rendered, inject into body
        $this->inject_top_navbar();
    }

}
