<?php
/**
 * Class GK_Pricing
 * Renders the High-Converting, Luxury Pricing & Subscription Page
 * Supports both Parents (B2C) and Schools/Kindergartens (B2B) via Smart Segment Switcher
 */
if (!defined('ABSPATH')) exit;

class GK_Pricing {

    public static function init() {
        add_shortcode('ghorbanikids_pricing', [__CLASS__, 'render_pricing_table_shortcode']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
    }

    public static function enqueue_assets() {
        // Assets handled inline for maximum performance and isolation
    }

    public static function render_pricing_table_shortcode($atts = []) {
        $atts = shortcode_atts([
            'default_tab' => 'parents' // 'parents' or 'schools'
        ], $atts);

        // Parent Plans
        $p_1m_id = 687; // 1 month
        $p_3m_id = 688; // 3 months
        $p_6m_id = 689; // 6 months
        $p_1y_id = 690; // 1 year gold

        // School Plans
        $p_starter_id    = 809; // school-starter-30
        $p_standard_id   = 810; // school-standard-80
        $p_enterprise_id = 811; // school-enterprise-200

        $checkout_url = wc_get_checkout_url();
        $cart_url     = wc_get_checkout_url();

        // Direct buy URLs
        $url_1m = add_query_arg('add-to-cart', $p_1m_id, $checkout_url);
        $url_3m = add_query_arg('add-to-cart', $p_3m_id, $checkout_url);
        $url_6m = add_query_arg('add-to-cart', $p_6m_id, $checkout_url);
        $url_1y = add_query_arg('add-to-cart', $p_1y_id, $checkout_url);

        $url_starter    = add_query_arg('add-to-cart', $p_starter_id, $checkout_url);
        $url_standard   = add_query_arg('add-to-cart', $p_standard_id, $checkout_url);
        $url_enterprise = add_query_arg('add-to-cart', $p_enterprise_id, $checkout_url);

        ob_start();
        ?>
        <style>
            @font-face {
                font-family: 'aviny';
                src: url('/wp-content/uploads/2021/10/aviny-web.woff2') format('woff2'),
                     url('/wp-content/uploads/2021/10/aviny-web.woff') format('woff'),
                     url('/wp-content/uploads/2021/10/aviny.ttf') format('truetype');
                font-weight: normal;
                font-style: normal;
                font-display: swap;
            }
            @font-face {
                font-family: 'IRANSansXFaNum';
                src: url('/wp-content/uploads/2021/10/IRANSansXFaNum-Regular.woff2') format('woff2'),
                     url('/wp-content/uploads/2021/10/IRANSansXFaNum-Regular.woff') format('woff'),
                     url('/wp-content/uploads/2021/10/IRANSansXFaNum-Regular.ttf') format('truetype');
                font-weight: normal;
                font-style: normal;
                font-display: swap;
            }

            .gk-pricing-page-wrapper, .gk-pricing-page-wrapper * {
                font-family: 'IRANSansXFaNum', 'IRANSansX', 'IRANSans', 'Vazirmatn', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
                box-sizing: border-box !important;
                -webkit-font-smoothing: antialiased;
            }

            .gk-pricing-hero-title, .gk-pricing-card-title, .gk-section-heading {
                font-family: 'aviny', 'Aviny', 'IRANSansXFaNum', sans-serif !important;
            }

            .gk-pricing-page-wrapper {
                direction: rtl !important;
                text-align: right !important;
                width: 100% !important;
                max-width: 1240px !important;
                margin: 10px auto 50px auto !important;
                padding: 0 14px !important;
            }

            /* هدر صفحه */
            .gk-pricing-header {
                text-align: center !important;
                margin-bottom: 28px !important;
                padding: 10px 0 !important;
            }
            .gk-pricing-badge-pill {
                display: inline-flex !important;
                align-items: center !important;
                gap: 6px !important;
                background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%) !important;
                color: #16a34a !important;
                border: 1.5px solid #86efac !important;
                padding: 6px 16px !important;
                border-radius: 99px !important;
                font-size: 13px !important;
                font-weight: 800 !important;
                margin-bottom: 14px !important;
                box-shadow: 0 4px 12px rgba(22, 163, 74, 0.08) !important;
            }
            .gk-pricing-hero-title {
                font-size: 32px !important;
                font-weight: 900 !important;
                color: #0f172a !important;
                margin: 0 0 10px 0 !important;
                letter-spacing: 0.5px !important;
                line-height: 1.3 !important;
            }
            .gk-pricing-hero-subtitle {
                font-size: 14.5px !important;
                color: #64748b !important;
                max-width: 620px !important;
                margin: 0 auto !important;
                line-height: 1.6 !important;
            }

            /* سوییچر تب هوشمند */
            .gk-pricing-switcher-wrap {
                display: flex !important;
                justify-content: center !important;
                margin-bottom: 32px !important;
            }
            .gk-pricing-switcher {
                display: inline-flex !important;
                background: #f1f5f9 !important;
                padding: 5px !important;
                border-radius: 20px !important;
                border: 1.5px solid #cbd5e1 !important;
                box-shadow: inset 0 2px 6px rgba(0,0,0,0.04) !important;
                gap: 4px !important;
            }
            .gk-switch-tab-btn {
                background: transparent !important;
                border: none !important;
                padding: 10px 22px !important;
                border-radius: 16px !important;
                font-size: 13.5px !important;
                font-weight: 900 !important;
                color: #475569 !important;
                cursor: pointer !important;
                transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1) !important;
                display: inline-flex !important;
                align-items: center !important;
                gap: 8px !important;
            }
            .gk-switch-tab-btn:hover {
                color: #0f172a !important;
            }
            .gk-switch-tab-btn.active {
                background: #ffffff !important;
                color: #0284c7 !important;
                box-shadow: 0 4px 14px rgba(2, 132, 199, 0.18) !important;
            }

