<?php
/**
 * Class GK_Sidebar
 * Ultra-Luxury, Modern Sidebar with Ambient Pastel Mesh & Flyout Submenus
 */

if (!defined('ABSPATH')) {
    exit;
}

class GK_Sidebar {

    public static function init() {
        $instance = new self();
        add_action('wp_enqueue_scripts', [$instance, 'enqueue_assets'], 10);
        add_action('wp_body_open', [$instance, 'render_sidebar'], 5);
        add_filter('nav_menu_item_title', [$instance, 'enrich_menu_item_icons'], 10, 4);
    }

    public function enqueue_assets() {
        $assets_url = plugins_url('assets', dirname(__FILE__));
        $css_ver = file_exists(dirname(dirname(__FILE__)) . '/assets/css/gk-sidebar.css') ? filemtime(dirname(dirname(__FILE__)) . '/assets/css/gk-sidebar.css') : time();
        wp_enqueue_style('gk-sidebar', $assets_url . '/css/gk-sidebar.css', [], time());
    }

    /**
     * رندر کامل سایدبار در تمام صفحات
     */
    public function render_sidebar() {
        static $rendered = false;
        if ($rendered) return;
        $rendered = true;

        $home_url = home_url('/');
        ?>
        <!-- دکمه شناور باز کردن سایدبار (Floating Open Pill) -->
        <button type="button" id="gkFloatingOpenBtn" class="gk-floating-open-sidebar-btn" aria-label="باز کردن منو" title="منوی اصلی قربانی کیدز">
            <span class="gk-f-icon">✨</span>
            <span class="gk-f-text">منو</span>
        </button>

        <!-- پرده تاریک پشت منو در زمان باز شدن در موبایل یا دسکتاپ -->
        <div id="gkSidebarOverlay" class="gk-sidebar-backdrop"></div>

        <!-- بدنه سایدبار اصلی -->
        <aside id="gkDesktopSidebar" class="gk-desktop-sidebar" role="navigation" aria-label="منوی کناری سایت">
            <div class="gk-sidebar-inner-wrap">
                
                <!-- ۱. هدر شاداب سایدبار با لوگوی گرد و دکمه بستن -->
                <div class="gk-sidebar-brand-box">
                    <button type="button" id="gkSidebarCloseBtn" class="gk-sidebar-minimize-btn" title="جمع کردن منو" aria-label="بستن منو">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="4" y1="6" x2="20" y2="6"></line>
                            <line x1="4" y1="12" x2="20" y2="12"></line>
                            <line x1="4" y1="18" x2="20" y2="18"></line>
                        </svg>
                    </button>
                    <a href="<?php echo esc_url($home_url); ?>" class="gk-sidebar-logo-link" title="صفحه اصلی قربانی کیدز">
                        <img src="https://ghorbanikids.ir/wp-content/uploads/2026/08/ghorbanikids_brand_logo.png" alt="قربانی کیدز" class="gk-sidebar-logo-img" width="76" height="76" data-no-optimize="1" loading="eager" decoding="async" />
                    </a>
                    <span class="gk-sidebar-brand-title">قربانی کیدز</span>
                    
                </div>

                <!-- ۲. منوی ناوبری با آیکون‌های خوش‌رنگ -->
                <nav class="gk-sidebar-nav" id="gkSidebarNav">
                    <?php
                    $menu_to_use = null;
                    if (has_nav_menu('sidebar')) {
                        $menu_to_use = ['theme_location' => 'sidebar'];
                    } elseif (has_nav_menu('primary')) {
                        $menu_to_use = ['theme_location' => 'primary'];
                    } else {
                        $all_menus = wp_get_nav_menus();
                        if (!empty($all_menus)) {
                            $menu_to_use = ['menu' => $all_menus[0]->term_id];
                        }
                    }

                    if ($menu_to_use) {
                        wp_nav_menu(array_merge($menu_to_use, [
                            'container'   => false,
                            'menu_class'  => 'gk-sidebar-menu-list',
                            'depth'       => 2,
                            'fallback_cb' => [$this, 'fallback_sidebar_menu']
                        ]));
                    } else {
                        $this->fallback_sidebar_menu();
                    }
                    ?>
                </nav>

                                <!-- ۳. فوتر شیک و حرفه‌ای سایدبار -->
                <div class="gk-sidebar-footer">
                    <div class="gk-sidebar-socials">
                        <!-- Bale -->
                        <a href="<?php echo esc_url(GK_Utils::get_bale_channel_url()); ?>" target="_blank" rel="noopener noreferrer" class="gk-side-s-btn gk-s-ble" title="پیام‌رسان بله قربانی کیدز">
                            <svg viewBox="0 0 24 24" width="22" height="22" fill="none">
                                <path d="M12 3C7.03 3 3 7.03 3 12c0 1.8.53 3.48 1.45 4.9L3.3 20.3a.8.8 0 0 0 1 .98l3.65-1.05A8.94 8.94 0 0 0 12 21c4.97 0 9-4.03 9-9s-4.03-9-9-9zm-1.5 12.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm3-4a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z" fill="#ffffff"/>
                            </svg>
                        </a>
                        <!-- Instagram -->
                        <a href="<?php echo esc_url(GK_Utils::get_instagram_url()); ?>" target="_blank" rel="noopener noreferrer" class="gk-side-s-btn gk-s-ig" title="صفحه اینستاگرام">
                            <svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                                <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                                <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                            </svg>
                        </a>
                        <!-- Telegram -->
                        <a href="<?php echo esc_url(GK_Utils::get_telegram_url()); ?>" target="_blank" rel="noopener noreferrer" class="gk-side-s-btn gk-s-tg" title="کانال تلگرام">
                            <svg viewBox="0 0 24 24" width="19" height="19" fill="currentColor">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69.01-.03.01-.14-.07-.19-.08-.05-.19-.02-.27 0-.12.03-1.99 1.27-5.61 3.72-.53.36-1.01.54-1.44.53-.47-.01-1.38-.27-2.05-.49-.83-.27-1.49-.42-1.43-.88.03-.24.37-.49 1.02-.75 3.98-1.73 6.64-2.88 7.97-3.44 3.8-1.58 4.59-1.86 5.1-1.87.11 0 .37.03.54.17.14.12.18.28.2.45-.02.07-.02.21-.04.38z"/>
                            </svg>
                        </a>
                    </div>
                    <a href="<?php echo esc_url('tel:' . GK_Utils::get_phone()); ?>" class="gk-sidebar-phone-btn" title="تماس با پشتیبانی">
                        <div class="gk-phone-icon-circle">
                            <svg viewBox="0 0 24 24" width="15" height="15" fill="currentColor">
                                <path d="M6.62 10.79a15.053 15.053 0 006.59 6.59l2.2-2.2a1 1 0 011.02-.24c1.12.37 2.33.57 3.57.57a1 1 0 011 1V20a1 1 0 01-1 1A17 17 0 013 4a1 1 0 011-1h3.5a1 1 0 011 1c0 1.24.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/>
                            </svg>
                        </div>
                        <div class="gk-phone-text-box">
                            <span class="gk-phone-label">پشتیبانی قربانی کیدز:</span>
                            <span class="gk-phone-digits"><?php echo esc_html(GK_Utils::get_phone_display()); ?></span>
                        </div>
                    </a>
                </div>
            </div>
        </aside>

        <script>
        (function() {
            function initSidebar() {
                var sidebar = document.getElementById('gkDesktopSidebar');
                var openBtn = document.getElementById('gkFloatingOpenBtn');
                var closeBtn = document.getElementById('gkSidebarCloseBtn');
                var overlay = document.getElementById('gkSidebarOverlay');
                if (!sidebar) return;

                function openSidebar() {
                    sidebar.classList.remove('is-collapsed');
                    if (openBtn) openBtn.classList.remove('is-visible');
                    if (overlay) overlay.classList.add('is-visible');
                    try { localStorage.setItem('gk_sidebar_collapsed', '0'); } catch(e){}
                }

                function closeSidebar() {
                    sidebar.classList.add('is-collapsed');
                    if (openBtn) openBtn.classList.add('is-visible');
                    if (overlay) overlay.classList.remove('is-visible');
                    try { localStorage.setItem('gk_sidebar_collapsed', '1'); } catch(e){}
                }

                if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
                if (openBtn) openBtn.addEventListener('click', openSidebar);
                if (overlay) overlay.addEventListener('click', closeSidebar);

                                // Handle Submenu on Click & Hover
                var parentItems = sidebar.querySelectorAll('.menu-item-has-children');
                parentItems.forEach(function(item) {
                    var link = item.querySelector(':scope > a');
                    if (link) {
                        link.addEventListener('click', function(e) {
                            var sub = item.querySelector('.sub-menu');
                            if (sub && !item.classList.contains('is-expanded')) {
                                e.preventDefault();
                                item.classList.add('is-expanded');
                            }
                        });
                    }
                });

                // Restore collapsed state
                try {
                    var savedState = localStorage.getItem('gk_sidebar_collapsed');
                    if (savedState === '1') {
                        sidebar.classList.add('is-collapsed');
                        if (openBtn) openBtn.classList.add('is-visible');
                    }
                } catch(e){}
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initSidebar);
            } else {
                initSidebar();
            }
        })();
        </script>
        <?php
    }

    /**
     * تزریق خودکار آیکون‌های شاداب، مرتبط و سه‌بعدی به تمام آیتم‌های منو و زیرمنوهای بازی‌ها
     */
    public function enrich_menu_item_icons($title, $item, $args, $depth) {
        if (!isset($args->menu_class) || strpos($args->menu_class, 'gk-sidebar-menu-list') === false) {
            return $title;
        }

        // اگر آیکون اموجی دارد آن را برداریم تا خودمان در بج شکیل قرار دهیم
        $clean_title = preg_replace('/^[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]\s*/u', '', $title);
        $clean_title = trim($clean_title);

        $icon = '🎮';
        $t_lower = mb_strtolower($clean_title);
        $url = $item->url ?? '';

        // ۱. دسته‌بندی‌های بازی‌ها
        if (strpos($t_lower, 'دیداری') !== false || strpos($t_lower, 'بصری') !== false || strpos($t_lower, 'چشمی') !== false) {
            $icon = '👁️'; // دیداری و بصری
        } elseif (strpos($t_lower, 'حافظه') !== false || strpos($t_lower, 'تمرکز') !== false || strpos($t_lower, 'دقت') !== false) {
            $icon = '🧠'; // حافظه و تمرکز
        } elseif (strpos($t_lower, 'ریاضی') !== false || strpos($t_lower, 'محاسبه') !== false || strpos($t_lower, 'هوش و ریاضی') !== false || strpos($t_lower, 'اعداد') !== false) {
            $icon = '🔢'; // هوش و ریاضی
        } elseif (strpos($t_lower, 'سرگرمی') !== false || strpos($t_lower, 'تفریحی') !== false || strpos($t_lower, 'هیجان') !== false || strpos($t_lower, 'فکری') !== false) {
            $icon = '🎪'; // سرگرمی و تفریحی
        } elseif (strpos($t_lower, 'شنیداری') !== false || strpos($t_lower, 'صوتی') !== false || strpos($t_lower, 'صدا') !== false || strpos($t_lower, 'آهنگ') !== false) {
            $icon = '🎧'; // شنیداری
        } elseif (strpos($t_lower, 'حرکتی') !== false || strpos($t_lower, 'واکنش') !== false || strpos($t_lower, 'سرعت عمل') !== false) {
            $icon = '⚡'; // مهارتی و حرکتی
        } elseif (strpos($t_lower, 'کلمات') !== false || strpos($t_lower, 'الفبا') !== false || strpos($t_lower, 'زبان') !== false || strpos($t_lower, 'ادبی') !== false) {
            $icon = '🔤'; // کلمات و الفبا
        
        // ۲. صفحات اصلی و امکانات
        } elseif (strpos($t_lower, 'خانه') !== false || strpos($t_lower, 'اصلی') !== false || $url === home_url('/') || $url === home_url()) {
            $icon = '🏠';
        } elseif (strpos($t_lower, 'بازی') !== false) {
            $icon = '🎮';
        } elseif (strpos($t_lower, 'اشتراک') !== false || strpos($t_lower, 'قیمت') !== false || strpos($t_lower, 'تعرفه') !== false || strpos($t_lower, 'vip') !== false) {
            $icon = '💎';
        } elseif (strpos($t_lower, 'تست') !== false || strpos($t_lower, 'آزمون') !== false || strpos($t_lower, 'استعدادیابی') !== false) {
            $icon = '📊';
        } elseif (strpos($t_lower, 'مدرسه') !== false || strpos($t_lower, 'مدارس') !== false || strpos($t_lower, 'مهد') !== false) {
            $icon = '🏫';
        } elseif (strpos($t_lower, 'درباره') !== false) {
            $icon = 'ℹ️';
        } elseif (strpos($t_lower, 'تماس') !== false || strpos($t_lower, 'ارتباط') !== false) {
            $icon = '📞';
        } elseif (strpos($t_lower, 'خبر') !== false || strpos($t_lower, 'وبلاگ') !== false || strpos($t_lower, 'مقالات') !== false) {
            $icon = '📰';
        } elseif (strpos($t_lower, 'حساب') !== false || strpos($t_lower, 'پروفایل') !== false || strpos($t_lower, 'ورود') !== false) {
            $icon = '👤';
        } elseif (strpos($t_lower, 'لیگ') !== false || strpos($t_lower, 'مسابقه') !== false || strpos($t_lower, 'جام') !== false || strpos($t_lower, 'قهرمان') !== false) {
            $icon = '🏆';
        }

        $icon_html = '<span class="gk-sidebar-menu-icon" aria-hidden="true">' . $icon . '</span>';
        $title_html = '<span class="gk-sidebar-menu-title">' . esc_html($clean_title) . '</span>';

        return $icon_html . $title_html;
    }

    public function fallback_sidebar_menu() {
        $home_url = home_url('/');
        ?>
        <ul class="gk-sidebar-menu-list">
            <li><a href="<?php echo esc_url($home_url); ?>"><span class="gk-sidebar-menu-icon">🏠</span><span class="gk-sidebar-menu-title">خانه</span></a></li>
            <li><a href="<?php echo esc_url($home_url . 'games/'); ?>"><span class="gk-sidebar-menu-icon">🎮</span><span class="gk-sidebar-menu-title">بازی‌ها</span></a></li>
            <li><a href="<?php echo esc_url($home_url . 'pricing/'); ?>"><span class="gk-sidebar-menu-icon">💎</span><span class="gk-sidebar-menu-title">خرید اشتراک</span></a></li>
            <li><a href="<?php echo esc_url($home_url . 'tests/'); ?>"><span class="gk-sidebar-menu-icon">📊</span><span class="gk-sidebar-menu-title">آزمون‌ها</span></a></li>
            <li><a href="<?php echo esc_url($home_url . 'school-panel/'); ?>"><span class="gk-sidebar-menu-icon">🏫</span><span class="gk-sidebar-menu-title">پنل مدارس</span></a></li>
            <li><a href="<?php echo esc_url($home_url . 'contact-us/'); ?>"><span class="gk-sidebar-menu-icon">📞</span><span class="gk-sidebar-menu-title">تماس با ما</span></a></li>
        </ul>
        <?php
    }
}