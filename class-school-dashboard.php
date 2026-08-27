<?php
/**
 * Class GK_School_Dashboard
 * 100% Real-Time AJAX Single-Page Application: Zero Page Reloads across all actions (Add, Edit, Delete, Filter, Branding)
 */
if (!defined('ABSPATH')) exit;

class GK_School_Dashboard {

    public static function init() {
        add_shortcode('gk_school_dashboard', [__CLASS__, 'render_dashboard_shortcode']);
        add_action('init', [__CLASS__, 'add_account_endpoint']);
        add_filter('woocommerce_account_menu_items', [__CLASS__, 'add_menu_item']);
        add_action('woocommerce_account_school-panel_endpoint', [__CLASS__, 'render_account_tab']);
        add_action('wp_ajax_gk_school_save_branding', [__CLASS__, 'ajax_save_branding']);
        add_action('wp_ajax_gk_school_create_class', [__CLASS__, 'ajax_create_class']);
        add_action('wp_ajax_gk_school_edit_class', [__CLASS__, 'ajax_edit_class']);
        add_action('wp_ajax_gk_school_delete_class', [__CLASS__, 'ajax_delete_class']);
        add_action('wp_ajax_gk_school_add_student', [__CLASS__, 'ajax_add_student']);
        add_action('wp_ajax_gk_school_edit_student', [__CLASS__, 'ajax_edit_student']);
        add_action('wp_ajax_gk_school_delete_student', [__CLASS__, 'ajax_delete_student']);
    }

    public static function add_account_endpoint() {
        add_rewrite_endpoint('school-panel', EP_PAGES);
    }

    public static function add_menu_item($items) {
        $user_id = get_current_user_id();
        if (self::get_user_organization($user_id) || user_can($user_id, 'manage_options')) {
            $new_items = [];
            foreach ($items as $k => $v) {
                $new_items[$k] = $v;
                if ($k === 'dashboard') {
                    $new_items['school-panel'] = '🏢 پنل مدیریت مدارس و مهدها';
                }
            }
            return $new_items;
        }
        return $items;
    }

    public static function render_account_tab() {
        echo do_shortcode('[gk_school_dashboard]');
    }

    public static function get_user_organization($user_id = null) {
        if ($user_id === null) $user_id = get_current_user_id();
        if (!$user_id) return null;

        global $wpdb;
        $table_orgs = $wpdb->prefix . 'gk_organizations';
        
        $org = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_orgs WHERE manager_user_id = %d ORDER BY id DESC LIMIT 1", $user_id));

        if (!$org) {
            $org = $wpdb->get_row("SELECT * FROM $table_orgs WHERE status = 'active' ORDER BY id DESC LIMIT 1");
        }

        return $org;
    }

    public static function render_dashboard_shortcode() {
        if (!is_user_logged_in()) {
            return '<div class="gk-alert-box" style="text-align:center; padding:30px; background:#f8fafc; border-radius:18px; direction:rtl;">برای دسترسی به پنل مدیریت، لطفاً ابتدا <a href="' . esc_url(wc_get_page_permalink('myaccount')) . '" style="color:#6c5ce7; font-weight:bold;">وارد حساب کاربری خود</a> شوید.</div>';
        }

        $user_id = get_current_user_id();
        $org = self::get_user_organization($user_id);

        if (!$org) {
            return '<div class="gk-alert-box" style="text-align:center; padding:40px 20px; background:#fffbeb; border:2px solid #fde68a; border-radius:24px; direction:rtl;">
                <div style="font-size:48px; margin-bottom:12px;">🏢</div>
                <h2 style="margin:0 0 10px 0; color:#92400e;">هنوز اشتراک سازمانی فعال ندارید!</h2>
                <p style="color:#b45309; max-width:540px; margin:0 auto 24px auto;">با تهیه یکی از پلن‌های سازمانی، پنل مدیریت کلاس‌ها، صدور کارنامه با لوگوی مهدکودک/مدرسه و تابلوی امتیازات فعال خواهد شد.</p>
                <a href="' . esc_url(home_url('/pricing/')) . '" style="display:inline-block; background:linear-gradient(135deg,#f59e0b,#d97706); color:#fff; font-weight:900; padding:14px 30px; border-radius:16px; text-decoration:none;">مشاهده پلن‌های سازمانی مدارس و مهدها 🚀</a>
            </div>';
        }

        global $wpdb;
        $table_classes = $wpdb->prefix . 'gk_classes';
        $table_students = $wpdb->prefix . 'gk_students';

        $classes = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_classes WHERE org_id = %d ORDER BY id DESC", $org->id));
        $students = $wpdb->get_results($wpdb->prepare("SELECT s.*, c.name as class_name FROM $table_students s LEFT JOIN $table_classes c ON s.class_id = c.id WHERE s.org_id = %d ORDER BY s.id DESC", $org->id));

        $students_by_class = [];
        $class_counts = [];
        foreach ($students as $st) {
            $class_counts[$st->class_id] = ($class_counts[$st->class_id] ?? 0) + 1;
            $students_by_class[$st->class_id][] = $st;
        }

        $total_students = count($students);
        $limit = intval($org->student_limit);
        $remaining = max(0, $limit - $total_students);
        $pct_used = $limit > 0 ? min(100, round(($total_students / $limit) * 100)) : 0;
        $is_full = ($total_students >= $limit);

        $nonce = wp_create_nonce('gk_school_nonce');
        $ajax_url = admin_url('admin-ajax.php');

        ob_start();
        ?>
        <style>
            .gk-school-wrap,
            .gk-school-wrap *,
            .gk-modal-backdrop,
            .gk-modal-backdrop * {
                font-family: 'IRANSansXFaNum', 'IRANSansX', 'Vazirmatn', 'Vazir', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Tahoma, sans-serif !important;
            }
            .gk-school-wrap {
                direction: rtl !important;
                text-align: right !important;
                max-width: 1280px !important;
                margin: 20px auto 50px auto !important;
                padding: 0 15px !important;
                box-sizing: border-box !important;
                width: 100% !important;
                overflow-x: hidden !important;
            }
            .gk-school-header-card {
                background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #9333ea 100%);
                color: #ffffff;
                border-radius: 22px;
                padding: 18px 24px;
                margin-bottom: 22px;
                box-shadow: 0 10px 30px rgba(99, 102, 241, 0.22);
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
                gap: 16px;
                border: 1px solid rgba(255, 255, 255, 0.2);
                box-sizing: border-box;
            }
            .gk-school-brand-info {
                display: flex;
                align-items: center;
                gap: 16px;
            }
            .gk-school-logo-thumb {
                width: 54px;
                height: 54px;
                background: rgba(255, 255, 255, 0.18);
                backdrop-filter: blur(8px);
                -webkit-backdrop-filter: blur(8px);
                border-radius: 16px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 26px;
                overflow: hidden;
                border: 2px solid rgba(255, 255, 255, 0.35);
                flex-shrink: 0;
            }
            .gk-school-logo-thumb img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            .gk-school-title {
                font-size: 19px !important;
                font-weight: 900 !important;
                color: #ffffff !important;
                margin: 0 0 4px 0 !important;
                line-height: 1.3 !important;
            }
            .gk-school-subtitle {
                font-size: 12.5px;
                color: rgba(255, 255, 255, 0.88);
                margin: 0;
                display: flex;
                flex-wrap: wrap;
                gap: 12px;
                align-items: center;
                line-height: 1.5;
            }
            .gk-btn-settings-brand {
                background: rgba(255, 255, 255, 0.18);
                color: #ffffff !important;
                border: 1.5px solid rgba(255, 255, 255, 0.4);
                backdrop-filter: blur(6px);
                -webkit-backdrop-filter: blur(6px);
                border-radius: 12px;
                padding: 10px 18px;
                font-size: 12.5px;
                font-weight: 900;
                cursor: pointer;
                transition: all 0.25s;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 6px;
                box-sizing: border-box;
            }
            .gk-btn-settings-brand:hover {
                background: #ffffff;
                color: #6366f1 !important;
                border-color: #ffffff;
                transform: translateY(-1px);
            }

            /* سهمیه */
            .gk-quota-card {
                background: linear-gradient(135deg, #ffffff 0%, #f5f3ff 100%);
                border: 2px solid #ddd6fe;
                border-radius: 20px;
                padding: 18px 24px;
                margin-bottom: 24px;
                box-shadow: 0 6px 20px rgba(109, 40, 217, 0.04);
                box-sizing: border-box;
            }
            .gk-quota-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 10px;
                font-weight: 800;
                font-size: 13.5px;
                color: #1e1b4b;
                gap: 8px;
            }
            .gk-quota-bar {
                height: 12px;
                background: #e2e8f0;
                border-radius: 99px;
                overflow: hidden;
                margin-bottom: 8px;
            }
            .gk-quota-fill {
                height: 100%;
                width: <?php echo $pct_used; ?>%;
                background: linear-gradient(90deg, #10b981 0%, #6366f1 70%, #d946ef 100%);
                border-radius: 99px;
                transition: width 0.4s ease;
            }

            /* گرید ۲ ستونه دسکتاپ */
            .gk-school-tabs-grid {
                display: grid;
                grid-template-columns: 370px 1fr;
                gap: 24px;
                box-sizing: border-box;
            }
            .gk-panel-box {
                background: #ffffff;
                border: 2px solid #e2e8f0;
                border-top: 4px solid #6366f1;
                border-radius: 22px;
                padding: 22px;
                box-shadow: 0 6px 24px rgba(0,0,0,0.03);
                box-sizing: border-box;
            }
            .gk-box-head {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 18px;
                padding-bottom: 12px;
                border-bottom: 2px dashed #f1f5f9;
            }
            .gk-box-head h3 {
                font-size: 16.5px !important;
                font-weight: 900 !important;
                color: #1e293b !important;
                margin: 0 !important;
            }
            .gk-btn-primary {
                background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
                color: #fff !important;
                border: none;
                border-radius: 12px;
                padding: 10px 18px;
                font-weight: 900;
                font-size: 13px;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                text-decoration: none !important;
                transition: transform 0.2s, box-shadow 0.2s;
                box-shadow: 0 4px 14px rgba(99, 102, 241, 0.25);
                box-sizing: border-box;
            }
            .gk-btn-primary:hover {
                transform: translateY(-1px);
                box-shadow: 0 6px 18px rgba(99, 102, 241, 0.35);
            }

            /* کلاس‌ها در دسکتاپ */
            #gk-classes-list {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
                width: 100%;
                box-sizing: border-box;
                margin-top: 14px;
            }
            .gk-class-card-item {
                background: #ffffff;
                border: 2px solid #e2e8f0;
                border-radius: 16px;
                padding: 12px 14px;
                margin-bottom: 0;
                transition: all 0.2s ease;
                cursor: pointer;
                position: relative;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                min-height: 80px;
                box-sizing: border-box;
                box-shadow: 0 2px 8px rgba(0,0,0,0.03);
            }
            .gk-class-card-item:hover {
                border-color: #0284c7;
                transform: translateY(-2px);
                background: #f0f9ff;
                box-shadow: 0 6px 18px rgba(2,132,199,0.12);
            }
            .gk-class-card-item.active-class {
                border-color: #0284c7;
                background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            }
            .gk-class-top-row {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 6px;
                gap: 4px;
            }
            .gk-class-title {
                font-size: 13.5px !important;
                font-weight: 900 !important;
                color: #0f172a !important;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .gk-class-badge {
                background: #e0f2fe;
                color: #0369a1;
                font-size: 11px;
                font-weight: 800;
                padding: 2px 7px;
                border-radius: 10px;
                white-space: nowrap;
            }
            .gk-class-bottom-row {
                display: flex;
                justify-content: space-between;
                align-items: center;
                font-size: 11.5px;
                color: #64748b;
            }
            .gk-class-teacher-snippet {
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                font-weight: 700;
            }
            .gk-class-info-icon {
                font-size: 13px;
                color: #0284c7;
            }

            /* تب‌های فیلتر */
            .gk-filter-tabs {
                display: flex;
                gap: 8px;
                flex-wrap: wrap;
                margin-bottom: 16px;
                padding-bottom: 14px;
                border-bottom: 1.5px solid #f1f5f9;
            }
            .gk-filter-btn {
                background: #f8fafc;
                color: #475569;
                border: 1.5px solid #e2e8f0;
                padding: 6px 14px;
                border-radius: 30px;
                font-size: 12.5px;
                font-weight: 800;
                cursor: pointer;
                transition: all 0.2s;
                box-sizing: border-box;
            }
            .gk-filter-btn:hover {
                background: #eef2ff;
                color: #4f46e5;
                border-color: #c7d2fe;
            }
            .gk-filter-btn.active {
                background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
                color: #fff;
                border-color: #4f46e5;
                box-shadow: 0 4px 14px rgba(79, 70, 229, 0.3);
            }

            /* فرم افزودن دانش‌آموز در دسکتاپ */
            .gk-add-student-form-grid {
                display: grid;
                grid-template-columns: 1.4fr 0.8fr 1fr 1.2fr auto;
                gap: 8px;
                background: #f8fafc;
                border: 1.5px dashed #cbd5e1;
                border-radius: 16px;
                padding: 14px;
                margin-bottom: 20px;
                align-items: flex-end;
                box-sizing: border-box;
            }
            .gk-school-wrap input[type="text"],
            .gk-school-wrap input[type="url"],
            .gk-school-wrap select {
                width: 100%;
                box-sizing: border-box;
                border: 1.5px solid #cbd5e1;
                border-radius: 10px;
                padding: 8px 12px;
                font-size: 13px;
                background: #fff;
                color: #1e293b;
                outline: none;
                transition: border-color 0.2s, box-shadow 0.2s;
            }
            .gk-school-wrap input:focus,
            .gk-school-wrap select:focus {
                border-color: #6366f1;
                box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
            }

            /* جدول نوآموزان دسکتاپ */
            .gk-table-responsive-wrapper {
                width: 100%;
                overflow-x: auto;
                box-sizing: border-box;
            }
            .gk-students-table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 0 8px;
                text-align: right;
            }
            .gk-students-table th {
                background: transparent;
                padding: 8px 10px;
                font-size: 12.5px;
                font-weight: 800;
                color: #64748b;
            }
            .gk-student-row {
                background: #f8fafc;
                border-radius: 14px;
                transition: transform 0.2s, background-color 0.4s ease;
            }
            .gk-student-row:hover {
                background: #f1f5f9;
            }
            .gk-student-row td {
                padding: 12px 10px;
                font-size: 13px;
                vertical-align: middle;
                border-top: 1.5px solid #e2e8f0;
                border-bottom: 1.5px solid #e2e8f0;
            }
            .gk-student-row td:first-child {
                border-right: 1.5px solid #e2e8f0;
                border-top-right-radius: 14px;
                border-bottom-right-radius: 14px;
            }
            .gk-student-row td:last-child {
                border-left: 1.5px solid #e2e8f0;
                border-top-left-radius: 14px;
                border-bottom-left-radius: 14px;
            }