            /* گرید کارت‌های اشتراک */
            .gk-pricing-grid {
                display: grid !important;
                grid-template-columns: repeat(4, 1fr) !important;
                gap: 18px !important;
                width: 100% !important;
                box-sizing: border-box !important;
                align-items: stretch !important;
            }
            .gk-school-grid {
                grid-template-columns: repeat(3, 1fr) !important;
                max-width: 1080px !important;
                margin: 0 auto !important;
            }

            /* کارت قیمت‌گذاری */
            .gk-plan-card {
                background: #ffffff !important;
                border: 2px solid #e2e8f0 !important;
                border-radius: 24px !important;
                padding: 24px 20px !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
                position: relative !important;
                transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1) !important;
                box-shadow: 0 4px 20px rgba(15, 23, 42, 0.03) !important;
            }
            .gk-plan-card:hover {
                transform: translateY(-6px) !important;
                border-color: #0284c7 !important;
                box-shadow: 0 16px 36px rgba(2, 132, 199, 0.12) !important;
            }

            /* کارت ویژه طلایی / استاندارد */
            .gk-plan-featured {
                border-color: #6366f1 !important;
                background: linear-gradient(180deg, #faf5ff 0%, #ffffff 100%) !important;
                box-shadow: 0 8px 30px rgba(99, 102, 241, 0.14) !important;
                transform: scale(1.03) !important;
            }
            .gk-plan-featured:hover {
                transform: scale(1.03) translateY(-6px) !important;
                border-color: #4f46e5 !important;
                box-shadow: 0 20px 45px rgba(99, 102, 241, 0.22) !important;
            }

            .gk-featured-badge {
                position: absolute !important;
                top: -13px !important;
                left: 50% !important;
                transform: translateX(-50%) !important;
                background: linear-gradient(135deg, #6366f1 0%, #9333ea 100%) !important;
                color: #ffffff !important;
                font-size: 11.5px !important;
                font-weight: 900 !important;
                padding: 4px 14px !important;
                border-radius: 99px !important;
                box-shadow: 0 4px 12px rgba(147, 51, 234, 0.35) !important;
                white-space: nowrap !important;
            }

            .gk-card-icon-wrap {
                width: 52px !important;
                height: 52px !important;
                border-radius: 16px !important;
                background: #f8fafc !important;
                border: 1.5px solid #e2e8f0 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                font-size: 26px !important;
                margin-bottom: 14px !important;
            }
            .gk-plan-featured .gk-card-icon-wrap {
                background: #ede9fe !important;
                border-color: #ddd6fe !important;
            }

            .gk-pricing-card-title {
                font-size: 20px !important;
                font-weight: 900 !important;
                color: #0f172a !important;
                margin: 0 0 6px 0 !important;
            }
            .gk-plan-desc {
                font-size: 12.5px !important;
                color: #64748b !important;
                margin: 0 0 16px 0 !important;
                min-height: 36px !important;
                line-height: 1.5 !important;
            }

            /* قیمت */
            .gk-price-block {
                background: #f8fafc !important;
                border: 1.5px solid #f1f5f9 !important;
                border-radius: 18px !important;
                padding: 14px !important;
                text-align: center !important;
                margin-bottom: 18px !important;
            }
            .gk-plan-featured .gk-price-block {
                background: #f5f3ff !important;
                border-color: #ede9fe !important;
            }
                        .gk-pricing-price-num {
                font-family: 'IRANSansXFaNum', 'IRANSansX', 'IRANSans', 'Vazirmatn', -apple-system, sans-serif !important;
                font-size: 28px !important;
                font-weight: 900 !important;
                color: #0f172a !important;
                line-height: 1.1 !important;
                letter-spacing: -0.5px !important;
            }
            .gk-price-currency {
                font-size: 13px !important;
                color: #64748b !important;
                font-weight: 800 !important;
                margin-right: 4px !important;
            }
            .gk-price-period {
                display: block !important;
                font-size: 11.5px !important;
                color: #94a3b8 !important;
                margin-top: 4px !important;
                font-weight: 700 !important;
            }
            .gk-price-discount-tag {
                display: inline-block !important;
                background: #fef2f2 !important;
                color: #e11d48 !important;
                border: 1px solid #fecdd3 !important;
                font-size: 11px !important;
                font-weight: 900 !important;
                padding: 2px 8px !important;
                border-radius: 8px !important;
                margin-bottom: 6px !important;
            }

            /* لیست ویژگی‌ها */
            .gk-features-list {
                list-style: none !important;
                padding: 0 !important;
                margin: 0 0 22px 0 !important;
                display: flex !important;
                flex-direction: column !important;
                gap: 10px !important;
                flex: 1 !important;
            }
            .gk-features-list li {
                font-size: 13px !important;
                color: #334155 !important;
                display: flex !important;
                align-items: center !important;
                gap: 8px !important;
                line-height: 1.4 !important;
            }
            .gk-feat-check {
                width: 18px !important;
                height: 18px !important;
                border-radius: 50% !important;
                background: #dcfce7 !important;
                color: #16a34a !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                font-size: 11px !important;
                font-weight: 900 !important;
                flex-shrink: 0 !important;
            }

            /* دکمه خرید */
            .gk-btn-plan-buy {
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                gap: 6px !important;
                width: 100% !important;
                padding: 13px 0 !important;
                border-radius: 16px !important;
                font-size: 14px !important;
                font-weight: 900 !important;
                text-decoration: none !important;
                cursor: pointer !important;
                transition: all 0.22s ease !important;
                box-sizing: border-box !important;
                background: #f1f5f9 !important;
                color: #1e293b !important;
                border: 1.5px solid #cbd5e1 !important;
            }
            .gk-btn-plan-buy:hover {
                background: #0284c7 !important;
                color: #ffffff !important;
                border-color: #0284c7 !important;
                transform: translateY(-2px) !important;
                box-shadow: 0 6px 18px rgba(2, 132, 199, 0.25) !important;
            }
            .gk-plan-featured .gk-btn-plan-buy {
                background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%) !important;
                color: #ffffff !important;
                border: none !important;
                box-shadow: 0 6px 20px rgba(99, 102, 241, 0.35) !important;
            }
            .gk-plan-featured .gk-btn-plan-buy:hover {
                background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%) !important;
                transform: translateY(-2px) !important;
                box-shadow: 0 10px 25px rgba(99, 102, 241, 0.45) !important;
            }

            /* بخش ارزش‌ها */
            .gk-values-row {
                display: grid !important;
                grid-template-columns: repeat(3, 1fr) !important;
                gap: 16px !important;
                margin: 40px 0 30px 0 !important;
            }
            .gk-value-box {
                background: #ffffff !important;
                border: 1.5px solid #e2e8f0 !important;
                border-radius: 20px !important;
                padding: 18px 16px !important;
                display: flex !important;
                align-items: center !important;
                gap: 14px !important;
            }
            .gk-value-icon {
                font-size: 28px !important;
                width: 50px !important;
                height: 50px !important;
                border-radius: 14px !important;
                background: #f8fafc !important;
                border: 1.5px solid #e2e8f0 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                flex-shrink: 0 !important;
            }
            .gk-value-text h4 {
                margin: 0 0 4px 0 !important;
                font-size: 14px !important;
                font-weight: 900 !important;
                color: #0f172a !important;
            }
            .gk-value-text p {
                margin: 0 !important;
                font-size: 12px !important;
                color: #64748b !important;
                line-height: 1.4 !important;
            }

            /* بخش سوالات متداول */
            .gk-faq-section {
                background: #ffffff !important;
                border: 2px solid #e2e8f0 !important;
                border-radius: 24px !important;
                padding: 26px 22px !important;
                margin-top: 30px !important;
            }
            .gk-faq-heading {
                text-align: center !important;
                margin-bottom: 20px !important;
            }
            .gk-faq-item {
                border-bottom: 1px solid #f1f5f9 !important;
                padding: 14px 0 !important;
            }
            .gk-faq-item:last-child {
                border-bottom: none !important;
            }
            .gk-faq-question {
                font-size: 14px !important;
                font-weight: 800 !important;
                color: #1e293b !important;
                cursor: pointer !important;
                display: flex !important;
                justify-content: space-between !important;
                align-items: center !important;
            }
            .gk-faq-answer {
                font-size: 13px !important;
                color: #64748b !important;
                line-height: 1.7 !important;
                margin-top: 8px !important;
                display: none;
            }

            /* ریسپانسیو و موبایل */
            @media (max-width: 1024px) {
                .gk-pricing-grid {
                    grid-template-columns: repeat(2, 1fr) !important;
                }
                .gk-school-grid {
                    grid-template-columns: repeat(2, 1fr) !important;
                }
                .gk-plan-featured {
                    transform: none !important;
                }
                .gk-plan-featured:hover {
                    transform: translateY(-4px) !important;
                }
            }

            @media (max-width: 768px) {
                .gk-pricing-grid, .gk-school-grid, .gk-values-row {
                    grid-template-columns: 1fr !important;
                    gap: 16px !important;
                }
                .gk-pricing-hero-title {
                    font-size: 24px !important;
                }
                .gk-switch-tab-btn {
                    padding: 8px 14px !important;
                    font-size: 12.5px !important;
                }
                .gk-plan-card {
                    padding: 20px 16px !important;
                    border-radius: 20px !important;
                }
            }
        </style>

        <div class="gk-pricing-page-wrapper">
            
            <!-- هدر و تیتر صفحه -->
            <div class="gk-pricing-header">
                <div class="gk-pricing-badge-pill">
                    ✨ دسترسی نامحدود به بازی‌های استعدادیابی و هوش کودک
                </div>
                <h1 class="gk-pricing-hero-title">پلن‌های اشتراک و دسترسی ویژه قربانی کیدز</h1>
                <p class="gk-pricing-hero-subtitle">
                    اشتراک ویژه برای دسترسی نامحدود کودکان به تمامی بازی‌های قفل‌دار VIP، لیگ‌های رقابتی و پنل‌های سازمانی مهدکودک‌ها و مدارس
                </p>
            </div>

            <!-- سوییچر تب هوشمند بین خانواده و مدارس -->
            <div class="gk-pricing-switcher-wrap">
                <div class="gk-pricing-switcher">
                    <button type="button" class="gk-switch-tab-btn active" id="gk-tab-btn-parents" onclick="gkSwitchPricingTab('parents');">
                        👨‍👩‍👧‍👦 اشتراک والدین و کودکان (شخصی)
                    </button>
                    <button type="button" class="gk-switch-tab-btn" id="gk-tab-btn-schools" onclick="gkSwitchPricingTab('schools');">
                        🏫 اشتراک مهدکودک‌ها و مدارس (سازمانی)
                    </button>
                </div>
            </div>

            <!-- ۱. بخش اشتراک‌های والدین و کودکان (B2C) -->
            <div id="gk-pricing-parents-section">
                <div class="gk-pricing-grid">
                    
                    <!-- پلن ۱ ماهه -->
                    <div class="gk-plan-card">
                        <div>
                            <div class="gk-card-icon-wrap">🌱</div>
                            <h3 class="gk-pricing-card-title">اشتراک ۱ ماهه</h3>
                            <p class="gk-plan-desc">مناسب برای شروع، ارزیابی اولیه و آشنایی با بازی‌ها</p>
                            
                            <div class="gk-price-block">
                                <span class="gk-pricing-price-num">۹۹,۰۰۰</span>
                                <span class="gk-price-currency">تومان</span>
                                <span class="gk-price-period">دسترسی ۳۰ روزه</span>
                            </div>

                            <ul class="gk-features-list">
                                <li><span class="gk-feat-check">✓</span> دسترسی به تمام بازی‌های قفل‌دار VIP</li>
                                <li><span class="gk-feat-check">✓</span> شرکت در تابلوی امتیازات و رکوردها</li>
                                <li><span class="gk-feat-check">✓</span> ۱۰۰٪ بدون تبلیغات مزاحم</li>
                                <li><span class="gk-feat-check">✓</span> پشتیبانی آنلاین و راهنما</li>
                            </ul>
                        </div>

                        <a href="<?php echo esc_url($url_1m); ?>" class="gk-btn-plan-buy">
                            انتخاب و شروع بازی 🚀
                        </a>
                    </div>

                    <!-- پلن ۳ ماهه -->
                    <div class="gk-plan-card">
                        <div>
                            <div class="gk-card-icon-wrap">⭐</div>
                            <h3 class="gk-pricing-card-title">اشتراک ۳ ماهه</h3>
                            <p class="gk-plan-desc">اشتراک فصلی محبوب برای تثبیت تمرکز و تقویت مهارت‌ها</p>
                            
                            <div class="gk-price-block">
                                <span class="gk-price-discount-tag">۱۵٪ صرفه‌جویی</span><br>
                                <span class="gk-pricing-price-num">۲۴۹,۰۰۰</span>
                                <span class="gk-price-currency">تومان</span>
                                <span class="gk-price-period">دسترسی ۹۰ روزه (ماهی ۸۳ هزار)</span>
                            </div>

                            <ul class="gk-features-list">
                                <li><span class="gk-feat-check">✓</span> تمام امکانات پلن ۱ ماهه</li>
                                <li><span class="gk-feat-check">✓</span> دسترسی به آزمون‌های استعدادیابی جدید</li>
                                <li><span class="gk-feat-check">✓</span> ثبت مداوم روند رشد و رکوردها</li>
                                <li><span class="gk-feat-check">✓</span> ۱۵٪ تخفیف نسبت به اشتراک ماهانه</li>
                            </ul>
                        </div>

                        <a href="<?php echo esc_url($url_3m); ?>" class="gk-btn-plan-buy">
                            خرید اشتراک ۳ ماهه ✨
                        </a>
                    </div>

                    <!-- پلن ۶ ماهه -->
                    <div class="gk-plan-card">
                        <div>
                            <div class="gk-card-icon-wrap">🚀</div>
                            <h3 class="gk-pricing-card-title">اشتراک ۶ ماهه</h3>
                            <p class="gk-plan-desc">پلن نیم‌ساله اقتصادی با دسترسی کامل به آپدیت‌ها</p>
                            
                            <div class="gk-price-block">
                                <span class="gk-price-discount-tag">۲۵٪ صرفه‌جویی</span><br>
                                <span class="gk-pricing-price-num">۴۴۹,۰۰۰</span>
                                <span class="gk-price-currency">تومان</span>
                                <span class="gk-price-period">دسترسی ۱۸۰ روزه (ماهی ۷۴ هزار)</span>
                            </div>

                            <ul class="gk-features-list">
                                <li><span class="gk-feat-check">✓</span> تمام امکانات پلن‌های قبلی</li>
                                <li><span class="gk-feat-check">✓</span> دسترسی زودهنگام به بازی‌های جدید</li>
                                <li><span class="gk-feat-check">✓</span> ۲۵٪ صرفه‌جویی اقتصادی ویژه</li>
                                <li><span class="gk-feat-check">✓</span> اولویت در مسابقات و جوایز دوره‌ای</li>
                            </ul>
                        </div>

                        <a href="<?php echo esc_url($url_6m); ?>" class="gk-btn-plan-buy">
                            خرید اشتراک ۶ ماهه 🎯
                        </a>
                    </div>

                    <!-- پلن ۱ ساله طلایی -->
                    <div class="gk-plan-card gk-plan-featured">
                        <span class="gk-featured-badge">🌟 پیشنهاد ویژه (بیشترین تخفیف)</span>
                        <div>
                            <div class="gk-card-icon-wrap">👑</div>
                            <h3 class="gk-pricing-card-title">اشتراک ۱ ساله طلایی</h3>
                            <p class="gk-plan-desc">کامل‌ترین و اقتصادی‌ترین پلن برای همراهی تمام‌سال کودک</p>
                            
                            <div class="gk-price-block">
                                <span class="gk-price-discount-tag" style="background:#ede9fe; color:#6366f1; border-color:#c7d2fe;">۴۲٪ تخفیف سالانه</span><br>
                                <span class="gk-pricing-price-num" style="color:#4f46e5;">۶۹۰,۰۰۰</span>
                                <span class="gk-price-currency">تومان</span>
                                <span class="gk-price-period">دسترسی ۳۶۵ روزه (ماهی فقط ۵۷ هزار)</span>
                            </div>

                            <ul class="gk-features-list">
                                <li><span class="gk-feat-check" style="background:#ede9fe; color:#6366f1;">✓</span> دسترسی کامل ۳۶۵ روزه به بیش از ۴۰ بازی</li>
                                <li><span class="gk-feat-check" style="background:#ede9fe; color:#6366f1;">✓</span> بازگشایی تمامی آزمون‌ها و لیگ‌های فصلی</li>
                                <li><span class="gk-feat-check" style="background:#ede9fe; color:#6366f1;">✓</span> ۴۲٪ تخفیف شگفت‌انگیز سالانه</li>
                                <li><span class="gk-feat-check" style="background:#ede9fe; color:#6366f1;">✓</span> دریافت کارنامه و تحلیل رشد هوش کودک</li>
                                <li><span class="gk-feat-check" style="background:#ede9fe; color:#6366f1;">✓</span> پشتیبانی اختصاصی و نامحدود VIP</li>
                            </ul>
                        </div>

                        <a href="<?php echo esc_url($url_1y); ?>" class="gk-btn-plan-buy">
                            عضویت طلایی ۱ ساله 👑
                        </a>
                    </div>

                </div>
            </div>

            <!-- ۲. بخش اشتراک‌های مهدکودک‌ها و مدارس (B2B) -->
            <div id="gk-pricing-schools-section" style="display:none;">
                <div class="gk-pricing-grid gk-school-grid">
                    
                    <!-- پلن پایه مدارس -->
                    <div class="gk-plan-card">
                        <div>
                            <div class="gk-card-icon-wrap">🥉</div>
                            <h3 class="gk-pricing-card-title">پلن پایه (تا ۳۰ نوآموز)</h3>
                            <p class="gk-plan-desc">مناسب مهدکودک‌ها و مراکز آموزشی نوپا و تک‌کلاسه</p>
                            
                            <div class="gk-price-block">
                                <span class="gk-pricing-price-num">۱,۸۰۰,۰۰۰</span>
                                <span class="gk-price-currency">تومان</span>
                                <span class="gk-price-period">اشتراک ۱ ساله سازمانی</span>
                            </div>

                            <ul class="gk-features-list">
                                <li><span class="gk-feat-check">✓</span> ظرفیت تا ۳۰ دانش‌آموز / نوآموز</li>
                                <li><span class="gk-feat-check">✓</span> پنل اختصاصی مدیر مهدکودک</li>
                                <li><span class="gk-feat-check">✓</span> پنل مستقل مربیان کلاس‌ها</li>
                                <li><span class="gk-feat-check">✓</span> درج لوگو و برند مهد روی کارنامه‌ها</li>
                                <li><span class="gk-feat-check">✓</span> بازگشایی بازی‌های VIP برای نوآموزان</li>
                            </ul>
                        </div>

                        <a href="<?php echo esc_url($url_starter); ?>" class="gk-btn-plan-buy">
                            انتخاب پلن پایه 🚀
                        </a>
                    </div>

                    <!-- پلن استاندارد مدارس (ویژه) -->
                    <div class="gk-plan-card gk-plan-featured" style="border-color:#0284c7; background:linear-gradient(180deg,#f0f9ff 0%,#ffffff 100%);">
                        <span class="gk-featured-badge" style="background:linear-gradient(135deg,#0284c7,#0369a1);">🏆 انتخاب اول مهدکودک‌ها</span>
                        <div>
                            <div class="gk-card-icon-wrap" style="background:#e0f2fe; border-color:#bae6fd;">🥈</div>
                            <h3 class="gk-pricing-card-title">پلن استاندارد (تا ۸۰ نوآموز)</h3>
                            <p class="gk-plan-desc">پرفروش‌ترین پلن برای مهدها و پیش‌دبستانی‌های با چند کلاس</p>
                            
                            <div class="gk-price-block" style="background:#f0f9ff; border-color:#e0f2fe;">
                                <span class="gk-price-discount-tag" style="background:#e0f2fe; color:#0369a1; border-color:#bae6fd;">پیشنهاد ویژه مدارس</span><br>
                                <span class="gk-pricing-price-num" style="color:#0284c7;">۳,۵۰۰,۰۰۰</span>
                                <span class="gk-price-currency">تومان</span>
                                <span class="gk-price-period">اشتراک ۱ ساله سازمانی</span>
                            </div>

                            <ul class="gk-features-list">
                                <li><span class="gk-feat-check" style="background:#e0f2fe; color:#0284c7;">✓</span> ظرفیت تا ۸۰ دانش‌آموز در چندین کلاس</li>
                                <li><span class="gk-feat-check" style="background:#e0f2fe; color:#0284c7;">✓</span> تعریف نامحدود کلاس و معلمین</li>
                                <li><span class="gk-feat-check" style="background:#e0f2fe; color:#0284c7;">✓</span> چاپ کارت‌های QR هوشمند برای کودکان</li>
                                <li><span class="gk-feat-check" style="background:#e0f2fe; color:#0284c7;">✓</span> ارسال گروهی لینک به پیام‌رسان بله مادران</li>
                                <li><span class="gk-feat-check" style="background:#e0f2fe; color:#0284c7;">✓</span> موتور برگزاری لیگ‌ها و مسابقات کلاسی</li>
                            </ul>
                        </div>

                        <a href="<?php echo esc_url($url_standard); ?>" class="gk-btn-plan-buy" style="background:linear-gradient(135deg,#0284c7,#0369a1); color:#fff; border:none; box-shadow:0 6px 20px rgba(2,132,199,0.35);">
                            فعال‌سازی پلن استاندارد 🌟
                        </a>
                    </div>

                    <!-- پلن طلایی سازمانی -->
                    <div class="gk-plan-card">
                        <div>
                            <div class="gk-card-icon-wrap">🥇</div>
                            <h3 class="gk-pricing-card-title">پلن طلایی (تا ۲۰۰ نوآموز)</h3>
                            <p class="gk-plan-desc">مناسب مجتمع‌های آموزشی بزرگ، دبستان‌ها و مراکز چندشعبه</p>
                            
                            <div class="gk-price-block">
                                <span class="gk-pricing-price-num">۶,۹۰۰,۰۰۰</span>
                                <span class="gk-price-currency">تومان</span>
                                <span class="gk-price-period">اشتراک ۱ ساله سازمانی</span>
                            </div>

                            <ul class="gk-features-list">
                                <li><span class="gk-feat-check">✓</span> ظرفیت تا ۲۰۰ نوآموز بدون محدودیت</li>
                                <li><span class="gk-feat-check">✓</span> تمام امکانات سازمانی و پنل‌های مربیان</li>
                                <li><span class="gk-feat-check">✓</span> گزارش‌های تحلیلی عملکرد هوش و تمرکز</li>
                                <li><span class="gk-feat-check">✓</span> پشتیبانی تلفنی و اختصاصی مدیران</li>
                                <li><span class="gk-feat-check">✓</span> امکان ارتقا و افزودن ظرفیت اختصاصی</li>
                            </ul>
                        </div>

                        <a href="<?php echo esc_url($url_enterprise); ?>" class="gk-btn-plan-buy">
                            خرید پلن سازمانی طلایی 👑
                        </a>
                    </div>

                </div>
            </div>

            <!-- ۳. بخش ارزش‌ها و تعهدات -->
            <div class="gk-values-row">
                <div class="gk-value-box">
                    <div class="gk-value-icon">🔒</div>
                    <div class="gk-value-text">
                        <h4>محیط ۱۰۰٪ امن برای کودک</h4>
                        <p>بدون هیچ‌گونه تبلیغات مزاحم، پاپ‌آپ یا محتوای نامناسب برای سنین ۳ تا ۹ سال</p>
                    </div>
                </div>
                <div class="gk-value-box">
                    <div class="gk-value-icon">🧠</div>
                    <div class="gk-value-text">
                        <h4>تقویت مهارت‌های شناختی</h4>
                        <p>بازی‌های استاندارد بین‌المللی تقویت حافظه، هوش فضایی، حل مسئله و تمرکز</p>
                    </div>
                </div>
                <div class="gk-value-box">
                    <div class="gk-value-icon">📱</div>
                    <div class="gk-value-text">
                        <h4>اجرا روی تمام دستگاه‌ها</h4>
                        <p>بدون نیاز به نصب، قابل استفاده روی گوشی موبایل، تبلت، لپ‌تاپ و تلویزیون هوشمند</p>
                    </div>
                </div>
            </div>

                        <!-- بخش پشتیبانی مستقیم تلفنی و بله -->
            <div style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 2px solid #86efac; border-radius: 20px; padding: 18px 22px; margin-top: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <span style="font-size: 32px;">📞</span>
                    <div>
                        <h4 style="margin: 0 0 4px 0; font-size: 15px; font-weight: 900; color: #166534;">نیاز به راهنمایی یا مشاوره خرید دارید؟</h4>
                        <span style="font-size: 12.5px; color: #15803d; font-weight: 700;">کارشناسان ما در پیام‌رسان بله و تماس تلفنی پاسخگوی شما هستند:</span>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <a href="<?php echo esc_url('tel:' . GK_Utils::get_phone()); ?>" style="background: #ffffff; color: #16a34a; border: 1.5px solid #86efac; padding: 9px 18px; border-radius: 14px; font-size: 13.5px; font-weight: 900; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 8px rgba(22, 163, 74, 0.1);">
                        📞 <?php echo esc_html(GK_Utils::get_phone_display()); ?>
                    </a>
                    <a href="<?php echo esc_url(GK_Utils::get_ble_url()); ?>" target="_blank" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #ffffff; padding: 9px 18px; border-radius: 14px; font-size: 13.5px; font-weight: 900; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);">
                        🟢 گفتگو در بله
                    </a>
                </div>
            </div>

            <!-- ۴. سوالات متداول (FAQ) -->
            <div class="gk-faq-section">
                <div class="gk-faq-heading">
                    <h3 class="gk-section-heading" style="font-size:22px; margin:0 0 6px 0; color:#0f172a;">❓ سوالات متداول والدین و مدیران</h3>
                    <span style="font-size:12.5px; color:#64748b;">پاسخ به سوالات پرتکرار درباره نحوه استفاده و فعال‌سازی اشتراک</span>
                </div>

                <div class="gk-faq-item">
                    <div class="gk-faq-question" onclick="gkToggleFaq(this);">
                        <span>۱. بعد از پرداخت چه اتفاقی می‌افتد و چگونه به بازی‌ها دسترسی پیدا می‌کنم؟</span>
                        <span>▾</span>
                    </div>
                    <div class="gk-faq-answer">
                        بلافاصله پس از تکمیل فرآیند پرداخت، حساب کاربری شما فعال شده و تمامی بازی‌های قفل‌دار VIP به صورت خودکار برای شما بازگشایی می‌شوند.
                    </div>
                </div>

                <div class="gk-faq-item">
                    <div class="gk-faq-question" onclick="gkToggleFaq(this);">
                        <span>۲. در پلن مهدکودک‌ها آیا نیاز است والدین کودکان ثبت‌نام کنند؟</span>
                        <span>▾</span>
                    </div>
                    <div class="gk-faq-answer">
                        خیر! مدیر مهد یا مربی در پنل اختصاصی نام کودکان را وارد کرده و برای هر کودک یک لینک اختصاصی و کارت QR اختصاصی تولید می‌شود که والدین بدون نیاز به ثبت‌نام می‌توانند مستقیماً وارد بازی‌ها شوند.
                    </div>
                </div>

                <div class="gk-faq-item">
                    <div class="gk-faq-question" onclick="gkToggleFaq(this);">
                        <span>۳. آیا امکان استفاده از یک حساب روی چند گوشی و تبلت وجود دارد؟</span>
                        <span>▾</span>
                    </div>
                    <div class="gk-faq-answer">
                        بله، شما می‌توانید با نام کاربری و رمز عبور خود در هر دستگاهی (موبایل، تبلت، کامپیوتر) لاگین کرده و از بازی‌ها لذت ببرید.
                    </div>
                </div>
            </div>

        </div>

        <script>
        function gkSwitchPricingTab(tab) {
            if (tab === 'parents') {
                document.getElementById('gk-pricing-parents-section').style.display = 'block';
                document.getElementById('gk-pricing-schools-section').style.display = 'none';
                document.getElementById('gk-tab-btn-parents').classList.add('active');
                document.getElementById('gk-tab-btn-schools').classList.remove('active');
            } else {
                document.getElementById('gk-pricing-parents-section').style.display = 'none';
                document.getElementById('gk-pricing-schools-section').style.display = 'block';
                document.getElementById('gk-tab-btn-parents').classList.remove('active');
                document.getElementById('gk-tab-btn-schools').classList.add('active');
            }
        }

        function gkToggleFaq(element) {
            var answer = element.nextElementSibling;
            if (answer.style.display === 'block') {
                answer.style.display = 'none';
                element.querySelector('span:last-child').textContent = '▾';
            } else {
                answer.style.display = 'block';
                element.querySelector('span:last-child').textContent = '▴';
            }
        }
        </script>
        <?php
        return ob_get_clean();
    }
}