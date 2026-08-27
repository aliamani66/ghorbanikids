<?php
/**
 * Footer, Social Links & Contact Support Module for GhorbaniKids (100% Modular)
 */

if (!defined('ABSPATH')) {
    exit;
}

class GK_Footer {

    public static function init() {
        $instance = new self();
        add_action('wp_enqueue_scripts', [$instance, 'enqueue_assets'], 20);
        add_action('gk_footer_content', [$instance, 'render_luxury_footer'], 1);
        add_action('avada_footer_copyright_content', [$instance, 'render_luxury_footer'], 1);
    }

    public function enqueue_assets() {
        $assets_url = plugins_url('assets', dirname(__FILE__));
        wp_enqueue_style('gk-footer', $assets_url . '/css/gk-footer.css', [], '4.0.0');
    }

    public function render_luxury_footer() {
        static $rendered = false;
        if ($rendered) return;
        $rendered = true;

        remove_action('avada_footer_copyright_content', 'avada_render_footer_copyright_notice', 10);
        ?>
        <!-- موج نرم اقیانوسی در بالای فوتر -->
        <div class="gk-footer-wave-divider">
            <svg viewBox="0 0 1440 80" fill="none" preserveAspectRatio="none">
                <path d="M0,32L60,42.7C120,53,240,75,360,69.3C480,64,600,32,720,26.7C840,21,960,43,1080,48C1200,53,1320,43,1380,37.3L1440,32L1440,80L1380,80C1320,80,1200,80,1080,80C960,80,840,80,720,80C600,80,480,80,360,80C240,80,120,80,60,80L0,80Z" fill="#0284c7"></path>
            </svg>
        </div>

        <div class="gk-luxury-footer-container">
            <div class="gk-footer-top-row">
                <!-- ستون برند و درباره سامانه -->
                <div class="gk-footer-brand-col gk-footer-card-col">
                    <div class="gk-footer-brand-title">
                        <div class="gk-footer-logo-badge">🌟</div>
                        <span>قربانی کیدز</span>
                    </div>
                    <p class="gk-footer-brand-desc">
                        سامانه تخصصی بازی‌های فکری، تقویت هوش و تمرکز، سنجش روان‌شناختی و لیگ‌های مسابقات کلاسی برای کودکان، مهدها و مدارس سراسر کشور.
                    </p>
                    <div class="gk-footer-safe-badge">
                        <span>🛡️ محیط ۱۰۰٪ امن، استاندارد و بدون تبلیغات</span>
                    </div>
                </div>

                <!-- ستون دسترسی سریع -->
                <div class="gk-footer-nav-col gk-footer-card-col">
                    <h4 class="gk-footer-heading">🚀 دسترسی‌های سریع</h4>
                    <ul class="gk-footer-links-list">
                        <li><a href="<?php echo esc_url(home_url('/')); ?>">🏠 صفحه اصلی</a></li>
                        <li><a href="<?php echo esc_url(home_url('/games/')); ?>">🎮 سالن بازی‌ها</a></li>
                        <li><a href="<?php echo esc_url(home_url('/tests/')); ?>">🧠 آزمون‌های استعدادیابی</a></li>
                        <li><a href="<?php echo esc_url(home_url('/school-panel/')); ?>">🏫 پنل مدیریت مدارس</a></li>
                        <li><a href="<?php echo esc_url(home_url('/pricing/')); ?>">👑 خرید اشتراک VIP</a></li>
                    </ul>
                </div>

                <!-- ستون ارتباط و پشتیبانی -->
                <div class="gk-footer-contact-col gk-footer-card-col">
                    <h4 class="gk-footer-heading">📞 ارتباط و پشتیبانی</h4>
                    <div class="gk-footer-social-row">
                        <a href="<?php echo esc_url(GK_Utils::get_instagram_url()); ?>" target="_blank" rel="noopener" class="gk-s-btn gk-s-instagram" title="اینستاگرام">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
                        </a>
                        <a href="<?php echo esc_url(GK_Utils::get_telegram_url()); ?>" target="_blank" rel="noopener" class="gk-s-btn gk-s-telegram" title="تلگرام">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69.01-.03.01-.14-.07-.19-.08-.05-.19-.02-.27 0-.12.03-1.99 1.27-5.61 3.72-.53.36-1.01.54-1.44.53-.47-.01-1.38-.27-2.05-.49-.83-.27-1.49-.42-1.43-.88.03-.24.37-.49 1.02-.75 3.98-1.73 6.64-2.88 7.97-3.44 3.8-1.58 4.59-1.86 5.1-1.87.11 0 .37.03.54.17.14.12.18.28.2.45-.02.07-.02.21-.04.38z"/></svg>
                        </a>
                        <a href="<?php echo esc_url(GK_Utils::get_ble_url()); ?>" target="_blank" rel="noopener" class="gk-s-btn gk-s-bale" title="پیام‌رسان بله">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><rect width="24" height="24" rx="6" fill="#14b8a6"/><path d="M7 6h10a1 1 0 0 1 1 1v6a5 5 0 0 1-5 5H7a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1zm3 8h4a2 2 0 0 0 2-2V9H10v5z" fill="#ffffff"/></svg>
                        </a>
                    </div>
                                        <a href="tel:<?php echo esc_html(GK_Utils::get_phone()); ?>" class="gk-footer-phone-pill" title="تماس مستقیم با پشتیبانی">
                        <span style="font-size: 18px;">📞</span>
                        <span>پشتیبانی و مشاوره: </span>
                        <strong style="font-family:'IRANSansXFaNum',sans-serif; letter-spacing:0.5px;"><?php echo esc_html(GK_Utils::get_phone()); ?></strong>
                    </a>
                </div>
            </div>

            <div class="gk-footer-bottom-bar">
                <div class="gk-footer-copyright-text">
                    © تمامی حقوق مادی و معنوی متعلق به <strong>قربانی کیدز (GhorbaniKids)</strong> می‌باشد.
                </div>
            </div>
        </div>
        <?php
    }
}