            .gk-phone-badge {
                background: #f1f5f9;
                border: 1px solid #cbd5e1;
                padding: 3px 8px;
                border-radius: 8px;
                font-size: 12px;
                font-weight: 800;
                color: #1e293b;
                display: inline-flex;
                align-items: center;
                gap: 4px;
                direction: ltr;
            }
            .gk-action-icons-group {
                display: inline-flex;
                align-items: center;
                gap: 6px;
            }
            .gk-icon-btn {
                width: 34px;
                height: 34px;
                border-radius: 10px;
                border: 1.5px solid transparent;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                text-decoration: none !important;
                transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
                box-sizing: border-box;
                padding: 0;
            }
            .gk-icon-btn:hover {
                transform: translateY(-2px) scale(1.08);
            }
            .gk-btn-bale {
                background: #f0fdf4;
                color: #16a34a !important;
                border-color: #bbf7d0;
            }
            .gk-btn-bale:hover {
                background: #16a34a;
                color: #ffffff !important;
                border-color: #16a34a;
                box-shadow: 0 4px 12px rgba(22, 163, 74, 0.35);
            }
            .gk-btn-copy {
                background: #eef2ff;
                color: #6366f1 !important;
                border-color: #c7d2fe;
            }
            .gk-btn-copy:hover {
                background: #6366f1;
                color: #ffffff !important;
                border-color: #6366f1;
                box-shadow: 0 4px 12px rgba(99, 102, 241, 0.35);
            }
            .gk-btn-edit {
                background: #fffbeb;
                color: #d97706 !important;
                border-color: #fde68a;
            }
            .gk-btn-edit:hover {
                background: #f59e0b;
                color: #ffffff !important;
                border-color: #f59e0b;
                box-shadow: 0 4px 12px rgba(245, 158, 11, 0.35);
            }
            .gk-btn-delete {
                background: #fff1f2;
                color: #e11d48 !important;
                border-color: #fecdd3;
            }
            .gk-btn-delete:hover {
                background: #e11d48;
                color: #ffffff !important;
                border-color: #e11d48;
                box-shadow: 0 4px 12px rgba(225, 29, 72, 0.35);
            }

