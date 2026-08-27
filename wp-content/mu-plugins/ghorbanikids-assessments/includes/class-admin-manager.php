<?php
/**
 * Class GK_Admin_Manager
 * WordPress Admin Dashboard Management for Assessments, Submissions & Free/VIP Access Control
 */
if (!defined('ABSPATH')) exit;

class GK_Admin_Manager {

    public static function init() {
        add_action('admin_menu', [__CLASS__, 'register_admin_menu']);
        add_action('admin_init', [__CLASS__, 'handle_admin_actions']);
    }

    public static function register_admin_menu() {
        add_menu_page(
            'نتایج آزمون‌های هوش و استعدادیابی',
            '🧠 آزمون‌های هوش',
            'manage_options',
            'gk-assessments-admin',
            [__CLASS__, 'render_admin_dashboard'],
            'dashicons-chart-area',
            26
        );

        add_submenu_page(
            'gk-assessments-admin',
            'همه کارنامه‌ها و سوابق',
            '📋 کارنامه‌های ثبت‌شده',
            'manage_options',
            'gk-assessments-admin',
            [__CLASS__, 'render_admin_dashboard']
        );

        add_submenu_page(
            'gk-assessments-admin',
            'تنظیمات دسترسی و اشتراک ویژه آزمون‌ها',
            '⚙️ دسترسی (رایگان / ویژه)',
            'manage_options',
            'gk-assessments-access',
            [__CLASS__, 'render_access_settings']
        );
    }

    public static function handle_admin_actions() {
        if (!current_user_can('manage_options')) return;

        // Delete action
        if (isset($_GET['action']) && $_GET['action'] === 'delete_result' && isset($_GET['id'])) {
            $id = intval($_GET['id']);
            check_admin_referer('gk_delete_result_' . $id);

            global $wpdb;
            $table = $wpdb->prefix . 'gk_assessment_results';
            $wpdb->delete($table, ['id' => $id], ['%d']);

            wp_safe_redirect(admin_url('admin.php?page=gk-assessments-admin&deleted=1'));
            exit;
        }

        // Save access rules
        if (isset($_POST['gk_save_access_rules'])) {
            check_admin_referer('gk_access_rules_nonce');

            $posted_rules = isset($_POST['gk_rules']) && is_array($_POST['gk_rules']) ? $_POST['gk_rules'] : [];
            $sanitized = [];
            foreach ($posted_rules as $slug => $access) {
                $sanitized[sanitize_key($slug)] = ($access === 'premium') ? 'premium' : 'free';
            }

            update_option('gk_assessment_access_rules', $sanitized);
            
            if (function_exists('litespeed_purge_all')) litespeed_purge_all();
            if (function_exists('wp_cache_flush')) wp_cache_flush();

            wp_safe_redirect(admin_url('admin.php?page=gk-assessments-access&saved=1'));
            exit;
        }
    }

