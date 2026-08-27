<?php
/**
 * Class GK_School_Landing
 * Renders the B2B School & Kindergarten Sales / Pricing Landing Page
 */
if (!defined('ABSPATH')) exit;

class GK_School_Landing {

    public static function init() {
        add_shortcode('gk_schools_landing', [__CLASS__, 'render_landing_page']);
        add_action('init', [__CLASS__, 'create_schools_page']);
    }

    public static function create_schools_page() {
        $page = get_page_by_path('schools');
        if (!$page) {
            wp_insert_post([
                'post_title' => '🏢 سامانه هوشمند مهدکودک‌ها و مدارس',
                'post_name' => 'schools',
                'post_content' => '[gk_schools_landing]',
                'post_status' => 'publish',
                'post_type' => 'page'
            ]);
        }
    }

    public static function render_landing_page() {
        $p_starter_id    = 809; // school-starter-30
        $p_standard_id   = 810; // school-standard-80
        $p_enterprise_id = 811; // school-enterprise-200

        $starter_url    = add_query_arg('add-to-cart', $p_starter_id, wc_get_cart_url());
        $standard_url   = add_query_arg('add-to-cart', $p_standard_id, wc_get_cart_url());
        $enterprise_url = add_query_arg('add-to-cart', $p_enterprise_id, wc_get_cart_url());

        ob_start();
        ?>
        <style>
            /* ==========================================================
               استاندارد فونت یکپارچه و لوکس مدارس قربانی کیدز (Typography Standard)
               ========================================================== */
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

            .gk-school-wrap,
            .gk-school-wrap *,
            .gk-teacher-wrap,
            .gk-teacher-wrap *,
            .gk-league-wrap,
            .gk-league-wrap *,
            .gk-modal-backdrop,
            .gk-modal-backdrop *,
            .gk-modal-card,
            .gk-modal-card *,
            input, select, textarea, button {
                font-family: 'IRANSansXFaNum', 'IRANSansX', 'IRANSans', 'Vazirmatn', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
                -webkit-font-smoothing: antialiased;
                -moz-osx-font-smoothing: grayscale;
            }

            /* تیترها با فونت شاداب آوینی */
            .gk-school-title-text,
            .gk-school-brand-title,
            .gk-box-head h3,
            .gk-modal-card h3,
            .gk-teacher-header h1,
            .gk-league-header h1,
            .gk-hero-title {
                font-family: 'aviny', 'Aviny', 'IRANSansXFaNum', 'IRANSansX', sans-serif !important;
                letter-spacing: 0.5px !important;
            }

            .gk-b2b-hero {
                text-align: center;
                max-width: 900px;
                margin: 20px auto 45px auto;
                direction: rtl;
            }
            .gk-b2b-hero-badge {
                display: inline-block;
                background: #f1f5f9;
                color: #0984e3;
                font-size: 13.5px;
                font-weight: 900;
                padding: 6px 18px;
                border-radius: 20px;
                border: 1.5px solid #cbd5e1;
                margin-bottom: 14px;
            }
            .gk-b2b-hero h1 {
                font-size: 32px !important;
                font-weight: 900 !important;
                color: #0f172a !important;
                line-height: 1.4 !important;
                margin-bottom: 14px !important;
            }
            .gk-b2b-hero p {
                font-size: 16px !important;
                color: #475569 !important;
                line-height: 1.8 !important;
                max-width: 720px;
                margin: 0 auto !important;
            }
            .gk-b2b-features-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
                gap: 20px;
                max-width: 1100px;
                margin: 0 auto 50px auto;
                direction: rtl;
            }
            .gk-b2b-feat-card {
                background: #ffffff;
                border: 2px solid #eef2f7;
                border-radius: 22px;
                padding: 24px;
                text-align: right;
                box-shadow: 0 8px 24px rgba(0,0,0,0.03);
            }
            .gk-b2b-feat-icon {
                font-size: 38px;
                margin-bottom: 12px;
            }
            .gk-b2b-feat-card h3 {
                font-size: 17px !important;
                font-weight: 900 !important;
                color: #1e293b !important;
                margin: 0 0 8px 0 !important;
            }
            .gk-b2b-feat-card p {
                font-size: 13.5px !important;
                color: #64748b !important;
                line-height: 1.7 !important;
                margin: 0 !important;
            }
            .gk-b2b-pricing-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(290px, 1fr));
                gap: 26px;
                max-width: 1100px;
                margin: 0 auto 60px auto;
                direction: rtl;
                align-items: stretch;
            }
            .gk-b2b-price-card {
                background: #ffffff;
                border: 2.5px solid #e2e8f0;
                border-radius: 26px;
                padding: 34px 26px;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                text-align: right;
                box-shadow: 0 10px 30px rgba(0,0,0,0.04);
                position: relative;
                transition: transform 0.3s;
            }
            .gk-b2b-price-card:hover {
                transform: translateY(-6px);
            }
            .gk-b2b-card-popular {
                border-color: #6c5ce7 !important;
                box-shadow: 0 18px 45px rgba(108, 92, 231, 0.18) !important;
            }
            .gk-b2b-popular-tag {
                position: absolute;
                top: -14px;
                left: 50%;
                transform: translateX(-50%);
                background: linear-gradient(135deg, #ff7675, #e84393);
                color: #fff;
                font-size: 12px;
                font-weight: 900;
                padding: 4px 16px;
                border-radius: 20px;
            }
            .gk-b2b-plan-name {
                font-size: 20px !important;
                font-weight: 900 !important;
                color: #1e293b !important;
                margin: 0 0 6px 0 !important;
            }
            .gk-b2b-plan-desc {
                font-size: 13.5px !important;
                color: #64748b !important;
                margin-bottom: 20px !important;
            }
            .gk-b2b-plan-price {
                font-size: 28px !important;
                font-weight: 900 !important;
                color: #0f172a !important;
                margin-bottom: 24px !important;
                padding-bottom: 18px !important;
                border-bottom: 2px dashed #f1f5f9 !important;
            }
            .gk-b2b-plan-price small {
                font-size: 13px !important;
                color: #64748b !important;
                font-weight: bold !important;
            }
            .gk-b2b-checklist {
                list-style: none !important;
                padding: 0 !important;
                margin: 0 0 28px 0 !important;
            }
            .gk-b2b-checklist li {
                font-size: 13.5px !important;
                color: #334155 !important;
                font-weight: 700 !important;
                margin-bottom: 12px !important;
                display: flex !important;
                align-items: center !important;
                gap: 8px !important;
            }
            .gk-btn-b2b-buy {
                display: block !important;
                text-align: center !important;
                background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%) !important;
                color: #fff !important;
                font-weight: 900 !important;
                font-size: 15px !important;
                padding: 14px 20px !important;
                border-radius: 16px !important;
                text-decoration: none !important;
                box-shadow: 0 6px 18px rgba(15, 23, 42, 0.15) !important;
                transition: transform 0.2s !important;
            }
            .gk-btn-b2b-buy:hover {
                transform: scale(1.03) !important;
                color: #fff !important;
            }
            .gk-btn-b2b-pop {
                background: linear-gradient(135deg, #6c5ce7 0%, #5641e5 100%) !important;
                box-shadow: 0 8px 24px rgba(108, 92, 231, 0.35) !important;
            }
        </style>

        <!-- بخش هدر معرفی سازمانی -->
        <div class="gk-b2b-hero">
            <span class="gk-b2b-hero-badge">🏢 ویژه مهدکودک‌ها، مدارس و مراکز رشد کودک</span>
            <h1>سامانه هوشمند استعدادیابی و ارزیابی شناختی نوآموزان</h1>
            <p>
                ارائه کارنامه‌های تحلیلی با لوگوی اختصاصی مهدکودک شما، ایجاد تابلوی قهرمانان کلاسی برای بازی‌های فکری، و ارسال لینک بدون دردسر برای والدین در پیام‌رسان‌ها.
            </p>
        </div>

        <!-- ۴ مزیت کلیدی برای مدیران مهد -->
        <div class="gk-b2b-features-grid">
            <div class="gk-b2b-feat-card">
                <div class="gk-b2b-feat-icon">🏷️</div>
                <h3>کارنامه با برند و لوگوی مهد</h3>
                <p>درج نام، لوگو و مهر مهدکودک شما در سربرگ تمام کارنامه‌ها جهت تحویل شکیل به اولیا.</p>
            </div>
            <div class="gk-b2b-feat-card">
                <div class="gk-b2b-feat-icon">👩‍🏫</div>
                <h3>پنل مجزای معلمان و کلاس‌ها</h3>
                <p>تولید لینک دعوت یک‌کلیکه برای هر کلاس؛ معلم فقط کودکان کلاس خود را نظارت می‌کند.</p>
            </div>
            <div class="gk-b2b-feat-card">
                <div class="gk-b2b-feat-icon">📱</div>
                <h3>ارزیابی بدون نیاز به لاگین اولیا</h3>
                <p>ارسال لینک یا بارکد QR اختصاصی برای والدین در ایتا یا شاد بدون نیاز به ثبت‌نام پیچیده.</p>
            </div>
            <div class="gk-b2b-feat-card">
                <div class="gk-b2b-feat-icon">🏆</div>
                <h3>لیدربورد بازی‌های کلاسی</h3>
                <p>جدول رتبه‌بندی زنده بازی‌های تقویت حافظه و هوش کودکان برای نمایش در برد هوشمند کلاس.</p>
            </div>
        </div>

        <!-- جدول پلن‌های سازمانی -->
        <div class="gk-b2b-pricing-grid">
            
            <!-- پلن پایه ۳۰ نفره -->
            <div class="gk-b2b-price-card">
                <div>
                    <h3 class="gk-b2b-plan-name">پلن نوپا (پایه)</h3>
                    <p class="gk-b2b-plan-desc">مناسب مهدکودک‌های تک‌شعبه و نوپا</p>
                    <div class="gk-b2b-plan-price">
                        ۱,۸۰۰,۰۰۰ <small>تومان / سالانه</small>
                    </div>
                    <ul class="gk-b2b-checklist">
                        <li>✅ ظرفیت تا <strong>۳۰ نوآموز</strong></li>
                        <li>✅ ایجاد نامحدود کلاس و مربی</li>
                        <li>✅ کارنامه با لوگوی مهدکودک</li>
                        <li>✅ لینک و بارکد QR برای اولیا</li>
                        <li>✅ دسترسی به ۶ آزمون تخصصی</li>
                    </ul>
                </div>
                <div>
                    <a href="<?php echo esc_url($starter_url); ?>" class="gk-btn-b2b-buy">
                        خرید و فعال‌سازی آنی 🚀
                    </a>
                </div>
            </div>

            <!-- پلن استاندارد ۸۰ نفره -->
            <div class="gk-b2b-price-card gk-b2b-card-popular">
                <span class="gk-b2b-popular-tag">🌟 پرفروش‌ترین پلن مهدها</span>
                <div>
                    <h3 class="gk-b2b-plan-name">پلن مهد استاندارد</h3>
                    <p class="gk-b2b-plan-desc">مناسب مهدکودک‌ها و مراکز متوسط</p>
                    <div class="gk-b2b-plan-price">
                        ۳,۵۰۰,۰۰۰ <small>تومان / سالانه</small>
                    </div>
                    <ul class="gk-b2b-checklist">
                        <li>✅ ظرفیت تا <strong>۸۰ نوآموز</strong></li>
                        <li>✅ تمام امکانات پلن پایه</li>
                        <li>✅ تابلوی قهرمانان و لیگ کلاسی</li>
                        <li>✅ خروجی اکسل و گزارشات آماری مهد</li>
                        <li>✅ صدور کارنامه چاپی تکی و گروهی</li>
                    </ul>
                </div>
                <div>
                    <a href="<?php echo esc_url($standard_url); ?>" class="gk-btn-b2b-buy gk-btn-b2b-pop">
                        خرید و فعال‌سازی آنی 🚀
                    </a>
                </div>
            </div>

            <!-- پلن طلایی ۲۰۰ نفره -->
            <div class="gk-b2b-price-card">
                <div>
                    <h3 class="gk-b2b-plan-name">پلن مجتمع آموزشی</h3>
                    <p class="gk-b2b-plan-desc">مناسب مجتمع‌های بزرگ و مدارس</p>
                    <div class="gk-b2b-plan-price">
                        ۶,۹۰۰,۰۰۰ <small>تومان / سالانه</small>
                    </div>
                    <ul class="gk-b2b-checklist">
                        <li>✅ ظرفیت تا <strong>۲۰۰ نوآموز</strong></li>
                        <li>✅ تمام امکانات پلن استاندارد</li>
                        <li>✅ تحلیل مقایسه‌ای بین کلاس‌ها</li>
                        <li>✅ پشتیبانی اختصاصی سازمانی</li>
                        <li>✅ امکان ارتقای ظرفیت در آینده</li>
                    </ul>
                </div>
                <div>
                    <a href="<?php echo esc_url($enterprise_url); ?>" class="gk-btn-b2b-buy">
                        خرید و فعال‌سازی آنی 🚀
                    </a>
                </div>
            </div>

        </div>
        <?php
        return ob_get_clean();
    }
}