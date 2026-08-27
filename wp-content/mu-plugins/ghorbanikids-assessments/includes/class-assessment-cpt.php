<?php
/**
 * Class GK_Assessment_CPT
 * Handles shortcodes, test catalog and frontend routing with Free/VIP access control
 */
if (!defined('ABSPATH')) exit;

class GK_Assessment_CPT {

    public static function init() {
        add_shortcode('gk_assessments_list', [__CLASS__, 'render_assessments_catalog']);
        add_shortcode('gk_assessment', [__CLASS__, 'render_single_assessment']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets'], 999);
    }

    public static function is_user_vip($user_id = null) {
        if ($user_id === null) $user_id = get_current_user_id();
        if (!$user_id) return false;
        
        if (user_can($user_id, 'manage_options') || user_can($user_id, 'edit_posts')) {
            return true;
        }

        if (class_exists('GhorbaniKids_Games')) {
            $sub = GhorbaniKids_Games::user_has_active_subscription($user_id);
            return !empty($sub['active']);
        }

        $exp = get_user_meta($user_id, '_gk_subscription_expires_at', true);
        return ($exp && time() < intval($exp));
    }

    public static function get_test_access($slug) {
        $rules = get_option('gk_assessment_access_rules', []);
        return $rules[$slug] ?? 'free';
    }

    public static function get_all_tests() {
        $tests = [];
        $dir = WPMU_PLUGIN_DIR . '/ghorbanikids-assessments/tests-data/';
        $files = glob($dir . 'test-*.php');
        if (!empty($files)) {
            foreach ($files as $f) {
                $data = include $f;
                if (is_array($data) && !empty($data['slug'])) {
                    $data['access'] = self::get_test_access($data['slug']);
                    $tests[$data['slug']] = $data;
                }
            }
        }
        return $tests;
    }

    public static function get_test_by_slug($slug) {
        $tests = self::get_all_tests();
        return $tests[$slug] ?? null;
    }

        public static function enqueue_assets() {
        if (!is_page('tests') && !is_singular('gk_assessment')) {
            global $post;
            if (!$post || (strpos($post->post_content, 'gk_assessment') === false)) {
                return;
            }
        }
        $css_url = content_url('mu-plugins/ghorbanikids-assessments/assets/css/assessment.css');
        $js_url  = content_url('mu-plugins/ghorbanikids-assessments/assets/js/assessment-engine.js');
        $v = '2.0.0';

        wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js', [], '4.4.1', true);
        wp_enqueue_style('gk-assessment-css', $css_url, [], $v);
        wp_enqueue_script('gk-assessment-js', $js_url, ['jquery', 'chartjs'], $v, true);

        wp_localize_script('gk-assessment-js', 'gkAssessmentData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('gk_assessment_nonce')
        ]);
    }

