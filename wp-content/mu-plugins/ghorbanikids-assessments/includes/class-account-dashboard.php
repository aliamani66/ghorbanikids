<?php
/**
 * Class GK_Account_Dashboard
 * Integrates assessment history into WooCommerce My Account page
 */
if (!defined('ABSPATH')) exit;

class GK_Account_Dashboard {

    public static function init() {
        add_filter('woocommerce_get_query_vars', [__CLASS__, 'add_wc_query_vars'], 0);
        add_action('init', [__CLASS__, 'add_endpoint']);
        add_filter('query_vars', [__CLASS__, 'add_query_vars'], 0);
        
        add_filter('woocommerce_account_menu_items', [__CLASS__, 'add_menu_item']);
        add_action('woocommerce_account_my-assessments_endpoint', [__CLASS__, 'render_endpoint_content']);
        add_action('woocommerce_account_dashboard', [__CLASS__, 'render_dashboard_fallback'], 1);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_account_styles'], 999);
    }

    public static function add_endpoint() {
        add_rewrite_endpoint('my-assessments', EP_ROOT | EP_PAGES);
    }

    public static function add_wc_query_vars($vars) {
        $vars['my-assessments'] = 'my-assessments';
        return $vars;
    }

    public static function add_query_vars($vars) {
        $vars[] = 'my-assessments';
        return $vars;
    }

    public static function add_menu_item($items) {
        $new_items = [];
        foreach ($items as $key => $val) {
            $new_items[$key] = $val;
            if ($key === 'dashboard') {
                $new_items['my-assessments'] = '🧠 آزمون‌های من';
            }
        }
        return $new_items;
    }

    public static function render_dashboard_fallback() {
        if (isset($_GET['tab']) && $_GET['tab'] === 'assessments') {
            self::render_endpoint_content();
        }
    }

    public static function enqueue_account_styles() {
        if (is_account_page()) {
            $css_url = content_url('mu-plugins/ghorbanikids-assessments/assets/css/assessment.css');
            wp_enqueue_style('gk-assessment-css', $css_url, [], time());
        }
    }