            /* مدال‌ها و پنجره‌های پاپ‌آپ */
            .gk-modal-backdrop {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                bottom: 0 !important;
                background: rgba(15, 23, 42, 0.7) !important;
                z-index: 9999999 !important;
                backdrop-filter: blur(6px) !important;
                -webkit-backdrop-filter: blur(6px) !important;
                display: none;
                align-items: center !important;
                justify-content: center !important;
                overflow-y: auto !important;
                padding: 20px 16px !important;
                box-sizing: border-box !important;
            }
            .gk-modal-backdrop[style*="display: block"],
            .gk-modal-backdrop[style*="display:block"] {
                display: flex !important;
            }
            .gk-modal-card {
                background: #ffffff !important;
                border-radius: 26px !important;
                padding: 24px 26px !important;
                box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3) !important;
                border: 2px solid #e2e8f0 !important;
                max-height: 90vh !important;
                overflow-y: auto !important;
                width: 100% !important;
                max-width: 500px !important;
                box-sizing: border-box !important;
                position: relative !important;
                margin: auto !important;
            }
            .gk-branding-form-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                gap: 14px;
                box-sizing: border-box;
            }

            /* ==========================================================
               ۲. واکنش‌گرایی اختصاصی موبایل و تبلت (Mobile View: <= 960px)
               ========================================================== */
            @media (max-width: 960px) {
                .gk-school-wrap {
                    margin: 10px auto 35px auto !important;
                    padding: 0 10px !important;
                }
                .gk-school-header-card {
                    padding: 16px 14px !important;
                    border-radius: 18px !important;
                    flex-direction: column !important;
                    align-items: stretch !important;
                    gap: 14px !important;
                }
                .gk-school-brand-info {
                    gap: 12px !important;
                }
                .gk-school-logo-thumb {
                    width: 48px !important;
                    height: 48px !important;
                    font-size: 22px !important;
                    border-radius: 14px !important;
                }
                .gk-school-title {
                    font-size: 16px !important;
                }
                .gk-school-subtitle {
                    font-size: 11.5px !important;
                    gap: 6px 10px !important;
                    line-height: 1.6 !important;
                }
                .gk-btn-settings-brand {
                    width: 100% !important;
                    min-height: 44px !important;
                    font-size: 12.5px !important;
                    padding: 10px 14px !important;
                }

                .gk-quota-card {
                    padding: 14px 14px !important;
                    border-radius: 16px !important;
                    margin-bottom: 16px !important;
                }
                .gk-quota-header {
                    flex-direction: column !important;
                    align-items: flex-start !important;
                    gap: 6px !important;
                    font-size: 12.5px !important;
                }
                .gk-quota-bar {
                    height: 10px !important;
                    margin-bottom: 6px !important;
                }

                /* تبدیل چیدمان دسکتاپ به تک‌ستونه عمودی در موبایل */
                .gk-school-tabs-grid {
                    display: flex !important;
                    flex-direction: column !important;
                    gap: 16px !important;
                }
                .gk-panel-box {
                    padding: 16px 12px !important;
                    border-radius: 18px !important;
                }
                .gk-box-head {
                    flex-wrap: wrap !important;
                    gap: 8px !important;
                    margin-bottom: 14px !important;
                    padding-bottom: 10px !important;
                }
                .gk-box-head h3 {
                    font-size: 15px !important;
                }

                /* تایل‌های کلاس‌ها: ۲ تایی در موبایل */
                #gk-classes-list {
                    display: grid !important;
                    grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                    gap: 8px !important;
                    width: 100% !important;
                    box-sizing: border-box !important;
                }
                .gk-class-card-item {
                    min-width: 0 !important;
                    width: 100% !important;
                    padding: 10px 8px !important;
                    border-radius: 14px !important;
                    min-height: 70px !important;
                    display: flex !important;
                    flex-direction: column !important;
                    justify-content: space-between !important;
                    box-sizing: border-box !important;
                }
                .gk-class-top-row {
                    display: flex !important;
                    justify-content: space-between !important;
                    align-items: center !important;
                    margin-bottom: 4px !important;
                    gap: 4px !important;
                    width: 100% !important;
                }
                .gk-class-title {
                    font-size: 12px !important;
                    font-weight: 900 !important;
                    white-space: nowrap !important;
                    overflow: hidden !important;
                    text-overflow: ellipsis !important;
                    max-width: 65% !important;
                }
                .gk-class-badge {
                    font-size: 9.5px !important;
                    padding: 2px 5px !important;
                    border-radius: 6px !important;
                    white-space: nowrap !important;
                    flex-shrink: 0 !important;
                }
                .gk-class-bottom-row {
                    display: flex !important;
                    justify-content: space-between !important;
                    align-items: center !important;
                    font-size: 10.5px !important;
                    width: 100% !important;
                }
                .gk-class-teacher-snippet {
                    font-size: 10.5px !important;
                    white-space: nowrap !important;
                    overflow: hidden !important;
                    text-overflow: ellipsis !important;
                    max-width: 80% !important;
                }

                /* نوار جستجو و فیلتر در موبایل */
                .gk-search-filter-bar {
                    flex-direction: column !important;
                    align-items: stretch !important;
                    gap: 8px !important;
                    padding: 10px 10px !important;
                }
                .gk-search-filter-bar > div {
                    width: 100% !important;
                    min-width: 100% !important;
                }
                #gk-clear-filter-btn {
                    width: 100% !important;
                    justify-content: center !important;
                    min-height: 38px !important;
                }
                .gk-school-wrap input[type="text"],
                .gk-school-wrap input[type="url"],
                .gk-school-wrap select {
                    min-height: 44px !important;
                    font-size: 13.5px !important;
                    border-radius: 12px !important;
                    padding: 9px 12px !important;
                    box-sizing: border-box !important;
                }
                #gk-btn-add-student {
                    width: 100% !important;
                    min-height: 46px !important;
                    font-size: 13.5px !important;
                    margin-top: 4px !important;
                }

                /* تبدیل جدول دانش‌آموزان به کارت‌های مستقل در موبایل */
                .gk-table-responsive-wrapper {
                    overflow: visible !important;
                    overflow-x: visible !important;
                    width: 100% !important;
                }
                .gk-students-table {
                    display: block !important;
                    width: 100% !important;
                    border: none !important;
                    border-collapse: separate !important;
                    border-spacing: 0 !important;
                }
                .gk-students-table thead {
                    display: none !important;
                }
                .gk-students-table tbody {
                    display: flex !important;
                    flex-direction: column !important;
                    gap: 8px !important;
                    width: 100% !important;
                }
                .gk-student-row {
                    display: grid !important;
                    grid-template-columns: 1fr auto !important;
                    grid-template-rows: auto auto !important;
                    gap: 6px 8px !important;
                    align-items: center !important;
                    width: 100% !important;
                    background: #ffffff !important;
                    border: 1.5px solid #e2e8f0 !important;
                    border-radius: 14px !important;
                    padding: 9px 12px !important;
                    margin-bottom: 0 !important;
                    box-sizing: border-box !important;
                    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03) !important;
                }
                .gk-student-row td {
                    display: flex !important;
                    align-items: center !important;
                    padding: 0 !important;
                    border: none !important;
                    border-radius: 0 !important;
                    background: transparent !important;
                    text-align: right !important;
                    box-sizing: border-box !important;
                    min-width: 0 !important;
                }
                .gk-student-row td:nth-child(1) {
                    grid-column: 1 / 2 !important;
                    grid-row: 1 / 2 !important;
                    justify-content: flex-start !important;
                }
                .gk-student-row td:nth-child(2) {
                    grid-column: 2 / 3 !important;
                    grid-row: 1 / 2 !important;
                    justify-content: flex-end !important;
                    gap: 4px !important;
                }
                .gk-student-row td:nth-child(3) {
                    grid-column: 1 / 2 !important;
                    grid-row: 2 / 3 !important;
                    justify-content: flex-start !important;
                }
                .gk-student-row td:nth-child(4),
                .gk-student-row td:last-child {
                    grid-column: 2 / 3 !important;
                    grid-row: 2 / 3 !important;
                    justify-content: flex-end !important;
                    margin-top: 0 !important;
                    padding-top: 0 !important;
                    border-top: none !important;
                }
                .gk-student-row .st-row-name {
                    font-size: 13.5px !important;
                    color: #0f172a !important;
                    white-space: nowrap !important;
                    overflow: hidden !important;
                    text-overflow: ellipsis !important;
                }
                .gk-student-row .st-row-class {
                    font-size: 11px !important;
                    padding: 2px 6px !important;
                }
                .gk-student-row .st-row-age {
                    font-size: 10.5px !important;
                }
                .gk-student-row .gk-phone-badge {
                    font-size: 11px !important;
                    padding: 2px 6px !important;
                }
                .gk-student-row .gk-action-icons-group {
                    display: flex !important;
                    align-items: center !important;
                    gap: 4px !important;
                    width: auto !important;
                }
                .gk-student-row .gk-icon-btn {
                    width: 30px !important;
                    height: 30px !important;
                    border-radius: 8px !important;
                    display: flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                }
                .gk-student-row .gk-icon-btn svg {
                    width: 15px !important;
                    height: 15px !important;
                }

                /* پاپ‌آپ‌ها در موبایل */
                .gk-modal-backdrop {
                    padding: 16px 12px !important;
                }
                .gk-modal-card {
                    padding: 20px 16px !important;
                    border-radius: 20px !important;
                    max-width: 100% !important;
                    margin: auto !important;
                }
                .gk-branding-form-grid {
                    grid-template-columns: 1fr !important;
                    gap: 10px !important;
                }
                #gk-btn-save-branding {
                    width: 100% !important;
                    min-height: 46px !important;
                }
            }
        </style>

        <div class="gk-school-wrap" id="gk-school-app">
            
            <!-- هدر مرکز -->
            <div class="gk-school-header-card">
                <div class="gk-school-brand-info">
                    <div class="gk-school-logo-thumb" id="gk-header-logo-container">
                        <?php if (!empty($org->logo_url)): ?>
                            <img src="<?php echo esc_url($org->logo_url); ?>" alt="<?php echo esc_attr($org->name); ?>" id="gk-header-logo-img">
                        <?php else: ?>
                            🏢
                        <?php endif; ?>
                    </div>
                    <div>
                        <h1 class="gk-school-title" id="gk-header-org-name"><?php echo esc_html($org->name); ?></h1>
                        <p class="gk-school-subtitle">
                            📍 <span id="gk-header-city"><?php echo esc_html($org->city ?: 'ایران'); ?></span> | 📞 <span id="gk-header-phone"><?php echo esc_html($org->phone ?: 'ثبت نشده'); ?></span>
                            | 📅 انقضای اشتراک: <?php echo date_i18n('j F Y', strtotime($org->expires_at)); ?>
                        </p>
                    </div>
                </div>
                <div>
                    <button type="button" class="gk-btn-settings-brand" onclick="jQuery('#gk-branding-modal').toggle();">
                        ⚙️ تنظیمات برند و لوگوی مرکز
                    </button>
                </div>
            </div>

            <!-- وضعیت سهمیه -->
            <div class="gk-quota-card">
                <div class="gk-quota-header">
                    <span>📊 ظرفیت کل دانش‌آموزان / نوآموزان: <strong><span id="gk-stat-students-count"><?php echo $total_students; ?></span> از <?php echo $limit; ?> کودک</strong></span>
                    <span style="color: <?php echo $remaining > 5 ? '#10b981' : '#ef4444'; ?>;">
                        <?php echo $remaining > 0 ? "🟢 $remaining سهمیه خالی باقیمانده" : '🔴 سهمیه تکمیل شده'; ?>
                    </span>
                </div>
                <div class="gk-quota-bar">
                    <div class="gk-quota-fill" id="gk-quota-fill-bar"></div>
                </div>
                <?php if ($is_full): ?>
                    <div id="gk-quota-full-alert" style="font-size:13px; color:#b91c1c; font-weight:bold; text-align:center;">
                        ⚠️ سقف ظرفیت تکمیل است. برای افزودن کودکان بیشتر، <a href="<?php echo esc_url(home_url('/pricing/')); ?>" style="color:#6c5ce7; text-decoration:underline;">ظرفیت را ارتقا دهید</a>.
                    </div>
                <?php endif; ?>
            </div>

            <!-- فرم پاپ‌آپ ویرایش لوگو و برندینگ -->
            <div id="gk-branding-modal" style="display:none; background:#f8fafc; border:2px solid #cbd5e1; border-radius:20px; padding:20px; margin-bottom:26px;">
                <h3 style="margin:0 0 14px 0; font-size:16px;">⚙️ تنظیمات برند مدرسه / مهدکودک (درج در سربرگ کارنامه‌ها):</h3>
                <div class="gk-branding-form-grid">
                    <div>
                        <label style="font-weight:bold; font-size:12.5px;">نام مدرسه، مهدکودک یا مرکز آموزشی:</label>
                        <input type="text" id="gk-org-name" value="<?php echo esc_attr($org->name); ?>" style="width:100%; padding:8px 12px; border-radius:10px; border:1.5px solid #cbd5e1;">
                    </div>
                    <div>
                        <label style="font-weight:bold; font-size:12.5px;">شهر:</label>
                        <input type="text" id="gk-org-city" value="<?php echo esc_attr($org->city); ?>" style="width:100%; padding:8px 12px; border-radius:10px; border:1.5px solid #cbd5e1;">
                    </div>
                    <div>
                        <label style="font-weight:bold; font-size:12.5px;">تلفن تماس مرکز:</label>
                        <input type="text" id="gk-org-phone" value="<?php echo esc_attr($org->phone); ?>" style="width:100%; padding:8px 12px; border-radius:10px; border:1.5px solid #cbd5e1;">
                    </div>
                    <div>
                        <label style="font-weight:bold; font-size:12.5px;">لینک لوگوی مرکز (تصویر PNG یا JPG):</label>
                        <input type="url" id="gk-org-logo" value="<?php echo esc_url($org->logo_url); ?>" placeholder="https://..." style="width:100%; padding:8px 12px; border-radius:10px; border:1.5px solid #cbd5e1;">
                    </div>
                </div>
                <button type="button" class="gk-btn-primary" id="gk-btn-save-branding" style="margin-top:12px;">
                    💾 ذخیره تغییرات مشخصات مرکز
                </button>
            </div>

            <!-- مدال ویرایش اطلاعات نوآموز -->
            <div id="gk-edit-student-modal" class="gk-modal-backdrop" style="display:none;">
                <div class="gk-modal-card" style="max-width:480px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; border-bottom:1.5px solid #f1f5f9; padding-bottom:12px;">
                        <h3 style="margin:0; font-size:17px; color:#1e293b;">✏️ ویرایش مشخصات نوآموز</h3>
                        <button type="button" onclick="gkCloseModal('#gk-edit-student-modal');" style="background:none; border:none; font-size:20px; cursor:pointer; color:#64748b;">✕</button>
                    </div>
                    <input type="hidden" id="gk-edit-st-id">
                    <div style="margin-bottom:12px;">
                        <label style="font-size:12.5px; font-weight:bold; color:#475569; display:block; margin-bottom:6px;">نام و نام خانوادگی:</label>
                        <input type="text" id="gk-edit-st-name" style="width:100%; padding:9px 12px; border-radius:10px; border:1.5px solid #cbd5e1; font-size:13px; box-sizing:border-box;">
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:12px;">
                        <div>
                            <label style="font-size:12.5px; font-weight:bold; color:#475569; display:block; margin-bottom:6px;">سن:</label>
                            <select id="gk-edit-st-age" style="width:100%; padding:9px 12px; border-radius:10px; border:1.5px solid #cbd5e1; font-size:13px; box-sizing:border-box;">
                                <option value="4">۴ ساله</option>
                                <option value="5">۵ ساله</option>
                                <option value="6">۶ ساله</option>
                                <option value="7">۷ ساله</option>
                                <option value="8">۸ ساله</option>
                            </select>
                        </div>
                        <div>
                            <label style="font-size:12.5px; font-weight:bold; color:#475569; display:block; margin-bottom:6px;">کلاس:</label>
                            <select id="gk-edit-st-class" style="width:100%; padding:9px 12px; border-radius:10px; border:1.5px solid #cbd5e1; font-size:13px; box-sizing:border-box;">
                                <?php foreach ($classes as $c): ?>
                                    <option value="<?php echo $c->id; ?>"><?php echo esc_html($c->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div style="margin-bottom:18px;">
                        <label style="font-size:12.5px; font-weight:bold; color:#475569; display:block; margin-bottom:6px;">شماره همراه مادر (بله):</label>
                        <input type="text" id="gk-edit-st-phone" style="width:100%; padding:9px 12px; border-radius:10px; border:1.5px solid #cbd5e1; font-size:13px; box-sizing:border-box;">
                    </div>
                    <div style="display:flex; gap:10px;">
                        <button type="button" class="gk-btn-primary" id="gk-btn-save-edit-st" style="flex:1; padding:11px 0; text-align:center;">
                            💾 ذخیره تغییرات
                        </button>
                        <button type="button" onclick="gkCloseModal('#gk-edit-student-modal');" style="background:#f1f5f9; border:1.5px solid #cbd5e1; border-radius:12px; padding:0 18px; font-weight:bold; font-size:13px; cursor:pointer;">
                            انصراف
                        </button>
                    </div>
                </div>
            </div>

            <!-- پنجره پاپ‌آپ ثبت دانش‌آموز جدید -->
            <div id="gk-new-student-modal" class="gk-modal-backdrop" style="display:none;">
                <div class="gk-modal-card" style="max-width:480px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; border-bottom:2px solid #f1f5f9; padding-bottom:14px;">
                        <div style="display:flex; align-items:center; gap:10px;">
                            <div style="width:44px; height:44px; border-radius:14px; background:linear-gradient(135deg,#e0e7ff,#c7d2fe); display:flex; align-items:center; justify-content:center; font-size:22px;">
                                👶
                            </div>
                            <div>
                                <h3 style="margin:0; font-size:17.5px; font-weight:900; color:#1e1b4b;">ثبت دانش‌آموز / نوآموز جدید</h3>
                                <span style="font-size:12px; color:#64748b;">مشخصات نوآموز را وارد فرمایید</span>
                            </div>
                        </div>
                        <button type="button" onclick="gkCloseModal('#gk-new-student-modal');" style="background:#f1f5f9; border:none; width:34px; height:34px; border-radius:50%; font-size:16px; cursor:pointer; color:#64748b; font-weight:900; display:flex; align-items:center; justify-content:center;">✕</button>
                    </div>

                    <div style="display:flex; flex-direction:column; gap:14px;">
                        <div>
                            <label style="font-size:12.5px; font-weight:800; color:#334155; display:block; margin-bottom:6px;">👶 نام و نام خانوادگی:</label>
                            <input type="text" id="gk-student-name" placeholder="مثلاً: آرتین رضایی" style="width:100%; border:1.5px solid #cbd5e1; border-radius:12px; padding:10px 12px; font-size:13px; box-sizing:border-box;">
                        </div>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                            <div>
                                <label style="font-size:12.5px; font-weight:800; color:#334155; display:block; margin-bottom:6px;">🎂 سن نوآموز:</label>
                                <select id="gk-student-age" style="width:100%; border:1.5px solid #cbd5e1; border-radius:12px; padding:10px 12px; font-size:13px; box-sizing:border-box; background:#fff;">
                                    <option value="4">۴ ساله</option>
                                    <option value="5" selected>۵ ساله</option>
                                    <option value="6">۶ ساله</option>
                                    <option value="7">۷ ساله</option>
                                    <option value="8">۸ ساله</option>
                                </select>
                            </div>
                            <div>
                                <label style="font-size:12.5px; font-weight:800; color:#334155; display:block; margin-bottom:6px;">🏫 کلاس انتخابی:</label>
                                <select id="gk-student-class" style="width:100%; border:1.5px solid #cbd5e1; border-radius:12px; padding:10px 12px; font-size:13px; box-sizing:border-box; background:#fff;">
                                    <?php foreach ($classes as $c): ?>
                                        <option value="<?php echo $c->id; ?>"><?php echo esc_html($c->name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label style="font-size:12.5px; font-weight:800; color:#334155; display:block; margin-bottom:6px;">📱 شماره همراه مادر (جهت ارسال لینک بله):</label>
                            <input type="text" id="gk-student-phone" placeholder="0912..." style="width:100%; border:1.5px solid #cbd5e1; border-radius:12px; padding:10px 12px; font-size:13px; box-sizing:border-box; direction:ltr; text-align:right;">
                        </div>

                        <div style="display:flex; gap:10px; margin-top:8px; border-top:1.5px solid #f1f5f9; padding-top:16px;">
                            <button type="button" class="gk-btn-primary" id="gk-btn-add-student" style="flex:1; padding:12px 0; font-size:13px; font-weight:900; text-align:center; border-radius:12px; cursor:pointer;">
                                🎯 ثبت و افزودن نوآموز
                            </button>
                            <button type="button" onclick="gkCloseModal('#gk-new-student-modal');" style="background:#f8fafc; color:#64748b; border:1.5px solid #cbd5e1; padding:12px 20px; border-radius:12px; font-size:13px; font-weight:800; cursor:pointer;">
                                انصراف
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ۱. پنجره پاپ‌آپ ایجاد کلاس جدید -->
            <div id="gk-new-class-modal" class="gk-modal-backdrop" style="display:none;">
                <div class="gk-modal-card" style="max-width:480px;">
                    
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; border-bottom:2px solid #f1f5f9; padding-bottom:14px;">
                        <div style="display:flex; align-items:center; gap:10px;">
                            <div style="width:44px; height:44px; border-radius:14px; background:linear-gradient(135deg,#e0e7ff,#c7d2fe); display:flex; align-items:center; justify-content:center; font-size:22px;">
                                🏫
                            </div>
                            <div>
                                <h3 style="margin:0; font-size:17.5px; font-weight:900; color:#1e1b4b;">ایجاد و تعریف کلاس جدید</h3>
                                <span style="font-size:12px; color:#64748b;">مشخصات کلاس و مربی را وارد فرمایید</span>
                            </div>
                        </div>
                        <button type="button" onclick="gkCloseModal('#gk-new-class-modal');" style="background:#f1f5f9; border:none; width:34px; height:34px; border-radius:50%; font-size:16px; cursor:pointer; color:#64748b; font-weight:900; display:flex; align-items:center; justify-content:center;">✕</button>
                    </div>

                    <div style="display:flex; flex-direction:column; gap:14px;">
                        <div>
                            <label style="font-size:12.5px; font-weight:800; color:#334155; display:block; margin-bottom:6px;">🏫 نام کلاس:</label>
                            <input type="text" id="gk-new-class-name" placeholder="مثلاً: کلاس پیش‌دبستانی شکوفه‌ها" style="width:100%; border:1.5px solid #cbd5e1; border-radius:12px; padding:10px 12px; font-size:13px; box-sizing:border-box;">
                        </div>

                        <div>
                            <label style="font-size:12.5px; font-weight:800; color:#334155; display:block; margin-bottom:6px;">👩‍🏫 نام و نام‌خانوادگی مربی:</label>
                            <input type="text" id="gk-new-teacher-name" placeholder="مثلاً: خانم مریم رضایی" style="width:100%; border:1.5px solid #cbd5e1; border-radius:12px; padding:10px 12px; font-size:13px; box-sizing:border-box;">
                        </div>

                        <div>
                            <label style="font-size:12.5px; font-weight:800; color:#334155; display:block; margin-bottom:6px;">📱 شماره همراه مربی در پیام‌رسان بله (اختیاری):</label>
                            <input type="text" id="gk-new-teacher-phone" placeholder="0912..." style="width:100%; border:1.5px solid #cbd5e1; border-radius:12px; padding:10px 12px; font-size:13px; box-sizing:border-box; direction:ltr; text-align:right;">
                        </div>

                        <div style="display:flex; gap:10px; margin-top:8px; border-top:1.5px solid #f1f5f9; padding-top:16px;">
                            <button type="button" class="gk-btn-primary" id="gk-btn-add-class" style="flex:1; padding:12px 0; font-size:13px; font-weight:900; text-align:center; border-radius:12px; cursor:pointer;">
                                🚀 ایجاد کلاس و ثبت مربی
                            </button>
                            <button type="button" onclick="gkCloseModal('#gk-new-class-modal');" style="background:#f8fafc; color:#64748b; border:1.5px solid #cbd5e1; padding:12px 20px; border-radius:12px; font-size:13px; font-weight:800; cursor:pointer;">
                                انصراف
                            </button>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ۲. پنجره لوکس مدیریت و جزئیات کلاس (دسته‌بندی‌شده و استاندارد) -->
            <div id="gk-class-info-modal" class="gk-modal-backdrop" style="display:none;">
                <div class="gk-modal-card" style="max-width:540px;">
                    
                    <!-- 1. Header -->
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; border-bottom:2px solid #f1f5f9; padding-bottom:14px;">
                        <div style="display:flex; align-items:center; gap:12px;">
                            <div style="width:46px; height:46px; border-radius:14px; background:linear-gradient(135deg,#e0e7ff,#c7d2fe); display:flex; align-items:center; justify-content:center; font-size:24px;">
                                🏫
                            </div>
                            <div>
                                <h3 id="gk-m-class-title" style="margin:0; font-size:18px; font-weight:900; color:#1e1b4b;">کلاس</h3>
                                <span id="gk-m-class-count-tag" style="font-size:12px; color:#4338ca; font-weight:800; background:#e0e7ff; padding:2px 10px; border-radius:10px; display:inline-block; margin-top:3px;">۰ نوآموز</span>
                            </div>
                        </div>
                        <button type="button" onclick="gkCloseModal('#gk-class-info-modal');" style="background:#f1f5f9; border:none; width:34px; height:34px; border-radius:50%; font-size:16px; cursor:pointer; color:#64748b; font-weight:900; display:flex; align-items:center; justify-content:center;">✕</button>
                    </div>

                    <!-- 2. دسته‌بندی ۱: بخش مربی و ورود به پنل کلاس -->
                    <div style="background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:18px; padding:16px; margin-bottom:14px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; font-size:13px; color:#334155;">
                            <div style="display:flex; align-items:center; gap:6px;">
                                <span>👩‍🏫</span>
                                <span>مربی کلاس: <strong id="gk-m-teacher-name" style="color:#0f172a; font-weight:900;">ثبت نشده</strong></span>
                            </div>
                            <span id="gk-m-teacher-phone" style="font-size:12px; color:#64748b; font-weight:700; direction:ltr;"></span>
                        </div>

                        <!-- Action Group: Teacher Portal & Connect -->
                        <div class="gk-class-actions-row" style="display:flex; gap:8px;">
                            <a id="gk-m-teacher-portal-btn" href="#" target="_blank" style="flex:2; background:linear-gradient(135deg,#0284c7,#0369a1); color:#ffffff !important; padding:10px 14px; border-radius:12px; text-decoration:none; font-size:12.5px; font-weight:900; display:flex; align-items:center; justify-content:center; gap:6px; box-shadow:0 3px 10px rgba(2,132,199,0.3);">
                                <span>🚀</span>
                                <span>ورود به پنل معلم</span>
                            </a>
                            <a id="gk-m-bale-teacher-btn" href="#" target="_blank" style="flex:1.5; background:linear-gradient(135deg,#10b981,#059669); color:#ffffff !important; padding:10px 12px; border-radius:12px; text-decoration:none; font-size:12px; font-weight:900; display:flex; align-items:center; justify-content:center; gap:6px; box-shadow:0 3px 10px rgba(16,185,129,0.3);" title="ارسال لینک مستقیم به بله مربی">
                                <span>📲</span>
                                <span>بله مربی</span>
                            </a>
                            <button type="button" onclick="gkCopyClassInviteLink();" style="flex:1; background:#ffffff; color:#334155 !important; border:1.5px solid #cbd5e1; padding:10px 10px; border-radius:12px; font-size:12px; font-weight:900; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:4px;" title="کپی لینک ورود معلم به کلاس">
                                <span>📋</span>
                                <span>کپی</span>
                            </button>
                        </div>
                    </div>

                    <!-- 3. دسته‌بندی ۲: ابزارهای کلاس و اشتراک‌گذاری با اولیا -->
                    <div style="margin-bottom:16px;">
                        <div style="font-size:12px; font-weight:900; color:#64748b; margin-bottom:8px; display:flex; align-items:center; gap:6px;">
                            <span>📢</span> ابزارهای کلاسی و اولیا:
                        </div>
                        <div class="gk-class-tools-grid" style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
                            <a id="gk-m-bale-bulk-btn" href="#" target="_blank" style="background:#ecfdf5; color:#065f46 !important; border:1.5px solid #a7f3d0; padding:10px 12px; border-radius:12px; text-decoration:none; font-size:12px; font-weight:900; display:flex; align-items:center; justify-content:center; gap:6px;">
                                <span>📢</span>
                                <span>ارسال گروهی به بله مادران</span>
                            </a>
                            <a id="gk-m-print-btn" href="#" target="_blank" style="background:#eff6ff; color:#1e40af !important; border:1.5px solid #bfdbfe; padding:10px 12px; border-radius:12px; text-decoration:none; font-size:12px; font-weight:900; display:flex; align-items:center; justify-content:center; gap:6px;">
                                <span>🖨️</span>
                                <span>چاپ کارت‌های QR</span>
                            </a>
                        </div>
                    </div>

                    <!-- 4. دسته‌بندی ۳: اقدامات مدیریت کلاس -->
                    <div class="gk-class-bottom-actions-row" style="display:flex; gap:8px; border-top:1.5px solid #f1f5f9; padding-top:14px; align-items:center;">
                        <button type="button" onclick="gkFilterByClassFromModal();" style="flex:2; background:linear-gradient(135deg,#6366f1,#4f46e5); color:#ffffff !important; border:none; padding:11px 0; border-radius:12px; font-size:12.5px; font-weight:900; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:6px; box-shadow:0 3px 10px rgba(79,70,229,0.3);">
                            <span>👶</span>
                            <span>مشاهده نوآموزان این کلاس</span>
                        </button>
                        <button type="button" onclick="gkTriggerEditFromModal();" style="flex:1.2; background:#f8fafc; color:#334155 !important; border:1.5px solid #cbd5e1; padding:11px 0; border-radius:12px; font-size:12px; font-weight:800; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:4px;">
                            <span>✏️</span>
                            <span>ویرایش</span>
                        </button>
                        <button type="button" onclick="gkTriggerDeleteFromModal();" style="background:#fef2f2; color:#dc2626 !important; border:1.5px solid #fecaca; padding:11px 14px; border-radius:12px; font-size:12px; font-weight:900; cursor:pointer;" title="حذف کلاس">
                            <span>🗑️</span>
                        </button>
                    </div>
                </div>
            </div>

            <div id="gk-edit-class-modal" class="gk-modal-backdrop" style="display:none;">
                <div class="gk-modal-card" style="max-width:460px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; border-bottom:1.5px solid #f1f5f9; padding-bottom:12px;">
                        <h3 style="margin:0; font-size:17px; color:#1e293b;">✏️ ویرایش مشخصات کلاس و مربی</h3>
                        <button type="button" onclick="gkCloseModal('#gk-edit-class-modal');" style="background:none; border:none; font-size:20px; cursor:pointer; color:#64748b;">✕</button>
                    </div>
                    <input type="hidden" id="gk-edit-class-id">
                    <div style="margin-bottom:12px;">
                        <label style="font-size:12.5px; font-weight:bold; color:#475569; display:block; margin-bottom:6px;">نام کلاس:</label>
                        <input type="text" id="gk-edit-class-name" style="width:100%; padding:9px 12px; border-radius:10px; border:1.5px solid #cbd5e1; font-size:13px; box-sizing:border-box;">
                    </div>
                    <div style="margin-bottom:12px;">
                        <label style="font-size:12.5px; font-weight:bold; color:#475569; display:block; margin-bottom:6px;">نام مربی / معلم:</label>
                        <input type="text" id="gk-edit-teacher-name" style="width:100%; padding:9px 12px; border-radius:10px; border:1.5px solid #cbd5e1; font-size:13px; box-sizing:border-box;">
                    </div>
                    <div style="margin-bottom:18px;">
                        <label style="font-size:12.5px; font-weight:bold; color:#475569; display:block; margin-bottom:6px;">شماره تماس مربی در بله:</label>
                        <input type="text" id="gk-edit-teacher-phone" style="width:100%; padding:9px 12px; border-radius:10px; border:1.5px solid #cbd5e1; font-size:13px; box-sizing:border-box;">
                    </div>
                    <div style="display:flex; gap:10px;">
                        <button type="button" class="gk-btn-primary" id="gk-btn-save-edit-class" style="flex:1; padding:11px 0; text-align:center;">
                            💾 ذخیره تغییرات کلاس
                        </button>
                        <button type="button" onclick="gkCloseModal('#gk-edit-class-modal');" style="background:#f1f5f9; border:1.5px solid #cbd5e1; border-radius:12px; padding:0 18px; font-weight:bold; font-size:13px; cursor:pointer;">
                            انصراف
                        </button>
                    </div>
                </div>
            </div>

            <!-- ساختار ۲ ستونه -->
            <div class="gk-school-tabs-grid">
                
                <!-- ستون کلاس‌ها (راست) -->
                <div class="gk-panel-box">
                    <div class="gk-box-head" style="display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <h3>🏫 کلاس‌های آموزشی</h3>
                            <span style="font-size:12px; color:#64748b; font-weight:bold;"><strong id="gk-stat-classes-count"><?php echo count($classes); ?></strong> کلاس فعال</span>
                        </div>
                        <button type="button" class="gk-btn-primary" onclick="gkOpenModal('#gk-new-class-modal'); jQuery('#gk-new-class-name').focus();" style="padding:8px 14px; font-size:12.5px; border-radius:12px; display:inline-flex; align-items:center; gap:6px; cursor:pointer;">
                            <span>✨</span>
                            <span>+ ایجاد کلاس جدید</span>
                        </button>
                    </div>

                    <div id="gk-classes-list">
                        <?php if (empty($classes)): ?>
                            <p id="gk-no-classes-msg" style="color:#94a3b8; font-size:13px; text-align:center; padding:15px 0;">هنوز کلاسی تعریف نکرده‌اید.</p>
                        <?php else: ?>
                            <?php foreach ($classes as $c): 
                                $invite_link = home_url('/teacher-class/?code=' . $c->invite_code);
                                $st_count = $class_counts[$c->id] ?? 0;
                                $teacher_display = !empty($c->teacher_name) ? $c->teacher_name : 'ثبت نشده';
                                $teacher_phone = !empty($c->teacher_phone) ? $c->teacher_phone : '';

                                $teacher_msg = "سلام و احترام، همکار گرامی " . ($c->teacher_name ?: '') . "\nلینک ورود اختصاصی به پنل کلاس «" . $c->name . "» در سامانه قربانی کیدز:\n" . $invite_link;
                                $bale_teacher_url = "https://ble.ir/share/url?url=" . urlencode($invite_link) . "&text=" . urlencode($teacher_msg);

                                $class_students = $students_by_class[$c->id] ?? [];
                                $bulk_msg = "📋 لیست لینک‌های اختصاصی آزمون‌ها و بازی‌های استعدادیابی کلاس «" . $c->name . "» (" . $org->name . "):\n\n";
                                foreach ($class_students as $idx => $cs) {
                                    $st_test_url = home_url('/tests/?st_token=' . $cs->student_token);
                                    $bulk_msg .= ($idx + 1) . ". 👶 " . $cs->name . ": " . $st_test_url . "\n";
                                }
                                $bale_bulk_url = "https://ble.ir/share/url?url=" . urlencode(home_url()) . "&text=" . urlencode($bulk_msg);
                                $teacher_portal_url = home_url('/teacher-class/?code=' . $c->invite_code);
                                $print_url = home_url('/?gk_action=print_class_cards&class_id=' . $c->id . '&v=5');
                            ?>
                                <div class="gk-class-card-item" id="gk-class-row-<?php echo $c->id; ?>" 
                                     data-class-id="<?php echo $c->id; ?>" 
                                     data-name="<?php echo esc_attr($c->name); ?>" 
                                     data-teacher-name="<?php echo esc_attr($c->teacher_name ?? ''); ?>" 
                                     data-teacher-phone="<?php echo esc_attr($c->teacher_phone ?? ''); ?>" 
                                     data-bale-teacher="<?php echo esc_url($bale_teacher_url); ?>" 
                                     data-bale-bulk="<?php echo esc_url($bale_bulk_url); ?>" 
                                     data-teacher-portal="<?php echo esc_url($teacher_portal_url); ?>" 
                                     data-print-url="<?php echo esc_url($print_url); ?>" 
                                     data-invite-link="<?php echo esc_url($invite_link); ?>" 
                                     data-count="<?php echo $st_count; ?>" 
                                     onclick="gkOpenClassInfoModal(<?php echo $c->id; ?>);">
                                    <div class="gk-class-top-row">
                                        <div class="gk-class-title">🏫 <?php echo esc_html($c->name); ?></div>
                                        <span class="gk-class-badge" id="gk-class-count-badge-<?php echo $c->id; ?>"><?php echo $st_count; ?> نوآموز</span>
                                    </div>
                                    <div class="gk-class-bottom-row">
                                        <span class="gk-class-teacher-snippet">👩‍🏫 <?php echo esc_html($teacher_display); ?></span>
                                        <span class="gk-class-info-icon" title="مدیریت و جزئیات کلاس">⚙️</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ستون نوآموزان (چپ) -->
                <div class="gk-panel-box">
                    
                    <div class="gk-box-head" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                        <div>
                            <h3 style="margin:0;">🎓 دانش‌آموزان و نوآموزان</h3>
                            <span style="font-size:12px; color:#64748b; font-weight:bold;"><strong id="gk-visible-count"><?php echo $total_students; ?></strong> نوآموز نمایش داده شده</span>
                        </div>
                        <div>
                            <button type="button" class="gk-btn-primary" onclick="gkOpenModal('#gk-new-student-modal'); jQuery('#gk-student-name').focus();" style="padding:9px 16px; font-size:12.5px; border-radius:12px; display:inline-flex; align-items:center; gap:6px; cursor:pointer;">
                                <span>✨</span>
                                <span>+ ثبت دانش‌آموز جدید</span>
                            </button>
                        </div>
                    </div>

                    <!-- نوار ابزار جستجوی زنده و فیلتر کلاس‌ها -->
                    <div class="gk-search-filter-bar" style="display:flex; gap:10px; margin-bottom:16px; flex-wrap:wrap; align-items:center; background:#f8fafc; padding:12px 14px; border-radius:18px; border:1.5px solid #e2e8f0; box-sizing:border-box;">
                        <div style="flex:1; min-width:200px; position:relative;">
                            <input type="text" id="gk-live-search-input" placeholder="🔍 جستجوی زنده (نام، نام‌خانوادگی یا شماره تلفن)..." style="width:100%; padding:9px 12px; border-radius:12px; border:1.5px solid #cbd5e1; font-size:13px; box-sizing:border-box; background:#ffffff;">
                        </div>
                        <div style="min-width:160px;">
                            <select id="gk-class-select-filter" onchange="gkApplyLiveFilter();" style="width:100%; padding:9px 12px; border-radius:12px; border:1.5px solid #cbd5e1; font-size:13px; box-sizing:border-box; background:#ffffff;">
                                <option value="all">🏫 همه کلاس‌ها (<?php echo $total_students; ?>)</option>
                                <?php foreach ($classes as $c): 
                                    $c_count = $class_counts[$c->id] ?? 0;
                                ?>
                                    <option value="<?php echo $c->id; ?>" id="gk-filter-opt-<?php echo $c->id; ?>">🏫 <?php echo esc_html($c->name); ?> (<?php echo $c_count; ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="button" id="gk-clear-filter-btn" onclick="gkResetSearchAndFilters();" title="پاکسازی فیلترها" style="display:none; background:#ffffff; border:1.5px solid #cbd5e1; border-radius:12px; padding:8px 12px; font-size:12px; font-weight:800; cursor:pointer; color:#ef4444; align-items:center; gap:4px;">
                            ✕ پاکسازی
                        </button>
                    </div>

                    <!-- جدول نوآموزان -->
                    <div class="gk-table-responsive-wrapper">
                        <table class="gk-students-table">
                            <thead>
                                <tr>
                                    <th>نام دانش‌آموز</th>
                                    <th>کلاس و سن</th>
                                    <th>شماره همراه والد</th>
                                    <th style="text-align:center;">عملیات و ارسال</th>
                                </tr>
                            </thead>
                            <tbody id="gk-students-tbody">
                                <?php if (empty($students)): ?>
                                    <tr id="gk-no-students-row">
                                        <td colspan="4" style="text-align:center; padding:30px; color:#94a3b8;">
                                            هنوز دانـش‌آموزی ثبت نشده است.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($students as $st): 
                                        $test_link = home_url('/tests/?st_token=' . $st->student_token);
                                        $game_link = home_url('/games/?st_token=' . $st->student_token);
                                        
                                        $bale_msg = "سلام و احترام، اولیا گرامی نوآموز «" . $st->name . "»\nلینک اختصاصی آزمون‌ها و بازی‌های استعدادیابی در " . $org->name . ":\n" . $test_link;
                                        $bale_url = "https://ble.ir/share/url?url=" . urlencode($test_link) . "&text=" . urlencode($bale_msg);
                                        $phone_display = !empty($st->parent_phone) ? $st->parent_phone : 'ثبت نشده';
                                    ?>
                                        <tr class="gk-student-row" data-class-id="<?php echo $st->class_id; ?>" id="gk-student-row-<?php echo $st->id; ?>">
                                            <td>
                                                <strong style="font-size:14px; color:#1e293b;" class="st-row-name">👶 <?php echo esc_html($st->name); ?></strong>
                                            </td>
                                            <td>
                                                <span style="background:#ede9fe; color:#6c5ce7; padding:3px 8px; border-radius:8px; font-size:11.5px; font-weight:800;" class="st-row-class">
                                                    🏫 <?php echo esc_html($st->class_name); ?>
                                                </span>
                                                <span style="font-size:11.5px; color:#64748b;" class="st-row-age">(<?php echo esc_html($st->age); ?> س)</span>
                                            </td>
                                            <td>
                                                <span class="gk-phone-badge st-row-phone">
                                                    📞 <?php echo esc_html($phone_display); ?>
                                                </span>
                                            </td>
                                            <td style="text-align:center;">
                                                <div class="gk-action-icons-group">
                                                    <a href="<?php echo esc_url($bale_url); ?>" target="_blank" class="gk-icon-btn gk-btn-bale" title="ارسال لینک اختصاصی در پیام‌رسان بله والد">
                                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                                                    </a>
                                                    <button type="button" class="gk-icon-btn gk-btn-copy" title="کپی لینک اختصاصی آزمون و بازی‌ها" onclick="navigator.clipboard.writeText('<?php echo esc_url($test_link); ?>'); alert('لینک اختصاصی <?php echo esc_js($st->name); ?> کپی شد!');">
                                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                                                    </button>
                                                    <button type="button" class="gk-icon-btn gk-btn-edit" title="ویرایش مشخصات نوآموز" onclick="gkOpenEditStudent(<?php echo $st->id; ?>, '<?php echo esc_js($st->name); ?>', <?php echo $st->age; ?>, <?php echo $st->class_id; ?>, '<?php echo esc_js($st->parent_phone ?: ''); ?>');">
                                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                                                    </button>
                                                    <button type="button" class="gk-icon-btn gk-btn-delete" title="حذف نوآموز" onclick="if(confirm('آیا از حذف نوآموز «<?php echo esc_js($st->name); ?>» اطمینان دارید؟')) gkDeleteStudent(<?php echo $st->id; ?>);">
                                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                </div>

            </div>

        </div>

        <script>
        var gkActiveFilter = 'all';

                var gkCurrentModalClassId = null;

        function gkOpenModal(modalId) {
            var m = jQuery(modalId);
            m.css('display', 'flex').hide().fadeIn(200);
        }

        function gkCloseModal(modalId) {
            jQuery(modalId).fadeOut(150);
        }

        function gkOpenClassInfoModal(classId) {
            gkCurrentModalClassId = classId;
            var card = jQuery('#gk-class-row-' + classId);
            if (!card.length) return;

            var name = card.data('name') || 'کلاس';
            var teacherName = card.data('teacher-name') || 'ثبت نشده';
            var teacherPhone = card.data('teacher-phone') || '';
            var baleTeacherUrl = card.data('bale-teacher') || '#';
            var baleBulkUrl = card.data('bale-bulk') || '#';
            var teacherPortalUrl = card.data('teacher-portal') || '#';
            var printUrl = card.data('print-url') || '#';
            var count = card.data('count') || 0;

            jQuery('#gk-m-class-title').text(name);
            jQuery('#gk-m-class-count-tag').text(count + ' نوآموز');
            jQuery('#gk-m-teacher-name').text(teacherName);
            jQuery('#gk-m-teacher-phone').text(teacherPhone ? '(📞 ' + teacherPhone + ')' : '');

            jQuery('#gk-m-bale-teacher-btn').attr('href', baleTeacherUrl);
            jQuery('#gk-m-teacher-portal-btn').attr('href', teacherPortalUrl);
            jQuery('#gk-m-print-btn').attr('href', printUrl);
            jQuery('#gk-m-bale-bulk-btn').attr('href', baleBulkUrl);

            gkOpenModal('#gk-class-info-modal');
            gkFilterClass(classId);
        }

        function gkCopyClassInviteLink() {
            if (!gkCurrentModalClassId) return;
            var card = jQuery('#gk-class-row-' + gkCurrentModalClassId);
            var link = card.data('invite-link') || card.data('teacher-portal') || window.location.href;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(link).then(function() {
                    alert('📋 لینک اختصاصی ورود به کلاس با موفقیت کپی شد:\n' + link);
                });
            } else {
                prompt('لینک ورود به کلاس:', link);
            }
        }

        function gkTriggerEditFromModal() {
            if (!gkCurrentModalClassId) return;
            gkCloseModal('#gk-class-info-modal');
            var card = jQuery('#gk-class-row-' + gkCurrentModalClassId);
            if (card.length) {
                jQuery('#gk-edit-class-id').val(gkCurrentModalClassId);
                jQuery('#gk-edit-class-name').val(card.data('name') || '');
                jQuery('#gk-edit-teacher-name').val(card.data('teacher-name') || '');
                jQuery('#gk-edit-teacher-phone').val(card.data('teacher-phone') || '');
                gkOpenModal('#gk-edit-class-modal');
            }
        }

        function gkTriggerDeleteFromModal() {
            if (!gkCurrentModalClassId) return;
            if (confirm('آیا از حذف این کلاس و تمام نوآموزان آن اطمینان دارید؟')) {
                gkDeleteClass(gkCurrentModalClassId);
                gkCloseModal('#gk-class-info-modal');
            }
        }

        function gkApplyLiveFilter() {
            var query = jQuery('#gk-live-search-input').val().toLowerCase().trim();
            var selectedClass = jQuery('#gk-class-select-filter').val();
            var visibleCount = 0;

            if (query !== '' || selectedClass !== 'all') {
                jQuery('#gk-clear-filter-btn').css('display', 'inline-flex');
            } else {
                jQuery('#gk-clear-filter-btn').hide();
            }

            jQuery('.gk-student-row').each(function() {
                var row = jQuery(this);
                var rowClassId = String(row.data('class-id'));
                var rowName = (row.find('.st-row-name').text() || '').toLowerCase();
                var rowPhone = (row.find('.st-row-phone').text() || '').toLowerCase();
                var rowClassName = (row.find('.st-row-class').text() || '').toLowerCase();

                var matchesClass = (selectedClass === 'all' || rowClassId === String(selectedClass));
                var matchesQuery = (query === '' || rowName.indexOf(query) !== -1 || rowPhone.indexOf(query) !== -1 || rowClassName.indexOf(query) !== -1);

                if (matchesClass && matchesQuery) {
                    row.show();
                    visibleCount++;
                } else {
                    row.hide();
                }
            });

            jQuery('#gk-visible-count').text(visibleCount);
        }

        function gkResetSearchAndFilters() {
            jQuery('#gk-live-search-input').val('');
            jQuery('#gk-class-select-filter').val('all');
            jQuery('#gk-clear-filter-btn').hide();
            jQuery('.gk-class-card-item').removeClass('active-class');
            jQuery('.gk-student-row').show();
            jQuery('#gk-visible-count').text(jQuery('.gk-student-row').length);
        }

        function gkFilterClass(classId) {
            gkActiveFilter = classId;
            jQuery('#gk-class-select-filter').val(classId);
            jQuery('.gk-class-card-item').removeClass('active-class');
            if (classId !== 'all') {
                jQuery('#gk-class-row-' + classId).addClass('active-class');
                jQuery('#gk-student-class').val(classId);
            }
            gkApplyLiveFilter();
        }

        function gkOpenEditStudent(id, name, age, classId, phone) {
            jQuery('#gk-edit-st-id').val(id);
            jQuery('#gk-edit-st-name').val(name);
            jQuery('#gk-edit-st-age').val(age);
            jQuery('#gk-edit-st-class').val(classId);
            jQuery('#gk-edit-st-phone').val(phone);
            gkOpenModal('#gk-edit-student-modal');
        }

        function gkOpenEditClass(id, name, teacherName, teacherPhone) {
            jQuery('#gk-edit-class-id').val(id);
            jQuery('#gk-edit-class-name').val(name);
            jQuery('#gk-edit-teacher-name').val(teacherName);
            jQuery('#gk-edit-teacher-phone').val(teacherPhone);
            gkOpenModal('#gk-edit-class-modal');
        }

        jQuery(document).ready(function($) {
            // فیلتر زنده با تایپ در ورودی جستجو
            $(document).on('input', '#gk-live-search-input', function() {
                gkApplyLiveFilter();
            });

            // جلوگیری از ارسال فرم با کلید اینتر
            $('#gk-student-name, #gk-student-phone').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    $('#gk-btn-add-student').trigger('click');
                }
            });
            $('#gk-new-class-name, #gk-new-teacher-name, #gk-new-teacher-phone').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    $('#gk-btn-add-class').trigger('click');
                }
            });

            // ۱. ثبت دانش‌آموز جدید در مودال بدون رفرش (Zero-Reload)
            $('#gk-btn-add-student').on('click', function(e) {
                e.preventDefault();
                var nameInput = $('#gk-student-name');
                var name = nameInput.val().trim();
                var age = $('#gk-student-age').val();
                var class_id = $('#gk-student-class').val();
                var phone = $('#gk-student-phone').val().trim();

                if (!name) { alert('لطفاً نام و نام خانوادگی کودک را وارد کنید.'); nameInput.focus(); return; }
                var btn = $(this).text('در حال ثبت...').prop('disabled', true);

                $.post('<?php echo $ajax_url; ?>', {
                    action: 'gk_school_add_student',
                    nonce: '<?php echo $nonce; ?>',
                    org_id: <?php echo $org->id; ?>,
                    class_id: class_id,
                    name: name,
                    age: age,
                    parent_phone: phone
                }, function(res) {
                    btn.text('🎯 ثبت و افزودن نوآموز').prop('disabled', false);
                    if (res.success && res.data) {
                        var d = res.data;
                        $('#gk-new-student-modal').fadeOut(200);
                        nameInput.val('');
                        $('#gk-student-phone').val('');
                        $('#gk-no-students-row').remove();

                        var testLink = '<?php echo home_url('/tests/?st_token='); ?>' + d.token;
                        var baleMsg = "سلام و احترام، اولیا گرامی نوآموز «" + d.name + "»\nلینک اختصاصی آزمون‌ها و بازی‌های استعدادیابی در <?php echo esc_js($org->name); ?>:\n" + testLink;
                        var baleUrl = "https://ble.ir/share/url?url=" + encodeURIComponent(testLink) + "&text=" + encodeURIComponent(baleMsg);

                        var newRowHtml = `
                            <tr class="gk-student-row" data-class-id="${d.class_id}" id="gk-student-row-${d.id}" style="background-color:#bbf7d0;">
                                <td>
                                    <strong style="font-size:14px; color:#1e293b;" class="st-row-name">👶 ${d.name}</strong>
                                </td>
                                <td>
                                    <span style="background:#ede9fe; color:#6c5ce7; padding:3px 8px; border-radius:8px; font-size:11.5px; font-weight:800;" class="st-row-class">
                                        🏫 ${d.class_name}
                                    </span>
                                    <span style="font-size:11.5px; color:#64748b;" class="st-row-age">(${d.age} س)</span>
                                </td>
                                <td>
                                    <span class="gk-phone-badge st-row-phone">
                                        📞 ${d.parent_phone || 'ثبت نشده'}
                                    </span>
                                </td>
                                <td style="text-align:center;">
                                    <div class="gk-action-icons-group">
                                        <a href="${baleUrl}" target="_blank" class="gk-icon-btn gk-btn-bale" title="ارسال لینک اختصاصی در پیام‌رسان بله والد">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                                        </a>
                                        <button type="button" class="gk-icon-btn gk-btn-copy" title="کپی لینک اختصاصی آزمون و بازی‌ها" onclick="navigator.clipboard.writeText('${testLink}'); alert('لینک اختصاصی ${d.name} کپی شد!');">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                                        </button>
                                        <button type="button" class="gk-icon-btn gk-btn-edit" title="ویرایش مشخصات نوآموز" onclick="gkOpenEditStudent(${d.id}, '${d.name.replace(/'/g, "\\'")}', ${d.age}, ${d.class_id}, '${(d.parent_phone || '').replace(/'/g, "\\'")}');">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                                        </button>
                                        <button type="button" class="gk-icon-btn gk-btn-delete" title="حذف نوآموز" onclick="if(confirm('آیا از حذف نوآموز «${d.name.replace(/'/g, "\\'")}» اطمینان دارید؟')) gkDeleteStudent(${d.id});">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `;
                        $('#gk-students-tbody').prepend(newRowHtml);
                        setTimeout(function() {
                            $('#gk-student-row-' + d.id).css('background-color', '');
                        }, 1200);

                        // بروزرسانی شمارنده‌ها
                        var totalSt = parseInt($('#gk-stat-students-count').text() || 0) + 1;
                        $('#gk-stat-students-count').text(totalSt);

                        // بروزرسانی شمارنده کارت کلاس
                        var classCountBadge = $('#gk-class-count-badge-' + d.class_id);
                        if (classCountBadge.length) {
                            var currentC = parseInt(classCountBadge.text()) || 0;
                            classCountBadge.text((currentC + 1) + ' نوآموز');
                        }

                        // اعمال فیلتر زنده فعلی
                        gkApplyLiveFilter();
                    } else {
                        alert(res.data || 'خطا در ثبت کودک.');
                    }
                }).fail(function() {
                    btn.text('🎯 ثبت و افزودن نوآموز').prop('disabled', false);
                    alert('خطای ارتباط با سرور.');
                });
            });

            // ۲. ویرایش نوآموز بدون رفرش
            $('#gk-btn-save-edit-st').on('click', function(e) {
                e.preventDefault();
                var stId = $('#gk-edit-st-id').val();
                var name = $('#gk-edit-st-name').val().trim();
                var age = $('#gk-edit-st-age').val();
                var classId = $('#gk-edit-st-class').val();
                var phone = $('#gk-edit-st-phone').val().trim();

                if (!name) { alert('نام نوآموز نمی‌تواند خالی باشد.'); return; }
                var btn = $(this).text('در حال ذخیره...').prop('disabled', true);

                $.post('<?php echo $ajax_url; ?>', {
                    action: 'gk_school_edit_student',
                    nonce: '<?php echo $nonce; ?>',
                    student_id: stId,
                    name: name,
                    age: age,
                    class_id: classId,
                    parent_phone: phone
                }, function(res) {
                    btn.text('💾 ذخیره تغییرات').prop('disabled', false);
                    if (res.success && res.data) {
                        $('#gk-edit-student-modal').fadeOut(200);
                        var data = res.data;
                        var row = $('#gk-student-row-' + data.id);
                        if (row.length) {
                            row.attr('data-class-id', data.class_id);
                            row.find('.st-row-name').text('👶 ' + data.name);
                            row.find('.st-row-class').text('🏫 ' + data.class_name);
                            row.find('.st-row-age').text('(' + data.age + ' س)');
                            row.find('.st-row-phone').text('📞 ' + (data.parent_phone ? data.parent_phone : 'ثبت نشده'));

                            var testLink = '<?php echo home_url('/tests/?st_token='); ?>' + data.student_token;
                            var baleMsg = "سلام و احترام، اولیا گرامی نوآموز «" + data.name + "»\nلینک اختصاصی آزمون‌ها و بازی‌های استعدادیابی در " + data.org_name + ":\n" + testLink;
                            var baleUrl = "https://ble.ir/share/url?url=" + encodeURIComponent(testLink) + "&text=" + encodeURIComponent(baleMsg);
                            row.find('.gk-btn-bale').attr('href', baleUrl);

                            row.find('.gk-btn-edit').attr('onclick', "gkOpenEditStudent(" + data.id + ", '" + data.name.replace(/'/g, "\\'") + "', " + data.age + ", " + data.class_id + ", '" + (data.parent_phone || '').replace(/'/g, "\\'") + "');");

                            // بررسی انطباق با فیلتر فعال
                            if (gkActiveFilter !== 'all' && gkActiveFilter != data.class_id) {
                                row.fadeOut(200);
                            } else {
                                row.show().css({'background-color': '#bbf7d0'});
                                setTimeout(function() { row.css('background-color', '#f8fafc'); }, 1000);
                            }
                            $('#gk-visible-count').text($('.gk-student-row:visible').length);
                        }
                    } else {
                        alert(res.data || 'خطا در ویرایش اطلاعات.');
                    }
                }).fail(function() {
                    btn.text('💾 ذخیره تغییرات').prop('disabled', false);
                    alert('خطای ارتباط با سرور.');
                });
            });

            // ۳. ایجاد کلاس جدید بدون رفرش
            $('#gk-btn-add-class').on('click', function(e) {
                e.preventDefault();
                var inputName = $('#gk-new-class-name');
                var name = inputName.val().trim();
                var teacherName = $('#gk-new-teacher-name').val().trim();
                var teacherPhone = $('#gk-new-teacher-phone').val().trim();

                if (!name) {
                    alert('لطفاً نام کلاس را وارد کنید.');
                    inputName.focus();
                    return;
                }

                var btn = $(this).text('در حال ایجاد...').prop('disabled', true);

                $.post('<?php echo $ajax_url; ?>', {
                    action: 'gk_school_create_class',
                    nonce: '<?php echo $nonce; ?>',
                    org_id: <?php echo $org->id; ?>,
                    name: name,
                    teacher_name: teacherName,
                    teacher_phone: teacherPhone
                }, function(res) {
                    btn.text('🚀 ایجاد کلاس و ثبت مربی').prop('disabled', false);
                    if (res.success && res.data) {
                        var d = res.data;
                        inputName.val('');
                        $('#gk-new-teacher-name').val('');
                        $('#gk-new-teacher-phone').val('');
                        $('#gk-no-classes-msg').remove();
                        $('#gk-new-class-modal').fadeOut(200);

                        var newTileHtml = '<div class="gk-class-card-item" id="gk-class-row-' + d.id + '" ' +
                            'data-class-id="' + d.id + '" ' +
                            'data-name="' + d.name + '" ' +
                            'data-teacher-name="' + (d.teacher_name || '') + '" ' +
                            'data-teacher-phone="' + (d.teacher_phone || '') + '" ' +
                            'data-bale-teacher="' + (d.bale_teacher_url || '#') + '" ' +
                            'data-bale-bulk="' + (d.bale_bulk_url || '#') + '" ' +
                            'data-teacher-portal="' + (d.teacher_portal_url || d.invite_link || '#') + '" ' +
                            'data-print-url="' + (d.print_url || '#') + '" ' +
                            'data-invite-link="' + (d.invite_link || '#') + '" ' +
                            'data-count="0" ' +
                            'onclick="gkOpenClassInfoModal(' + d.id + ');" style="display:none; background:#ecfdf5; border-color:#10b981;">' +
                            '<div class="gk-class-top-row">' +
                                '<div class="gk-class-title">🏫 ' + d.name + '</div>' +
                                '<span class="gk-class-badge" id="gk-class-count-badge-' + d.id + '">0 نوآموز</span>' +
                            '</div>' +
                            '<div class="gk-class-bottom-row">' +
                                '<span class="gk-class-teacher-snippet">👩‍🏫 ' + (d.teacher_name || 'ثبت نشده') + '</span>' +
                                '<span class="gk-class-info-icon" title="مدیریت و جزئیات کلاس">⚙️</span>' +
                            '</div>' +
                        '</div>';

                        $('#gk-classes-list').prepend(newTileHtml);
                        $('#gk-class-row-' + d.id).fadeIn(300, function() {
                            setTimeout(function(){
                                $('#gk-class-row-' + d.id).css({ 'background': '#ffffff', 'border-color': '#e2e8f0' });
                            }, 1500);
                        });

                        // بروزرسانی سلکتورهای فرم
                        $('#gk-student-class').append('<option value="' + d.id + '">' + d.name + '</option>').val(d.id);
                        $('#gk-edit-st-class').append('<option value="' + d.id + '">' + d.name + '</option>');
                        $('#gk-class-select-filter').append('<option value="' + d.id + '" id="gk-filter-opt-' + d.id + '">🏫 ' + d.name + ' (0)</option>');

                        var curCount = parseInt($('#gk-stat-classes-count').text()) || 0;
                        $('#gk-stat-classes-count').text(curCount + 1);

                        // انتخاب خودکار کلاس جدید
                        gkFilterClass(d.id);
                    } else {
                        alert(res.data || 'خطایی در ثبت کلاس رخ داد.');
                    }
                }).fail(function() {
                    btn.text('🚀 ایجاد کلاس و ثبت مربی').prop('disabled', false);
                    alert('خطای ارتباط با سرور.');
                });
            });

            $('#gk-btn-save-branding').on('click', function(e) {
                e.preventDefault();
                var btn = $(this).text('در حال ذخیره...').prop('disabled', true);
                var orgName = $('#gk-org-name').val();
                var city = $('#gk-org-city').val();
                var phone = $('#gk-org-phone').val();
                var logoUrl = $('#gk-org-logo').val();

                $.post('<?php echo $ajax_url; ?>', {
                    action: 'gk_school_save_branding',
                    nonce: '<?php echo $nonce; ?>',
                    org_id: <?php echo $org->id; ?>,
                    name: orgName,
                    city: city,
                    phone: phone,
                    logo_url: logoUrl
                }, function(res) {
                    btn.text('💾 ذخیره تغییرات مشخصات مرکز').prop('disabled', false);
                    if (res.success) {
                        $('#gk-branding-modal').slideUp(200);
                        $('#gk-header-org-name').text(orgName);
                        $('#gk-header-city').text(city || 'ایران');
                        $('#gk-header-phone').text(phone || 'ثبت نشده');
                        if (logoUrl) {
                            $('#gk-header-logo-container').html('<img src="' + logoUrl + '" alt="' + orgName + '" style="width:100%; height:100%; object-fit:cover;">');
                        }
                        alert('مشخصات و برند مرکز با موفقیت ذخیره شد! ✨');
                    } else {
                        alert(res.data || 'خطایی رخ داد.');
                    }
                }).fail(function() {
                    btn.text('💾 ذخیره تغییرات مشخصات مرکز').prop('disabled', false);
                    alert('خطای ارتباط با سرور.');
                });
            });
        });

        // ۶. حذف کلاس بدون رفرش
        function gkDeleteClass(classId) {
            jQuery.post('<?php echo $ajax_url; ?>', {
                action: 'gk_school_delete_class',
                nonce: '<?php echo $nonce; ?>',
                class_id: classId
            }, function(res) {
                if (res.success) {
                    jQuery('#gk-class-row-' + classId).fadeOut(200, function() { jQuery(this).remove(); });
                    jQuery('#gk-filter-btn-' + classId).remove();
                    jQuery('#gk-student-class option[value="' + classId + '"], #gk-edit-st-class option[value="' + classId + '"]').remove();
                    jQuery('.gk-student-row[data-class-id="' + classId + '"]').fadeOut(200, function() { jQuery(this).remove(); });
                    var totalC = Math.max(0, parseInt(jQuery('#gk-stat-classes-count').text() || 0) - 1);
                    jQuery('#gk-stat-classes-count').text(totalC);
                } else {
                    alert(res.data || 'خطا در حذف کلاس.');
                }
            });
        }

        // ۷. حذف نوآموز بدون رفرش
        function gkDeleteStudent(studentId) {
            jQuery.post('<?php echo $ajax_url; ?>', {
                action: 'gk_school_delete_student',
                nonce: '<?php echo $nonce; ?>',
                student_id: studentId
            }, function(res) {
                if (res.success) {
                    var row = jQuery('#gk-student-row-' + studentId);
                    var classId = row.data('class-id');
                    row.fadeOut(200, function() { 
                        jQuery(this).remove(); 
                        jQuery('#gk-visible-count').text(jQuery('.gk-student-row:visible').length);
                    });

                    var totalSt = Math.max(0, parseInt(jQuery('#gk-stat-students-count').text() || 0) - 1);
                    jQuery('#gk-stat-students-count').text(totalSt);
                    jQuery('#gk-filter-total-count').text(totalSt);

                    var classCountBadge = jQuery('#gk-class-count-badge-' + classId);
                    if (classCountBadge.length) {
                        var curC = Math.max(0, parseInt(classCountBadge.text()) - 1);
                        classCountBadge.text(curC + ' نوآموز');
                    }
                    var filterTabCount = jQuery('#gk-filter-btn-' + classId + ' .filter-btn-count');
                    if (filterTabCount.length) {
                        var curFC = Math.max(0, parseInt(filterTabCount.text()) - 1);
                        filterTabCount.text(curFC);
                    }
                } else {
                    alert(res.data || 'خطا در حذف.');
                }
            });
        }
        </script>
        <?php
        return ob_get_clean();
    }

    public static function ajax_save_branding() {
        check_ajax_referer('gk_school_nonce', 'nonce');
        $org_id = intval($_POST['org_id']);
        $user_id = get_current_user_id();

        global $wpdb;
        $table_orgs = $wpdb->prefix . 'gk_organizations';
        $org = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_orgs WHERE id = %d AND (manager_user_id = %d OR %d = 1)", $org_id, $user_id, current_user_can('manage_options') ? 1 : 0));

        if (!$org) wp_send_json_error('دسترسی غیرمجاز.');

        $wpdb->update($table_orgs, [
            'name'     => sanitize_text_field($_POST['name']),
            'city'     => sanitize_text_field($_POST['city']),
            'phone'    => sanitize_text_field($_POST['phone']),
            'logo_url' => esc_url_raw($_POST['logo_url'])
        ], ['id' => $org_id], ['%s', '%s', '%s', '%s'], ['%d']);

        wp_send_json_success('بروزرسانی شد.');
    }

    public static function ajax_create_class() {
        check_ajax_referer('gk_school_nonce', 'nonce');
        $org_id = intval($_POST['org_id']);
        $name = sanitize_text_field($_POST['name']);
        $teacher_name = sanitize_text_field($_POST['teacher_name'] ?? '');
        $teacher_phone = sanitize_text_field($_POST['teacher_phone'] ?? '');
        $user_id = get_current_user_id();

        global $wpdb;
        $table_orgs = $wpdb->prefix . 'gk_organizations';
        $org = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_orgs WHERE id = %d AND (manager_user_id = %d OR %d = 1)", $org_id, $user_id, current_user_can('manage_options') ? 1 : 0));

        if (!$org) wp_send_json_error('دسترسی غیرمجاز.');

        $table_classes = $wpdb->prefix . 'gk_classes';
        $invite_code = 'CLS-' . strtoupper(wp_generate_password(8, false));

        $wpdb->insert($table_classes, [
            'org_id'        => $org_id,
            'name'          => $name,
            'teacher_name'  => $teacher_name,
            'teacher_phone' => $teacher_phone,
            'invite_code'   => $invite_code
        ], ['%d', '%s', '%s', '%s', '%s']);

        $class_id = $wpdb->insert_id;
        $invite_link = home_url('/school/join/?code=' . $invite_code);
        $teacher_msg = "سلام و احترام، همکار گرامی " . ($teacher_name ?: '') . "\nلینک ورود اختصاصی به پنل کلاس «" . $name . "» در سامانه قربانی کیدز:\n" . $invite_link;
        $bale_teacher_url = "https://ble.ir/share/url?url=" . urlencode($invite_link) . "&text=" . urlencode($teacher_msg);
        $bulk_msg = "📋 لیست لینک‌های اختصاصی آزمون‌ها و بازی‌های استعدادیابی کلاس «" . $name . "» (" . $org->name . "):\n\n";
        $bale_bulk_url = "https://ble.ir/share/url?url=" . urlencode(home_url()) . "&text=" . urlencode($bulk_msg);
        $print_url = home_url('/?gk_action=print_class_cards&class_id=' . $class_id . '&v=5');

        wp_send_json_success([
            'id'               => $class_id,
            'name'             => $name,
            'teacher_name'     => $teacher_name,
            'teacher_phone'    => $teacher_phone,
            'invite_code'      => $invite_code,
            'print_url'        => $print_url,
            'bale_teacher_url' => $bale_teacher_url,
            'bale_bulk_url'    => $bale_bulk_url
        ]);
    }

    public static function ajax_edit_class() {
        check_ajax_referer('gk_school_nonce', 'nonce');
        $class_id = intval($_POST['class_id']);
        $name = sanitize_text_field($_POST['name']);
        $teacher_name = sanitize_text_field($_POST['teacher_name'] ?? '');
        $teacher_phone = sanitize_text_field($_POST['teacher_phone'] ?? '');
        $user_id = get_current_user_id();

        global $wpdb;
        $table_classes = $wpdb->prefix . 'gk_classes';
        $table_orgs = $wpdb->prefix . 'gk_organizations';
        $table_students = $wpdb->prefix . 'gk_students';

        $class = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_classes WHERE id = %d", $class_id));
        if (!$class) wp_send_json_error('کلاس یافت نشد.');

        $org = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_orgs WHERE id = %d AND (manager_user_id = %d OR %d = 1)", $class->org_id, $user_id, current_user_can('manage_options') ? 1 : 0));
        if (!$org) wp_send_json_error('دسترسی غیرمجاز.');

        $wpdb->update($table_classes, [
            'name'          => $name,
            'teacher_name'  => $teacher_name,
            'teacher_phone' => $teacher_phone
        ], ['id' => $class_id], ['%s', '%s', '%s'], ['%d']);

        $invite_link = home_url('/school/join/?code=' . $class->invite_code);
        $teacher_msg = "سلام و احترام، همکار گرامی " . ($teacher_name ?: '') . "\nلینک ورود اختصاصی به پنل کلاس «" . $name . "» در سامانه قربانی کیدز:\n" . $invite_link;
        $bale_teacher_url = "https://ble.ir/share/url?url=" . urlencode($invite_link) . "&text=" . urlencode($teacher_msg);

        $class_students = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_students WHERE class_id = %d", $class_id));
        $bulk_msg = "📋 لیست لینک‌های اختصاصی آزمون‌ها و بازی‌های استعدادیابی کلاس «" . $name . "» (" . $org->name . "):\n\n";
        foreach ($class_students as $idx => $cs) {
            $st_test_url = home_url('/tests/?st_token=' . $cs->student_token);
            $bulk_msg .= ($idx + 1) . ". 👶 " . $cs->name . ": " . $st_test_url . "\n";
        }
        $bale_bulk_url = "https://ble.ir/share/url?url=" . urlencode(home_url()) . "&text=" . urlencode($bulk_msg);

        wp_send_json_success([
            'id'               => $class_id,
            'name'             => $name,
            'teacher_name'     => $teacher_name,
            'teacher_phone'    => $teacher_phone,
            'bale_teacher_url' => $bale_teacher_url,
            'bale_bulk_url'    => $bale_bulk_url
        ]);
    }

    public static function ajax_delete_class() {
        check_ajax_referer('gk_school_nonce', 'nonce');
        $class_id = intval($_POST['class_id']);
        $user_id = get_current_user_id();

        global $wpdb;
        $table_classes = $wpdb->prefix . 'gk_classes';
        $table_orgs = $wpdb->prefix . 'gk_organizations';
        $table_students = $wpdb->prefix . 'gk_students';

        $class = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_classes WHERE id = %d", $class_id));
        if (!$class) wp_send_json_error('کلاس یافت نشد.');

        $org = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_orgs WHERE id = %d AND (manager_user_id = %d OR %d = 1)", $class->org_id, $user_id, current_user_can('manage_options') ? 1 : 0));
        if (!$org) wp_send_json_error('دسترسی غیرمجاز.');

        $wpdb->delete($table_students, ['class_id' => $class_id], ['%d']);
        $wpdb->delete($table_classes, ['id' => $class_id], ['%d']);

        wp_send_json_success('کلاس با موفقیت حذف شد.');
    }

    public static function ajax_add_student() {
        check_ajax_referer('gk_school_nonce', 'nonce');
        $org_id = intval($_POST['org_id']);
        $class_id = intval($_POST['class_id']);
        $name = sanitize_text_field($_POST['name']);
        $age = intval($_POST['age']);
        $parent_phone = sanitize_text_field($_POST['parent_phone'] ?? '');
        $user_id = get_current_user_id();

        global $wpdb;
        $table_orgs = $wpdb->prefix . 'gk_organizations';
        $table_students = $wpdb->prefix . 'gk_students';
        $table_classes = $wpdb->prefix . 'gk_classes';

        $org = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_orgs WHERE id = %d AND (manager_user_id = %d OR %d = 1)", $org_id, $user_id, current_user_can('manage_options') ? 1 : 0));

        if (!$org) wp_send_json_error('دسترسی غیرمجاز.');

        $current_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_students WHERE org_id = %d", $org_id));
        if ($current_count >= intval($org->student_limit)) {
            wp_send_json_error('سقف مجاز تعداد نوآموزان تکمیل شده است.');
        }

        $token = 'ST-' . strtoupper(wp_generate_password(12, false));

        $wpdb->insert($table_students, [
            'org_id'        => $org_id,
            'class_id'      => $class_id,
            'name'          => $name,
            'age'           => $age,
            'parent_phone'  => $parent_phone,
            'student_token' => $token
        ], ['%d', '%d', '%s', '%d', '%s', '%s']);

        $student_id = $wpdb->insert_id;
        $class_name = $wpdb->get_var($wpdb->prepare("SELECT name FROM $table_classes WHERE id = %d", $class_id));

        wp_send_json_success([
            'id'           => $student_id,
            'name'         => $name,
            'age'          => $age,
            'class_id'     => $class_id,
            'class_name'   => $class_name ?: 'کلاس',
            'parent_phone' => $parent_phone,
            'token'        => $token
        ]);
    }

    public static function ajax_edit_student() {
        check_ajax_referer('gk_school_nonce', 'nonce');
        $student_id = intval($_POST['student_id']);
        $name = sanitize_text_field($_POST['name']);
        $age = intval($_POST['age']);
        $class_id = intval($_POST['class_id']);
        $parent_phone = sanitize_text_field($_POST['parent_phone'] ?? '');
        $user_id = get_current_user_id();

        global $wpdb;
        $table_students = $wpdb->prefix . 'gk_students';
        $table_orgs = $wpdb->prefix . 'gk_organizations';

        $st = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_students WHERE id = %d", $student_id));
        if (!$st) wp_send_json_error('دانش‌آموز یافت نشد.');

        $org = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_orgs WHERE id = %d AND (manager_user_id = %d OR %d = 1)", $st->org_id, $user_id, current_user_can('manage_options') ? 1 : 0));
        if (!$org) wp_send_json_error('دسترسی غیرمجاز.');

        $wpdb->update($table_students, [
            'name'         => $name,
            'age'          => $age,
            'class_id'     => $class_id,
            'parent_phone' => $parent_phone
        ], ['id' => $student_id], ['%s', '%d', '%d', '%s'], ['%d']);

        $class_name = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}gk_classes WHERE id = %d", $class_id));

        wp_send_json_success([
            'message'      => 'ویرایش با موفقیت ذخیره شد.',
            'id'           => $student_id,
            'name'         => $name,
            'age'          => $age,
            'class_id'     => $class_id,
            'class_name'   => $class_name ?: 'کلاس',
            'parent_phone' => $parent_phone,
            'student_token'=> $st->student_token,
            'org_name'     => $org->name
        ]);
    }

    public static function ajax_delete_student() {
        check_ajax_referer('gk_school_nonce', 'nonce');
        $student_id = intval($_POST['student_id']);
        $user_id = get_current_user_id();

        global $wpdb;
        $table_students = $wpdb->prefix . 'gk_students';
        $table_orgs = $wpdb->prefix . 'gk_organizations';

        $st = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_students WHERE id = %d", $student_id));
        if (!$st) wp_send_json_error('یافت نشد.');

        $org = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_orgs WHERE id = %d AND (manager_user_id = %d OR %d = 1)", $st->org_id, $user_id, current_user_can('manage_options') ? 1 : 0));
        if (!$org) wp_send_json_error('دسترسی غیرمجاز.');

        $wpdb->delete($table_students, ['id' => $student_id], ['%d']);
        wp_send_json_success('حذف شد.');
    }
}