    public static function render_assessments_catalog() {
        $tests = self::get_all_tests();
        $is_vip = self::is_user_vip();
        ob_start();
        ?>
        <style>
            .gk-catalog-hero {
                text-align: center;
                margin: 10px auto 35px auto;
                max-width: 820px;
                direction: rtl;
            }
            .gk-hero-tag {
                display: inline-block;
                background: #f3f0ff;
                color: #6c5ce7;
                font-size: 13.5px;
                font-weight: 800;
                padding: 6px 18px;
                border-radius: 20px;
                border: 1.5px solid #e5dbff;
                margin-bottom: 12px;
            }
            .gk-catalog-hero h1 {
                font-size: 28px !important;
                font-weight: 900 !important;
                color: #1e293b !important;
                margin-bottom: 12px !important;
                line-height: 1.5 !important;
            }
            .gk-catalog-hero p {
                font-size: 15px !important;
                color: #64748b !important;
                line-height: 1.8 !important;
                margin: 0 !important;
            }
            .gk-assessments-grid {
                display: grid !important;
                grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)) !important;
                gap: 28px !important;
                margin: 25px 0 45px 0 !important;
                direction: rtl !important;
            }
            .gk-assessment-card {
                background: #ffffff !important;
                border: 2.5px solid #eef2f7 !important;
                border-radius: 26px !important;
                padding: 30px 24px !important;
                box-shadow: 0 10px 30px rgba(108, 92, 231, 0.07) !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
                text-align: right !important;
                transition: transform 0.3s, border-color 0.3s, box-shadow 0.3s !important;
                position: relative !important;
            }
            .gk-assessment-card:hover {
                transform: translateY(-6px) !important;
                border-color: #6c5ce7 !important;
                box-shadow: 0 18px 40px rgba(108, 92, 231, 0.18) !important;
            }
            .gk-card-icon-wrapper {
                display: flex !important;
                justify-content: space-between !important;
                align-items: center !important;
                margin-bottom: 18px !important;
            }
            .gk-card-icon {
                font-size: 2.8rem !important;
                background: #f3f0ff !important;
                width: 68px !important;
                height: 68px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                border-radius: 20px !important;
                border: 2px solid #e5dbff !important;
            }
            .gk-card-badges-row {
                display: flex !important;
                gap: 8px !important;
                align-items: center !important;
            }
            .gk-card-age {
                background: #fff0f6 !important;
                color: #d6336c !important;
                font-size: 13px !important;
                font-weight: 900 !important;
                padding: 6px 12px !important;
                border-radius: 30px !important;
                border: 1.5px solid #fcc2d7 !important;
            }
            .gk-badge-access-free {
                background: #e6fcf5 !important;
                color: #0ca678 !important;
                font-size: 12.5px !important;
                font-weight: 900 !important;
                padding: 6px 12px !important;
                border-radius: 30px !important;
                border: 1.5px solid #b2f2bb !important;
            }
            .gk-badge-access-vip {
                background: #fff9db !important;
                color: #e67700 !important;
                font-size: 12.5px !important;
                font-weight: 900 !important;
                padding: 6px 12px !important;
                border-radius: 30px !important;
                border: 1.5px solid #ffe066 !important;
            }
            .gk-card-title {
                font-size: 18.5px !important;
                font-weight: 900 !important;
                color: #1e293b !important;
                margin: 0 0 10px 0 !important;
                line-height: 1.55 !important;
            }
            .gk-card-subtitle {
                font-size: 14px !important;
                color: #475569 !important;
                line-height: 1.75 !important;
                margin: 0 0 18px 0 !important;
                flex-grow: 1 !important;
            }
            .gk-card-meta {
                display: flex !important;
                justify-content: space-between !important;
                font-size: 13px !important;
                font-weight: 800 !important;
                color: #475569 !important;
                background: #f8fafc !important;
                border: 1px solid #e2e8f0 !important;
                padding: 10px 14px !important;
                border-radius: 14px !important;
                margin-bottom: 18px !important;
            }
            .gk-btn-start-test {
                display: block !important;
                text-align: center !important;
                background: linear-gradient(135deg, #ff7675 0%, #e84393 50%, #6c5ce7 100%) !important;
                color: #fff !important;
                font-weight: 900 !important;
                font-size: 15.5px !important;
                padding: 13px 20px !important;
                border-radius: 16px !important;
                text-decoration: none !important;
                box-shadow: 0 6px 18px rgba(232, 67, 147, 0.35) !important;
                transition: transform 0.2s, box-shadow 0.2s !important;
            }
            .gk-btn-start-test:hover {
                transform: scale(1.03) !important;
                color: #fff !important;
                box-shadow: 0 10px 24px rgba(232, 67, 147, 0.5) !important;
            }
            .gk-btn-vip-locked {
                background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
                box-shadow: 0 6px 18px rgba(245, 158, 11, 0.35) !important;
            }
        </style>

        <div class="gk-catalog-hero">
            <span class="gk-hero-tag">🌟 سنجش هوش و استعدادیابی کودکان</span>
            <h1>آزمون‌های استاندارد رشد و روان‌شناختی کودک</h1>
            <p>با انجام هر یک از آزمون‌های زیر، کارنامه تحلیلی هوشمند با نمودار اختصاصی و بازی‌های مهارتی تقویتی دریافت کنید.</p>
        </div>

        <div class="gk-assessments-grid">
            <?php foreach ($tests as $slug => $test): 
                $is_premium = ($test['access'] === 'premium');
                $user_can_access = (!$is_premium || $is_vip);
            ?>
                <div class="gk-assessment-card">
                    <div>
                        <div class="gk-card-icon-wrapper">
                            <span class="gk-card-icon"><?php echo esc_html($test['icon']); ?></span>
                            <div class="gk-card-badges-row">
                                <?php if ($is_premium): ?>
                                    <span class="gk-badge-access-vip">👑 ویژه VIP</span>
                                <?php else: ?>
                                    <span class="gk-badge-access-free">🎁 رایگان</span>
                                <?php endif; ?>
                                <span class="gk-card-age">🎯 <?php echo esc_html($test['target_age']); ?></span>
                            </div>
                        </div>
                        <h3 class="gk-card-title"><?php echo esc_html($test['title']); ?></h3>
                        <p class="gk-card-subtitle"><?php echo esc_html($test['subtitle']); ?></p>
                    </div>
                    <div>
                        <div class="gk-card-meta">
                            <span>⏱️ زمان: <?php echo esc_html($test['estimated_time']); ?></span>
                            <span>❓ <?php echo count($test['questions']); ?> سوال</span>
                        </div>
                        <div class="gk-card-action">
                            <?php if ($user_can_access): ?>
                                <a href="<?php echo esc_url(add_query_arg('test', $slug, home_url('/tests/'))); ?>" class="gk-btn-start-test">
                                    <?php echo $is_premium ? 'شروع آزمون VIP 🚀' : 'شروع رایگان آزمون 🚀'; ?>
                                </a>
                            <?php else: ?>
                                <a href="<?php echo esc_url(add_query_arg('test', $slug, home_url('/tests/'))); ?>" class="gk-btn-start-test gk-btn-vip-locked">
                                    🔒 شروع با اشتراک ویژه 👑
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function render_single_assessment($atts) {
        if (isset($_GET['report_id'])) {
            $report_id = intval($_GET['report_id']);
            global $wpdb;
            $table = $wpdb->prefix . 'gk_assessment_results';
            $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $report_id));

            if ($row) {
                $scores_data = json_decode($row->scores_data, true);
                $test = self::get_test_by_slug($row->assessment_slug);
                $test_title = $test ? $test['title'] : 'آزمون رشد شناختی';
                $recommended = json_decode($row->recommendations, true) ?: [];

                $sorted_summary = $scores_data;
                uasort($sorted_summary, function($a, $b) {
                    return $b['percentage'] <=> $a['percentage'];
                });

                $chart_labels = [];
                $chart_data = [];
                $chart_colors = [];
                if (is_array($scores_data)) {
                    foreach ($scores_data as $cat_key => $cat_val) {
                        $chart_labels[] = $cat_val['name'];
                        $chart_data[]   = $cat_val['percentage'];
                        $chart_colors[] = $cat_val['color'];
                    }
                }

                $report_html = GK_Report_Renderer::render_report_card([
                    'result_id'         => $row->id,
                    'slug'              => $row->assessment_slug,
                    'test_title'        => $test_title,
                    'child_name'        => $row->child_name,
                    'child_age'         => $row->child_age,
                    'summary'           => $scores_data,
                    'sorted_summary'    => $sorted_summary,
                    'recommended_games' => $recommended,
                    'chart_labels'      => $chart_labels,
                    'chart_data'        => $chart_data,
                    'chart_colors'      => $chart_colors
                ]);

                ob_start();
                ?>
                <div style="margin-bottom: 20px; direction: rtl; text-align: right;">
                    <a href="<?php echo esc_url(home_url('/tests/')); ?>" style="display: inline-flex; align-items: center; gap: 8px; background: #f1f5f9; color: #475569; padding: 10px 18px; border-radius: 12px; font-weight: bold; text-decoration: none; font-size: 14px;">
                        ⬅️ بازگشت به فهرست همه آزمون‌ها
                    </a>
                </div>
                <?php
                echo $report_html;
                ?>
                <script>
                document.addEventListener("DOMContentLoaded", function() {
                    var canvas = document.getElementById('gk-radar-chart');
                    if (canvas && typeof Chart !== 'undefined') {
                        var ctx = canvas.getContext('2d');
                        var labels = <?php echo wp_json_encode($chart_labels); ?>;
                        var dataValues = <?php echo wp_json_encode($chart_data); ?>;
                        var chartType = labels.length > 3 ? 'radar' : 'bar';

                        new Chart(ctx, {
                            type: chartType,
                            data: {
                                labels: labels,
                                datasets: [{
                                    label: 'درصد توانمندی (٪)',
                                    data: dataValues,
                                    backgroundColor: chartType === 'radar' ? 'rgba(108, 92, 231, 0.25)' : 'rgba(108, 92, 231, 0.8)',
                                    borderColor: '#6c5ce7',
                                    borderWidth: 2.5,
                                    pointBackgroundColor: '#8526ff',
                                    pointBorderColor: '#fff',
                                    pointRadius: 5,
                                    pointHoverRadius: 7
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: true,
                                scales: chartType === 'radar' ? {
                                    r: {
                                        angleLines: { color: '#e2e8f0' },
                                        grid: { color: '#e2e8f0' },
                                        suggestedMin: 0,
                                        suggestedMax: 100,
                                        ticks: { stepSize: 25, font: { family: 'Tahoma', size: 11 }, backdropColor: 'transparent' },
                                        pointLabels: { font: { family: 'Tahoma', size: 13, weight: 'bold' }, color: '#1e293b' }
                                    }
                                } : {
                                    y: { beginAtZero: true, max: 100, ticks: { font: { family: 'Tahoma' } } },
                                    x: { ticks: { font: { family: 'Tahoma', weight: 'bold' } } }
                                },
                                plugins: {
                                    legend: { display: false }
                                }
                            }
                        });
                    }
                });
                </script>
                <?php
                return ob_get_clean();
            }
        }

        $atts = shortcode_atts([
            'slug' => isset($_GET['test']) ? sanitize_text_field($_GET['test']) : ''
        ], $atts);

        if (empty($atts['slug'])) {
            return self::render_assessments_catalog();
        }

        $test = self::get_test_by_slug($atts['slug']);
        if (!$test) {
            return '<div class="gk-alert-box gk-alert-warning">آزمون مورد نظر یافت نشد. <a href="' . esc_url(home_url('/tests/')) . '">مشاهده همه آزمون‌ها</a></div>';
        }

        // VIP Access Gate
        $is_premium = ($test['access'] === 'premium');
        $is_vip = self::is_user_vip();

        if ($is_premium && !$is_vip) {
            $pricing_url = home_url('/pricing/');
            $catalog_url = home_url('/tests/');
            ob_start();
            ?>
            <style>
                .gk-paywall-box {
                    max-width: 780px;
                    margin: 20px auto 40px auto;
                    background: #ffffff;
                    border: 3px solid #fef3c7;
                    border-radius: 32px;
                    padding: 45px 35px;
                    text-align: center;
                    box-shadow: 0 20px 50px rgba(245, 158, 11, 0.12);
                    direction: rtl;
                }
                .gk-paywall-icon {
                    font-size: 70px;
                    margin-bottom: 16px;
                    animation: gkPulse 2s infinite ease-in-out;
                }
                @keyframes gkPulse {
                    0%, 100% { transform: scale(1); }
                    50% { transform: scale(1.08); }
                }
                .gk-paywall-title {
                    font-size: 26px !important;
                    font-weight: 900 !important;
                    color: #1e293b !important;
                    margin-bottom: 14px !important;
                }
                .gk-paywall-desc {
                    font-size: 16px !important;
                    color: #64748b !important;
                    line-height: 1.8 !important;
                    max-width: 620px;
                    margin: 0 auto 28px auto !important;
                }
                .gk-paywall-features {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                    gap: 16px;
                    margin-bottom: 35px;
                    text-align: right;
                }
                .gk-paywall-feat-item {
                    background: #fffbeb;
                    border: 1.5px solid #fde68a;
                    border-radius: 16px;
                    padding: 16px;
                    font-size: 14px;
                    font-weight: 800;
                    color: #92400e;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }
                .gk-btn-paywall-buy {
                    display: inline-block;
                    background: linear-gradient(135deg, #f59e0b 0%, #d97706 50%, #b45309 100%);
                    color: #fff !important;
                    font-size: 18px;
                    font-weight: 900;
                    padding: 16px 36px;
                    border-radius: 20px;
                    text-decoration: none !important;
                    box-shadow: 0 10px 25px rgba(245, 158, 11, 0.4);
                    transition: transform 0.2s;
                }
                .gk-btn-paywall-buy:hover {
                    transform: scale(1.04);
                    color: #fff !important;
                }
                .gk-btn-paywall-back {
                    display: inline-block;
                    margin-top: 18px;
                    color: #64748b;
                    font-size: 14px;
                    font-weight: bold;
                    text-decoration: none;
                }
            </style>

            <div class="gk-paywall-box">
                <div class="gk-paywall-icon">👑🔒</div>
                <h2 class="gk-paywall-title">این آزمون نیازمند اشتراک ویژه VIP است</h2>
                <p class="gk-paywall-desc">
                    آزمون تخصصی <strong>«<?php echo esc_html($test['title']); ?>»</strong> شامل تحلیل عمیق روان‌شناختی و صدور کارنامه پیشرفته است. برای شروع این آزمون و دسترسی به تمام بازی‌ها و ارزیابی‌ها، اشتراک ویژه خود را فعال کنید.
                </p>

                <div class="gk-paywall-features">
                    <div class="gk-paywall-feat-item">
                        <span>✨</span> دسترسی به تمام آزمون‌های تخصصی
                    </div>
                    <div class="gk-paywall-feat-item">
                        <span>🎮</span> باز شدن تمام مراحل بازی‌های قفل‌دار
                    </div>
                    <div class="gk-paywall-feat-item">
                        <span>📊</span> نمودارهای عنکبوتی و ذخیره کارنامه‌ها
                    </div>
                    <div class="gk-paywall-feat-item">
                        <span>👩‍⚕️</span> بازی‌های تجویزی بر اساس استعداد
                    </div>
                </div>

                <div>
                    <a href="<?php echo esc_url($pricing_url); ?>" class="gk-btn-paywall-buy">
                        👑 مشاهده پلن‌ها و فعال‌سازی اشتراک ویژه 🚀
                    </a>
                </div>
                <div>
                    <a href="<?php echo esc_url($catalog_url); ?>" class="gk-btn-paywall-back">
                        ⬅️ مشاهده آزمون‌های رایگان
                    </a>
                </div>
            </div>
            <?php
            return ob_get_clean();
        }

        ob_start();
        ?>
        <div style="max-width: 900px; margin: 0 auto 16px auto; direction: rtl; text-align: right;">
            <a href="<?php echo esc_url(home_url('/tests/')); ?>" style="display: inline-flex; align-items: center; gap: 8px; background: #f8fafc; border: 1.5px solid #e2e8f0; color: #475569; padding: 10px 20px; border-radius: 14px; font-weight: bold; text-decoration: none; font-size: 14px;">
                ⬅️ بازگشت به فهرست همه آزمون‌ها
            </a>
        </div>

        <div class="gk-quiz-container" id="gk-quiz-app" data-slug="<?php echo esc_attr($test['slug']); ?>">
            
            <div class="gk-quiz-header">
                <div class="gk-header-badge"><?php echo esc_html($test['icon'] . ' ' . $test['target_age']); ?></div>
                <h1 class="gk-quiz-title"><?php echo esc_html($test['title']); ?></h1>
                <p class="gk-quiz-desc"><?php echo esc_html($test['description']); ?></p>
                
                <div class="gk-child-info-bar">
                    <div class="gk-input-group">
                        <label>نام یا نام مستعار کودک:</label>
                        <input type="text" id="gk-child-name" placeholder="مثلاً: آرتین یا ریحانه" required>
                    </div>
                    <div class="gk-input-group">
                        <label>سن کودک:</label>
                        <select id="gk-child-age">
                            <option value="4">۴ ساله</option>
                            <option value="5">۵ ساله</option>
                            <option value="6">۶ ساله</option>
                            <option value="7">۷ ساله</option>
                            <option value="8">۸ ساله</option>
                            <option value="9">۹ ساله</option>
                            <option value="10">۱۰ ساله</option>
                            <option value="11">۱۱ ساله</option>
                            <option value="12">۱۲ ساله</option>
                            <option value="13">۱۳ ساله</option>
                            <option value="14">۱۴ ساله</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="gk-progress-wrapper">
                <div class="gk-progress-info">
                    <span>پیشرفت آزمون: <strong id="gk-progress-text">۰٪</strong></span>
                    <span>سوال <strong id="gk-current-step">۱</strong> از <?php echo count($test['questions']); ?></span>
                </div>
                <div class="gk-progress-bar">
                    <div class="gk-progress-fill" id="gk-progress-fill"></div>
                </div>
            </div>

            <div class="gk-questions-deck" id="gk-questions-deck">
                <?php foreach ($test['questions'] as $idx => $q): ?>
                    <div class="gk-question-slide <?php echo $idx === 0 ? 'active' : ''; ?>" data-index="<?php echo $idx; ?>" data-qid="<?php echo $q['id']; ?>" data-cat="<?php echo esc_attr($q['cat']); ?>">
                        <div class="gk-question-badge">سوال <?php echo number_format_i18n($idx + 1); ?></div>
                        <h2 class="gk-question-text"><?php echo esc_html($q['text']); ?></h2>
                        <div class="gk-options-list">
                            <?php foreach ($test['options'] as $val => $label): ?>
                                <div class="gk-option-card" data-val="<?php echo esc_attr($val); ?>">
                                    <span class="gk-option-radio"></span>
                                    <span class="gk-option-label"><?php echo esc_html($label); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="gk-quiz-nav">
                <button type="button" class="gk-btn gk-btn-prev" id="gk-btn-prev" style="display: none;">⬅️ سوال قبلی</button>
                <button type="button" class="gk-btn gk-btn-next" id="gk-btn-next">سوال بعدی ➡️</button>
                <button type="button" class="gk-btn gk-btn-submit" id="gk-btn-submit" style="display: none;">📊 مشاهده کارنامه و تحلیل نهایی 🚀</button>
            </div>

            <div class="gk-loading-overlay" id="gk-loading-overlay" style="display: none;">
                <div class="gk-spinner"></div>
                <h3>در حال تحلیل هوشمند پاسخ‌ها و ساخت کارنامه... 🧠✨</h3>
                <p>لطفاً چند لحظه صبر کنید</p>
            </div>

            <div class="gk-report-container" id="gk-report-container" style="display: none;"></div>

        </div>
        <?php
        return ob_get_clean();
    }
}