    public static function render_endpoint_content() {
        $user_id = get_current_user_id();
        global $wpdb;
        $table = $wpdb->prefix . 'gk_assessment_results';

        $results = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM $table 
            WHERE user_id = %d 
            ORDER BY id DESC
        ", $user_id));

        $tests = GK_Assessment_CPT::get_all_tests();
        $assessments_page_url = home_url('/tests/');

        ?>
        <style>
            .woocommerce-MyAccount-content {
                text-align: right !important;
                direction: rtl !important;
            }
            .gk-dashboard-assessments-wrap {
                direction: rtl !important;
                text-align: right !important;
                margin-top: 10px;
            }
            .gk-dash-header {
                display: flex !important;
                justify-content: space-between !important;
                align-items: center !important;
                flex-wrap: wrap !important;
                gap: 16px !important;
                margin-bottom: 28px !important;
                padding-bottom: 20px !important;
                border-bottom: 2px dashed #e2e8f0 !important;
                text-align: right !important;
            }
            .gk-dash-header h2 {
                font-size: 1.6rem !important;
                font-weight: 900 !important;
                color: #1e293b !important;
                margin: 0 0 6px 0 !important;
            }
            .gk-dash-header p {
                color: #64748b !important;
                font-size: 1rem !important;
                margin: 0 !important;
            }
            .gk-btn-new-test {
                background: linear-gradient(135deg, #6c5ce7 0%, #5641e5 100%) !important;
                color: #fff !important;
                font-weight: 800 !important;
                font-size: 0.95rem !important;
                padding: 12px 24px !important;
                border-radius: 14px !important;
                text-decoration: none !important;
                box-shadow: 0 6px 18px rgba(108, 92, 231, 0.3) !important;
                display: inline-block !important;
                transition: transform 0.2s !important;
            }
            .gk-btn-new-test:hover {
                transform: scale(1.04) !important;
                color: #fff !important;
            }
            .gk-empty-assessments-box {
                text-align: center !important;
                background: #f8fafc !important;
                border: 2px dashed #cbd5e1 !important;
                border-radius: 24px !important;
                padding: 45px 20px !important;
            }
            .gk-empty-icon {
                font-size: 3.5rem !important;
                margin-bottom: 14px !important;
            }
            .gk-btn-start-primary {
                display: inline-block !important;
                background: linear-gradient(135deg, #6c5ce7 0%, #5641e5 100%) !important;
                color: #fff !important;
                font-weight: 800 !important;
                padding: 14px 32px !important;
                border-radius: 16px !important;
                margin-top: 20px !important;
                text-decoration: none !important;
                box-shadow: 0 6px 20px rgba(108, 92, 231, 0.35) !important;
            }
            .gk-history-cards-grid {
                display: grid !important;
                grid-template-columns: repeat(auto-fit, minmax(290px, 1fr)) !important;
                gap: 24px !important;
                margin-top: 20px !important;
            }
            .gk-history-card {
                background: #ffffff !important;
                border: 2.5px solid #eef2f7 !important;
                border-radius: 22px !important;
                padding: 24px !important;
                box-shadow: 0 8px 24px rgba(0, 0, 0, 0.05) !important;
                text-align: right !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
                transition: transform 0.2s, border-color 0.2s !important;
            }
            .gk-history-card:hover {
                transform: translateY(-5px) !important;
                border-color: #6c5ce7 !important;
            }
            .gk-hist-top {
                display: flex !important;
                justify-content: space-between !important;
                align-items: center !important;
                margin-bottom: 12px !important;
            }
            .gk-hist-icon {
                font-size: 2.2rem !important;
                background: #f3f0ff !important;
                width: 52px !important;
                height: 52px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                border-radius: 16px !important;
            }
            .gk-hist-date {
                font-size: 0.85rem !important;
                font-weight: 700 !important;
                color: #94a3b8 !important;
            }
            .gk-hist-title {
                font-size: 1.25rem !important;
                font-weight: 900 !important;
                color: #1e293b !important;
                margin: 0 0 10px 0 !important;
                line-height: 1.5 !important;
            }
            .gk-hist-child {
                font-size: 0.95rem !important;
                color: #475569 !important;
                margin-bottom: 12px !important;
            }
            .gk-hist-highlight {
                background: #f3f0ff !important;
                color: #5641e5 !important;
                font-size: 0.95rem !important;
                font-weight: 800 !important;
                padding: 10px 14px !important;
                border-radius: 12px !important;
                margin-bottom: 16px !important;
                border: 1px solid #e5dbff !important;
            }
            .gk-hist-actions {
                display: flex !important;
                flex-direction: column !important;
                gap: 10px !important;
                margin-top: 14px !important;
            }
            .gk-btn-hist-view {
                display: block !important;
                text-align: center !important;
                background: linear-gradient(135deg, #6c5ce7 0%, #5641e5 100%) !important;
                color: #fff !important;
                font-size: 0.95rem !important;
                font-weight: 800 !important;
                padding: 12px 16px !important;
                border-radius: 14px !important;
                text-decoration: none !important;
                box-shadow: 0 4px 14px rgba(108, 92, 231, 0.3) !important;
                transition: transform 0.2s !important;
            }
            .gk-btn-hist-view:hover {
                transform: scale(1.03) !important;
                color: #fff !important;
            }
            .gk-btn-hist-retry {
                display: block !important;
                text-align: center !important;
                background: #f1f5f9 !important;
                color: #475569 !important;
                font-size: 0.9rem !important;
                font-weight: 700 !important;
                padding: 8px 14px !important;
                border-radius: 12px !important;
                text-decoration: none !important;
                transition: background 0.2s !important;
            }
            .gk-btn-hist-retry:hover {
                background: #e2e8f0 !important;
            }
        </style>

        <div class="gk-dashboard-assessments-wrap">
            <div class="gk-dash-header">
                <div>
                    <h2>🧠 پرونده رشد شناختی و آزمون‌های فرزند شما</h2>
                    <p>در این بخش تاریخچه تمام آزمون‌های استعدادیابی و ارزیابی تمرکز کودک ذخیره شده و قابل بازبینی است.</p>
                </div>
                <a href="<?php echo esc_url($assessments_page_url); ?>" class="gk-btn-new-test">
                    + انجام آزمون جدید 🚀
                </a>
            </div>

            <?php if (empty($results)): ?>
                <div class="gk-empty-assessments-box">
                    <div class="gk-empty-icon">📊</div>
                    <h3>هنوز هیچ آزمونی برای فرزندتان ثبت نکرده‌اید!</h3>
                    <p>با انجام تست‌های استاندارد هوش و ارزیابی تمرکز، استعدادهای برجسته فرزندتان را کشف کنید و کارنامه تحلیلی اختصاصی دریافت نمایید.</p>
                    <a href="<?php echo esc_url($assessments_page_url); ?>" class="gk-btn-start-primary">
                        شروع اولین آزمون استعدادیابی 🎯
                    </a>
                </div>
            <?php else: ?>
                <div class="gk-history-cards-grid">
                    <?php foreach ($results as $row): 
                        $scores = json_decode($row->scores_data, true);
                        $test_info = $tests[$row->assessment_slug] ?? null;
                        $test_name = $test_info['title'] ?? $row->assessment_slug;
                        $test_icon = $test_info['icon'] ?? '📋';
                        
                        // Find top strength
                        $top_cat_name = '';
                        $top_pct = 0;
                        if (is_array($scores)) {
                            foreach ($scores as $s) {
                                if ($s['percentage'] > $top_pct) {
                                    $top_pct = $s['percentage'];
                                    $top_cat_name = $s['name'];
                                }
                            }
                        }
                    ?>
                        <div class="gk-history-card">
                            <div>
                                <div class="gk-hist-top">
                                    <span class="gk-hist-icon"><?php echo $test_icon; ?></span>
                                    <span class="gk-hist-date"><?php echo date_i18n('j F Y', strtotime($row->created_at)); ?></span>
                                </div>
                                <h3 class="gk-hist-title"><?php echo esc_html($test_name); ?></h3>
                                <div class="gk-hist-child">
                                    👦 فرزند: <strong><?php echo esc_html($row->child_name); ?></strong> (<?php echo esc_html($row->child_age); ?> ساله)
                                </div>
                                <?php if ($top_cat_name): ?>
                                    <div class="gk-hist-highlight">
                                        🌟 استعداد برتر: <strong><?php echo esc_html($top_cat_name); ?> (<?php echo $top_pct; ?>٪)</strong>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="gk-hist-actions">
                                <a href="<?php echo esc_url(add_query_arg(['report_id' => $row->id], $assessments_page_url)); ?>" class="gk-btn-hist-view">
                                    📊 مشاهده کامل کارنامه و نمودار
                                </a>
                                <a href="<?php echo esc_url(add_query_arg(['test' => $row->assessment_slug], $assessments_page_url)); ?>" class="gk-btn-hist-retry">
                                    تکرار آزمون 🔄
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}