    public static function render_access_settings() {
        $tests = GK_Assessment_CPT::get_all_tests();
        $rules = get_option('gk_assessment_access_rules', []);
        ?>
        <div class="wrap" style="direction: rtl; font-family: Tahoma, sans-serif; max-width: 960px;">
            <h1 style="font-size: 24px; font-weight: bold; margin-bottom: 10px; color: #1e293b;">
                ⚙️ مدیریت دسترسی آزمون‌ها (رایگان / ویژه VIP)
            </h1>
            <p style="color: #64748b; margin-bottom: 24px; font-size: 14px;">
                در این بخش می‌توانید مشخص کنید کدام آزمون‌ها برای تمام کاربران <strong>رایگان</strong> باشند و کدام آزمون‌ها نیازمند <strong>اشتراک ویژه VIP</strong> باشند.
            </p>

            <?php if (isset($_GET['saved'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p>تنظیمات دسترسی با موفقیت ذخیره و در سایت اعمال شد! ✨</p>
                </div>
            <?php endif; ?>

            <form method="post" action="">
                <?php wp_nonce_field('gk_access_rules_nonce'); ?>
                
                <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 14px rgba(0,0,0,0.04); margin-bottom: 24px;">
                    <table class="wp-list-table widefat fixed striped" style="border: none;">
                        <thead>
                            <tr>
                                <th style="width: 70px; padding: 14px 16px;">آیکون</th>
                                <th style="padding: 14px 16px;">عنوان آزمون</th>
                                <th style="width: 140px; padding: 14px 16px;">تعداد سوالات</th>
                                <th style="width: 220px; padding: 14px 16px;">نوع دسترسی</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tests as $slug => $t): 
                                $current_access = $rules[$slug] ?? 'free';
                            ?>
                                <tr>
                                    <td style="font-size: 28px; text-align: center; vertical-align: middle;">
                                        <?php echo esc_html($t['icon']); ?>
                                    </td>
                                    <td style="vertical-align: middle;">
                                        <strong style="font-size: 15px; color: #1e293b;"><?php echo esc_html($t['title']); ?></strong>
                                        <div style="color: #64748b; font-size: 12px; margin-top: 4px;"><?php echo esc_html($t['subtitle']); ?></div>
                                    </td>
                                    <td style="vertical-align: middle; font-weight: bold; color: #475569;">
                                        ❓ <?php echo count($t['questions']); ?> سوال
                                    </td>
                                    <td style="vertical-align: middle;">
                                        <select name="gk_rules[<?php echo esc_attr($slug); ?>]" style="font-weight: bold; padding: 6px 14px; border-radius: 10px; border: 2px solid <?php echo $current_access === 'premium' ? '#f59e0b' : '#10b981'; ?>; background: <?php echo $current_access === 'premium' ? '#fffbeb' : '#ecfdf5'; ?>;">
                                            <option value="free" <?php selected($current_access, 'free'); ?>>🎁 رایگان (همه کاربران)</option>
                                            <option value="premium" <?php selected($current_access, 'premium'); ?>>👑 نیازمند اشتراک ویژه VIP</option>
                                        </select>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <p>
                    <input type="submit" name="gk_save_access_rules" class="button button-primary button-hero" value="💾 ذخیره و اعمال تغییرات دسترسی" style="font-weight: bold; border-radius: 12px; padding: 6px 28px;">
                </p>
            </form>
        </div>
        <?php
    }

    public static function render_admin_dashboard() {
        global $wpdb;
        $table = $wpdb->prefix . 'gk_assessment_results';
        $tests = GK_Assessment_CPT::get_all_tests();

        // Stats
        $total_tests = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        $unique_children = $wpdb->get_var("SELECT COUNT(DISTINCT child_name) FROM $table");
        $today_tests = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE created_at >= CURDATE()");
        $top_test_slug = $wpdb->get_var("SELECT assessment_slug FROM $table GROUP BY assessment_slug ORDER BY COUNT(*) DESC LIMIT 1");
        $top_test_name = $tests[$top_test_slug]['title'] ?? '---';

        // Filter / Search
        $filter_slug = isset($_GET['test_slug']) ? sanitize_text_field($_GET['test_slug']) : '';
        $search_term = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

        $where = ["1=1"];
        $params = [];

        if ($filter_slug) {
            $where[] = "assessment_slug = %s";
            $params[] = $filter_slug;
        }

        if ($search_term) {
            $where[] = "(child_name LIKE %s OR user_id IN (SELECT ID FROM {$wpdb->users} WHERE user_login LIKE %s OR user_email LIKE %s))";
            $params[] = '%' . $wpdb->esc_like($search_term) . '%';
            $params[] = '%' . $wpdb->esc_like($search_term) . '%';
            $params[] = '%' . $wpdb->esc_like($search_term) . '%';
        }

        $where_sql = implode(' AND ', $where);
        $query = "SELECT * FROM $table WHERE $where_sql ORDER BY id DESC LIMIT 100";
        
        if (!empty($params)) {
            $results = $wpdb->get_results($wpdb->prepare($query, $params));
        } else {
            $results = $wpdb->get_results($query);
        }

        $assessments_page_url = home_url('/tests/');
        ?>
        <div class="wrap" style="direction: rtl; font-family: Tahoma, sans-serif;">
            <h1 style="font-size: 24px; font-weight: bold; margin-bottom: 20px; color: #1e293b;">
                🧠 مدیریت و گزارش‌های آزمون‌های رشد و استعدادیابی کودکان
            </h1>

            <?php if (isset($_GET['deleted'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p>کارنامه مورد نظر با موفقیت از دیتابیس حذف گردید.</p>
                </div>
            <?php endif; ?>

            <style>
                .gk-admin-stats-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                    gap: 18px;
                    margin-bottom: 30px;
                }
                .gk-admin-stat-card {
                    background: #fff;
                    border: 2px solid #e2e8f0;
                    border-radius: 16px;
                    padding: 20px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.03);
                    display: flex;
                    align-items: center;
                    gap: 16px;
                }
                .gk-stat-icon {
                    font-size: 32px;
                    background: #f1f5f9;
                    width: 60px;
                    height: 60px;
                    border-radius: 14px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .gk-stat-num {
                    font-size: 26px;
                    font-weight: 900;
                    color: #6c5ce7;
                    line-height: 1;
                    margin-bottom: 4px;
                }
                .gk-stat-label {
                    font-size: 13px;
                    color: #64748b;
                    font-weight: 700;
                }
                .gk-admin-filter-bar {
                    background: #fff;
                    border: 1px solid #e2e8f0;
                    border-radius: 14px;
                    padding: 14px 20px;
                    margin-bottom: 24px;
                    display: flex;
                    align-items: center;
                    gap: 14px;
                    flex-wrap: wrap;
                }
                .gk-admin-table {
                    background: #fff;
                    border-radius: 16px;
                    border: 1px solid #e2e8f0;
                    overflow: hidden;
                    box-shadow: 0 4px 14px rgba(0,0,0,0.04);
                }
                .gk-admin-table table {
                    border: none;
                }
                .gk-admin-table th {
                    background: #f8fafc;
                    font-weight: 800;
                    color: #334155;
                    padding: 14px 16px;
                    border-bottom: 2px solid #e2e8f0;
                }
                .gk-admin-table td {
                    padding: 14px 16px;
                    vertical-align: middle;
                    border-bottom: 1px solid #f1f5f9;
                }
                .gk-badge-score {
                    background: #ede9fe;
                    color: #5641e5;
                    padding: 4px 12px;
                    border-radius: 12px;
                    font-weight: 800;
                    font-size: 13px;
                    display: inline-block;
                }
                .gk-btn-admin-view {
                    background: #6c5ce7;
                    color: #fff !important;
                    padding: 6px 14px;
                    border-radius: 8px;
                    text-decoration: none !important;
                    font-weight: bold;
                    font-size: 12px;
                    display: inline-block;
                }
                .gk-btn-admin-del {
                    color: #e11d48 !important;
                    text-decoration: none !important;
                    font-weight: bold;
                    font-size: 12px;
                    margin-right: 10px;
                }
            </style>

            <div class="gk-admin-stats-grid">
                <div class="gk-admin-stat-card">
                    <div class="gk-stat-icon">📊</div>
                    <div>
                        <div class="gk-stat-num"><?php echo number_format_i18n($total_tests); ?></div>
                        <div class="gk-stat-label">کل آزمون‌های انجام‌شده</div>
                    </div>
                </div>
                <div class="gk-admin-stat-card">
                    <div class="gk-stat-icon">👶</div>
                    <div>
                        <div class="gk-stat-num"><?php echo number_format_i18n($unique_children); ?></div>
                        <div class="gk-stat-label">کودکان ارزیابی‌شده</div>
                    </div>
                </div>
                <div class="gk-admin-stat-card">
                    <div class="gk-stat-icon">📅</div>
                    <div>
                        <div class="gk-stat-num"><?php echo number_format_i18n($today_tests); ?></div>
                        <div class="gk-stat-label">آزمون‌های امروز</div>
                    </div>
                </div>
                <div class="gk-admin-stat-card">
                    <div class="gk-stat-icon">🏆</div>
                    <div>
                        <div class="gk-stat-num" style="font-size: 18px;"><?php echo esc_html($top_test_name); ?></div>
                        <div class="gk-stat-label">محبوب‌ترین آزمون</div>
                    </div>
                </div>
            </div>

            <div class="gk-admin-filter-bar">
                <form method="get" action="" style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap; width: 100%;">
                    <input type="hidden" name="page" value="gk-assessments-admin">
                    
                    <label style="font-weight: bold;">فیلتر بر اساس آزمون:</label>
                    <select name="test_slug" style="border-radius: 8px; padding: 4px 12px;">
                        <option value="">-- همه آزمون‌ها --</option>
                        <?php foreach ($tests as $s => $t): ?>
                            <option value="<?php echo esc_attr($s); ?>" <?php selected($filter_slug, $s); ?>>
                                <?php echo esc_html($t['icon'] . ' ' . $t['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label style="font-weight: bold;">جستجوی نام کودک یا والد:</label>
                    <input type="text" name="s" value="<?php echo esc_attr($search_term); ?>" placeholder="مثلاً: آرتین یا ایمیل" style="border-radius: 8px; padding: 4px 12px;">

                    <input type="submit" class="button button-primary" value="اعمال فیلتر 🔍">

                    <?php if ($filter_slug || $search_term): ?>
                        <a href="<?php echo admin_url('admin.php?page=gk-assessments-admin'); ?>" class="button">پاک کردن فیلتر</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="gk-admin-table">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 60px;">#</th>
                            <th style="width: 140px;">تاریخ ثبت</th>
                            <th style="width: 180px;">نام و سن کودک</th>
                            <th>والد / حساب کاربری</th>
                            <th>نوع آزمون</th>
                            <th>استعداد برتر (نمره ۱)</th>
                            <th style="width: 170px;">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($results)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 30px; color: #94a3b8;">
                                    هیچ کارنامه‌ای یافت نشد.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($results as $row): 
                                $scores = json_decode($row->scores_data, true);
                                $test_info = $tests[$row->assessment_slug] ?? null;
                                $test_name = $test_info['title'] ?? $row->assessment_slug;
                                $test_icon = $test_info['icon'] ?? '📋';

                                $top_cat_name = '---';
                                $top_pct = 0;
                                if (is_array($scores)) {
                                    foreach ($scores as $s) {
                                        if (isset($s['percentage']) && $s['percentage'] > $top_pct) {
                                            $top_pct = $s['percentage'];
                                            $top_cat_name = $s['name'] ?? '';
                                        }
                                    }
                                }

                                $user = get_userdata($row->user_id);
                                $user_display = $user ? $user->display_name . ' (' . $user->user_email . ')' : 'کاربر مهمان';
                                $report_url = add_query_arg(['report_id' => $row->id], $assessments_page_url);
                                $delete_url = wp_nonce_url(admin_url('admin.php?page=gk-assessments-admin&action=delete_result&id=' . $row->id), 'gk_delete_result_' . $row->id);
                            ?>
                                <tr>
                                    <td><strong>#<?php echo $row->id; ?></strong></td>
                                    <td><?php echo date_i18n('j F Y - H:i', strtotime($row->created_at)); ?></td>
                                    <td>
                                        <strong>👶 <?php echo esc_html($row->child_name); ?></strong>
                                        <span style="color: #64748b;">(<?php echo esc_html($row->child_age); ?> ساله)</span>
                                    </td>
                                    <td><?php echo esc_html($user_display); ?></td>
                                    <td>
                                        <span><?php echo $test_icon; ?></span>
                                        <strong><?php echo esc_html($test_name); ?></strong>
                                    </td>
                                    <td>
                                        <span class="gk-badge-score">
                                            🌟 <?php echo esc_html($top_cat_name); ?>: <strong><?php echo $top_pct; ?>٪</strong>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?php echo esc_url($report_url); ?>" class="gk-btn-admin-view" target="_blank">
                                            📊 مشاهده کارنامه
                                        </a>
                                        <a href="<?php echo esc_url($delete_url); ?>" class="gk-btn-admin-del" onclick="return confirm('آیا از حذف این کارنامه اطمینان دارید؟');">
                                            حذف
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
        <?php
    }
}