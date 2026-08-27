<?php
/**
 * Class GK_Teacher_Portal
 * Modern Tabbed Dashboard for Teachers: Students List, Tournament Arena, Expiration Timers, Podium Leaderboard & Bale Sharing
 */
if (!defined('ABSPATH')) exit;

class GK_Teacher_Portal {

    public static function init() {
        add_shortcode('gk_teacher_portal', [__CLASS__, 'render_teacher_portal']);
        add_action('template_redirect', [__CLASS__, 'disable_caching_on_teacher']);
        add_action('template_redirect', [__CLASS__, 'handle_join_redirect']);
    }

    public static function disable_caching_on_teacher() {
        if (is_page('teacher-class')) {
            nocache_headers();
            if (!defined('DONOTCACHEPAGE')) define('DONOTCACHEPAGE', true);
            if (function_exists('litespeed_control_set_nocache')) {
                do_action('litespeed_control_set_nocache', 'dynamic teacher page');
            }
        }
    }

    public static function handle_join_redirect() {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($uri, '/school/join') !== false && isset($_GET['code'])) {
            $code = sanitize_text_field($_GET['code']);
            wp_redirect(add_query_arg(['code' => $code], home_url('/teacher-class/')));
            exit;
        }
    }

    public static function render_teacher_portal() {
        $code = sanitize_text_field($_GET['code'] ?? ($_GET['class_code'] ?? ''));
        if (empty($code)) {
            return '<div class="gk-alert-box" style="text-align:center; max-width:600px; margin:40px auto; padding:40px 20px; background:#fffbeb; border:2px solid #fde68a; border-radius:24px; direction:rtl;">
                <div style="font-size:48px; margin-bottom:12px;">🏫</div>
                <h2 style="margin:0 0 8px 0; color:#92400e;">لینک ورود به کلاس مشخص نشده است!</h2>
                <p style="color:#b45309; margin:0 0 20px 0;">لطفاً از طریق لینک دعوت اختصاصی که توسط مدیر مهدکودک ارسال شده وارد شوید.</p>
                <a href="' . esc_url(home_url('/school-panel/')) . '" style="display:inline-block; background:#6c5ce7; color:#fff; font-weight:900; padding:12px 24px; border-radius:14px; text-decoration:none;">ورود به پنل مدیریت مهد 🏢</a>
            </div>';
        }

        global $wpdb;
        $table_classes  = $wpdb->prefix . 'gk_classes';
        $table_orgs     = $wpdb->prefix . 'gk_organizations';
        $table_students = $wpdb->prefix . 'gk_students';
        $table_leagues  = $wpdb->prefix . 'gk_leagues';
        $table_scores   = $wpdb->prefix . 'gk_league_scores';

        $class = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_classes WHERE invite_code = %s", $code));
        if (!$class) {
            return '<div class="gk-alert-box" style="text-align:center; padding:30px; background:#fef2f2; border:2px solid #fecaca; border-radius:18px; color:#b91c1c; direction:rtl;">کد دعوت کلاس یافت نشد یا نامعتبر است.</div>';
        }

        $org = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_orgs WHERE id = %d", $class->org_id));
        $students = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_students WHERE class_id = %d ORDER BY total_game_score DESC, id ASC", $class->id));
        $leagues = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_leagues WHERE class_id = %d ORDER BY id DESC", $class->id));
        $table_class_exams = $wpdb->prefix . 'gk_class_exams';
        $class_exams = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_class_exams WHERE class_id = %d ORDER BY id DESC", $class->id));
        $total_exams = count($class_exams);
        $all_curriculum_tests = class_exists('GK_Curriculum_Tests') ? GK_Curriculum_Tests::get_tests_data() : [];

        $all_games = get_posts([
            'post_type'   => 'gk_game',
            'numberposts' => 50,
            'post_status' => 'publish'
        ]);

        $total_st = count($students);
        $total_leagues = count($leagues);
        $org_name = $org ? $org->name : 'مرکز آموزشی';
        $print_qr_url = home_url('/?gk_action=print_class_cards&class_id=' . $class->id . '&v=5');
        $nonce = wp_create_nonce('gk_school_nonce');
        $ajax_url = admin_url('admin-ajax.php');

        $bulk_msg = "📋 لیست لینک‌های اختصاصی آزمون‌ها و بازی‌های استعدادیابی کلاس «" . $class->name . "» (" . $org_name . "):\n\n";
        foreach ($students as $idx => $cs) {
            $st_game_url = home_url('/games/?st_token=' . $cs->student_token);
            $bulk_msg .= ($idx + 1) . ". 👶 " . $cs->name . ": " . $st_game_url . "\n";
        }
        $bale_bulk_url = "https://ble.ir/share/url?url=" . urlencode(home_url()) . "&text=" . urlencode($bulk_msg);

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

            .gk-teacher-wrap {
                direction: rtl !important;
                text-align: right !important;
                font-family: inherit;
                max-width: 1240px;
                margin: 20px auto 60px auto;
                padding: 0 15px;
                box-sizing: border-box;
            }
            .gk-teacher-header {
                background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%);
                color: #ffffff;
                border-radius: 24px;
                padding: 22px 28px;
                margin-bottom: 22px;
                box-shadow: 0 12px 30px rgba(49, 46, 129, 0.22);
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
                gap: 16px;
                border: 1.5px solid rgba(255,255,255,0.25);
            }
            .gk-teacher-title {
                font-size: 20px !important;
                font-weight: 900 !important;
                color: #ffffff !important;
                margin: 0 0 5px 0 !important;
            }
            .gk-teacher-meta {
                font-size: 13px;
                color: rgba(255, 255, 255, 0.88);
                display: flex;
                flex-wrap: wrap;
                gap: 12px;
                align-items: center;
            }

            .gk-tabs-nav-bar {
                display: flex;
                gap: 6px;
                margin-bottom: 24px;
                border-bottom: 3px solid #e2e8f0;
                padding-bottom: 0;
                flex-wrap: wrap;
                position: relative;
            }
            .gk-tab-btn {
                background: #f1f5f9;
                color: #64748b;
                border: 2px solid #e2e8f0;
                border-bottom: none;
                padding: 12px 20px;
                border-radius: 16px 16px 0 0;
                font-size: 13.5px;
                font-weight: 800;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                transition: all 0.2s ease;
                position: relative;
                bottom: -3px;
                font-family: inherit;
            }
            .gk-tab-btn:hover {
                background: #e2e8f0;
                color: #334155;
            }
            .gk-tab-btn.active {
                background: #ffffff !important;
                color: #4338ca !important;
                border: 3px solid #e2e8f0;
                border-top: 4px solid #4f46e5;
                border-bottom: 4px solid #ffffff !important;
                font-weight: 900;
                z-index: 5;
                box-shadow: 0 -6px 16px rgba(15, 23, 42, 0.06);
            }
            .gk-tab-badge {
                background: #e2e8f0;
                color: #475569;
                padding: 2px 9px;
                border-radius: 20px;
                font-size: 11.5px;
                font-weight: 900;
            }
            .gk-tab-btn.active .gk-tab-badge {
                background: #e0e7ff;
                color: #4338ca;
            }
            .gk-tab-lbl-short { display: none; }
            .gk-tab-lbl-full { display: inline; }
            .gk-tab-pane {
                display: none;
                animation: fadeIn 0.25s ease;
            }
            .gk-tab-pane.active {
                display: block;
            }
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(4px); }
                to { opacity: 1; transform: translateY(0); }
            }

            /* SweetAlert2 GhorbaniKids Luxury Theme */
            .gk-swal-popup {
                font-family: 'IRANSansXFaNum', 'IRANSansX', 'IRANSans', -apple-system, BlinkMacSystemFont, sans-serif !important;
                border-radius: 22px !important;
                padding: 24px 20px !important;
                box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25) !important;
                direction: rtl !important;
                text-align: right !important;
                background: #ffffff !important;
                border: 2px solid #e2e8f0 !important;
            }
            .gk-swal-title {
                font-family: 'IRANSansXFaNum', 'IRANSansX', sans-serif !important;
                font-size: 17px !important;
                font-weight: 900 !important;
                color: #1e1b4b !important;
                margin-bottom: 8px !important;
                text-align: center !important;
            }
            .gk-swal-html {
                font-family: 'IRANSansXFaNum', 'IRANSansX', sans-serif !important;
                font-size: 13.5px !important;
                color: #334155 !important;
                line-height: 1.6 !important;
                text-align: center !important;
            }
            .gk-swal-confirm {
                background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%) !important;
                color: #ffffff !important;
                font-weight: 900 !important;
                font-size: 13px !important;
                padding: 10px 22px !important;
                border-radius: 12px !important;
                border: none !important;
                cursor: pointer !important;
                box-shadow: 0 4px 14px rgba(79, 70, 229, 0.3) !important;
                margin: 0 6px !important;
                transition: transform 0.15s !important;
            }
            .gk-swal-confirm:hover {
                transform: scale(1.03) !important;
            }
            .gk-swal-confirm.gk-swal-danger {
                background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
                box-shadow: 0 4px 14px rgba(220, 38, 38, 0.3) !important;
            }
            .gk-swal-cancel {
                background: #f1f5f9 !important;
                color: #64748b !important;
                font-weight: 800 !important;
                font-size: 13px !important;
                padding: 10px 18px !important;
                border-radius: 12px !important;
                border: 1.5px solid #cbd5e1 !important;
                cursor: pointer !important;
                margin: 0 6px !important;
            }
            .gk-swal-toast {
                font-family: 'IRANSansXFaNum', 'IRANSansX', sans-serif !important;
                border-radius: 14px !important;
                font-size: 13px !important;
                font-weight: 800 !important;
                direction: rtl !important;
                padding: 10px 16px !important;
                box-shadow: 0 10px 25px rgba(0,0,0,0.15) !important;
            }

            .gk-card-box {
                background: #ffffff;
                border: 2px solid #e2e8f0;
                border-radius: 22px;
                padding: 24px;
                box-shadow: 0 6px 24px rgba(0,0,0,0.03);
                margin-bottom: 24px;
            }
            .gk-btn-tool {
                background: #6366f1;
                color: #fff !important;
                border: none;
                border-radius: 12px;
                padding: 9px 16px;
                font-weight: 900;
                font-size: 12.5px;
                text-decoration: none !important;
                display: inline-flex;
                align-items: center;
                gap: 6px;
                box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
                cursor: pointer;
                transition: transform 0.2s;
            }
            .gk-btn-tool:hover {
                transform: translateY(-1px);
            }

            .gk-table-st {
                width: 100%;
                border-collapse: separate;
                border-spacing: 0 8px;
            }
            .gk-table-st th {
                padding: 8px 12px;
                font-size: 12.5px;
                color: #64748b;
                font-weight: 800;
            }
            .gk-table-st td {
                padding: 12px 10px;
                background: #f8fafc;
                font-size: 13px;
                border-top: 1.5px solid #e2e8f0;
                border-bottom: 1.5px solid #e2e8f0;
                vertical-align: middle;
            }
            .gk-table-st td:first-child {
                border-right: 1.5px solid #e2e8f0;
                border-top-right-radius: 12px;
                border-bottom-right-radius: 12px;
            }
            .gk-table-st td:last-child {
                border-left: 1.5px solid #e2e8f0;
                border-top-left-radius: 12px;
                border-bottom-left-radius: 12px;
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
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border: 1.5px solid transparent;
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
            .gk-btn-play-link {
                background: #e0f2fe;
                color: #0284c7 !important;
                border-color: #bae6fd;
            }
            .gk-btn-play-link:hover {
                background: #0284c7;
                color: #ffffff !important;
                border-color: #0284c7;
                box-shadow: 0 4px 12px rgba(2, 132, 199, 0.35);
            }
            .gk-btn-test-link {
                background: #ede9fe;
                color: #7c3aed !important;
                border-color: #ddd6fe;
            }
            .gk-btn-test-link:hover {
                background: #7c3aed;
                color: #ffffff !important;
                border-color: #7c3aed;
                box-shadow: 0 4px 12px rgba(124, 58, 237, 0.35);
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
            .gk-btn-arena {
                background: linear-gradient(135deg, #4f46e5, #4338ca);
                color: #ffffff !important;
                padding: 8px 14px;
                border-radius: 12px;
                font-size: 12.5px;
                font-weight: 900;
                text-decoration: none !important;
                display: inline-flex;
                align-items: center;
                gap: 6px;
                box-shadow: 0 3px 10px rgba(79, 70, 229, 0.25);
                transition: transform 0.2s;
            }
            .gk-btn-arena:hover {
                transform: translateY(-1px);
            }

            .gk-podium-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
                gap: 16px;
            }
            .gk-podium-card {
                background: #f8fafc;
                border: 2px solid #e2e8f0;
                border-radius: 18px;
                padding: 18px;
                text-align: center;
                transition: transform 0.2s;
            }
            .gk-podium-card:hover { transform: translateY(-2px); }
            .gk-podium-card.rank-1 {
                background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
                border-color: #f59e0b;
                box-shadow: 0 6px 18px rgba(245, 158, 11, 0.15);
            }
            .gk-podium-card.rank-2 {
                background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
                border-color: #94a3b8;
            }
            .gk-podium-card.rank-3 {
                background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
                border-color: #f97316;
            }

            /* استایل‌های کارنامه جامع و گزارش پیشرفت */
            .gk-btn-view-rc {
                background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
                color: #ffffff !important;
                font-size: 12px;
                font-weight: 900;
                padding: 7px 14px;
                border-radius: 10px;
                border: none;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                gap: 6px;
                box-shadow: 0 3px 10px rgba(79, 70, 229, 0.25);
                transition: all 0.2s ease;
                text-decoration: none !important;
            }
            .gk-btn-view-rc:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 16px rgba(79, 70, 229, 0.35);
            }

            .gk-rc-letterhead {
                background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
                border: 2px solid #e2e8f0;
                border-radius: 20px;
                padding: 18px 22px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
                gap: 16px;
                margin-bottom: 20px;
            }
            .gk-rc-brand-meta {
                display: flex;
                align-items: center;
                gap: 14px;
            }
            .gk-rc-logo {
                width: 60px;
                height: 60px;
                border-radius: 14px;
                object-fit: cover;
                border: 1.5px solid #cbd5e1;
            }
            .gk-rc-logo-fallback {
                width: 54px;
                height: 54px;
                background: #e0e7ff;
                border-radius: 14px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 26px;
            }
            .gk-rc-org-title {
                margin: 0 0 4px 0;
                font-size: 18px;
                font-weight: 900;
                color: #1e1b4b;
            }
            .gk-rc-subhead {
                font-size: 12.5px;
                color: #64748b;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .gk-rc-dot { color: #cbd5e1; font-weight: bold; }
            .gk-rc-actions-box {
                display: flex;
                flex-direction: column;
                align-items: flex-end;
                gap: 6px;
            }
            .gk-rc-btn-print {
                background: linear-gradient(135deg, #059669 0%, #047857 100%);
                color: #ffffff !important;
                font-size: 13px;
                font-weight: 900;
                padding: 9px 18px;
                border-radius: 12px;
                border: none;
                cursor: pointer;
                box-shadow: 0 4px 12px rgba(5, 150, 105, 0.25);
            }
            .gk-rc-date-badge {
                font-size: 11.5px;
                color: #64748b;
                font-weight: 600;
            }

            .gk-rc-student-card {
                background: #f8fafc;
                border: 1.5px solid #e2e8f0;
                border-radius: 18px;
                padding: 16px 20px;
                display: flex;
                align-items: center;
                gap: 16px;
                margin-bottom: 20px;
            }
            .gk-rc-st-avatar {
                width: 50px;
                height: 50px;
                background: #ffffff;
                border: 1.5px solid #cbd5e1;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 24px;
                box-shadow: 0 4px 10px rgba(0,0,0,0.04);
            }
            .gk-rc-st-name-row {
                display: flex;
                align-items: center;
                gap: 10px;
                flex-wrap: wrap;
                margin-bottom: 4px;
            }
            .gk-rc-st-name {
                font-size: 18px;
                font-weight: 900;
                color: #0f172a;
                margin: 0;
            }
            .gk-rc-st-age {
                background: #e2e8f0;
                color: #334155;
                font-size: 11.5px;
                font-weight: 800;
                padding: 2px 8px;
                border-radius: 6px;
            }
            .gk-rc-overall-badge {
                font-size: 12px;
                font-weight: 900;
                padding: 3px 10px;
                border-radius: 8px;
                border: 1px solid currentColor;
            }
            .gk-rc-st-meta-row {
                font-size: 12px;
                color: #64748b;
                display: flex;
                align-items: center;
                gap: 8px;
                flex-wrap: wrap;
            }

            .gk-rc-kpi-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
                gap: 14px;
                margin-bottom: 24px;
            }
            .gk-rc-kpi-item {
                background: #ffffff;
                border: 1.5px solid #e2e8f0;
                border-top: 4px solid #6366f1;
                border-radius: 16px;
                padding: 14px;
                display: flex;
                align-items: center;
                gap: 12px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.02);
            }
            .gk-rc-kpi-icon {
                width: 42px;
                height: 42px;
                border-radius: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 18px;
            }
            .gk-rc-kpi-label {
                font-size: 11.5px;
                color: #64748b;
                display: block;
                margin-bottom: 2px;
            }
            .gk-rc-kpi-val {
                font-size: 15px;
                font-weight: 900;
                color: #0f172a;
            }

            .gk-rc-section {
                background: #ffffff;
                border: 1.5px solid #e2e8f0;
                border-radius: 18px;
                padding: 18px;
                margin-bottom: 20px;
            }
            .gk-rc-sec-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 14px;
                border-bottom: 1.5px solid #f1f5f9;
                padding-bottom: 10px;
            }
            .gk-rc-sec-title {
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .gk-rc-sec-title h3 {
                margin: 0;
                font-size: 15px;
                font-weight: 900;
                color: #1e1b4b;
            }
            .gk-rc-sec-badge {
                background: #f1f5f9;
                color: #475569;
                font-size: 11.5px;
                font-weight: 800;
                padding: 3px 9px;
                border-radius: 8px;
            }
            .gk-rc-empty-box {
                text-align: center;
                padding: 24px;
                color: #94a3b8;
                font-size: 13px;
            }
            .gk-rc-empty-box span { font-size: 32px; display: block; margin-bottom: 6px; }
            .gk-rc-table-responsive { overflow-x: auto; }
            .gk-rc-table {
                width: 100%;
                border-collapse: collapse;
                font-size: 12.5px;
            }
            .gk-rc-table th {
                background: #f8fafc;
                color: #475569;
                font-weight: 800;
                padding: 10px 12px;
                border-bottom: 1.5px solid #e2e8f0;
                text-align: right;
            }
            .gk-rc-table td {
                padding: 11px 12px;
                border-bottom: 1px solid #f1f5f9;
                vertical-align: middle;
            }
            .gk-rc-score-pill {
                padding: 3px 9px;
                border-radius: 8px;
                font-weight: 900;
                font-size: 12px;
                display: inline-block;
            }
            .gk-badge-excel { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
            .gk-badge-good  { background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; }
            .gk-badge-need  { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
            .gk-rc-topic-chips {
                display: flex;
                flex-wrap: wrap;
                gap: 5px;
            }
            .gk-rc-topic-chip {
                font-size: 11px;
                padding: 2px 7px;
                border-radius: 6px;
                border: 1px solid #e2e8f0;
            }
            .gk-rc-topic-chip.is-correct {
                background: #f0fdf4;
                color: #15803d;
                border-color: #bbf7d0;
            }
            .gk-rc-topic-chip.is-review {
                background: #fffbeb;
                color: #b45309;
                border-color: #fde68a;
            }

            .gk-rc-footer-signature {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-top: 24px;
                padding-top: 16px;
                border-top: 2px dashed #cbd5e1;
                flex-wrap: wrap;
                gap: 20px;
            }
            .gk-rc-teacher-note {
                flex: 1 1 300px;
                font-size: 12px;
                color: #334155;
            }
            .gk-rc-note-line {
                height: 1px;
                background: #cbd5e1;
                margin-top: 18px;
            }
            .gk-rc-stamp-box {
                text-align: center;
            }
            .gk-rc-stamp-circle {
                width: 110px;
                height: 110px;
                border: 2.5px dashed #4f46e5;
                border-radius: 50%;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                color: #4f46e5;
                font-size: 11px;
                padding: 10px;
                box-sizing: border-box;
                transform: rotate(-5deg);
            }

            @media print {
                body {
                    background: #ffffff !important;
                    margin: 0 !important;
                    padding: 0 !important;
                    -webkit-print-color-adjust: exact !important;
                    print-color-adjust: exact !important;
                }
                .gk-teacher-wrap,
                header, footer, nav, .admin-bar, #wpadminbar {
                    display: none !important;
                }
                #gk-student-reportcard-modal {
                    position: static !important;
                    display: block !important;
                    width: 100% !important;
                    height: auto !important;
                    background: transparent !important;
                    backdrop-filter: none !important;
                    padding: 0 !important;
                    margin: 0 !important;
                }
                #gk-student-reportcard-modal > div {
                    position: static !important;
                    width: 100% !important;
                    max-width: 100% !important;
                    height: auto !important;
                    max-height: none !important;
                    overflow: visible !important;
                    padding: 0 !important;
                    margin: 0 !important;
                    box-shadow: none !important;
                    border: none !important;
                    background: #ffffff !important;
                }
                #gk-reportcard-modal-title,
                #gk-student-reportcard-modal button,
                .gk-rc-btn-print {
                    display: none !important;
                }
                .gk-reportcard-view {
                    width: 100% !important;
                    max-width: 100% !important;
                    padding: 0 !important;
                    margin: 0 !important;
                }
                .gk-rc-section, .gk-rc-letterhead, .gk-rc-student-card, .gk-rc-kpi-item {
                    break-inside: avoid !important;
                    page-break-inside: avoid !important;
                }
            }
                    /* استایل‌های واکنش‌گرا موبایل پنل معلم */
            @media (max-width: 768px) {
                .gk-teacher-wrap {
                    padding: 0 10px !important;
                    margin: 10px auto 40px auto !important;
                }
                .gk-teacher-header {
                    flex-direction: column !important;
                    align-items: stretch !important;
                    text-align: center !important;
                    gap: 14px !important;
                    padding: 18px 16px !important;
                    border-radius: 18px !important;
                }
                .gk-teacher-meta {
                    justify-content: center !important;
                    font-size: 12px !important;
                    gap: 8px !important;
                }
                .gk-teacher-header .gk-btn-tool {
                    flex: 1 1 100% !important;
                    justify-content: center !important;
                    padding: 11px 0 !important;
                }
                .gk-tab-lbl-short { display: inline !important; }
                .gk-tab-lbl-full { display: none !important; }
                .gk-tabs-nav-bar {
                    display: grid !important;
                    grid-template-columns: 1fr 1fr !important;
                    gap: 8px !important;
                    padding: 0 !important;
                    margin-bottom: 18px !important;
                    border-bottom: none !important;
                    overflow: visible !important;
                    width: 100% !important;
                    box-sizing: border-box !important;
                }
                .gk-tabs-nav-bar::-webkit-scrollbar { display: none; }
                .gk-tab-btn {
                    width: 100% !important;
                    box-sizing: border-box !important;
                    display: flex !important;
                    justify-content: space-between !important;
                    align-items: center !important;
                    padding: 12px 14px !important;
                    font-size: 13px !important;
                    border-radius: 14px !important;
                    border: 2px solid #e2e8f0 !important;
                    border-bottom: 2px solid #e2e8f0 !important;
                    bottom: 0 !important;
                    background: #f8fafc !important;
                    box-shadow: 0 2px 6px rgba(0,0,0,0.02) !important;
                    white-space: nowrap !important;
                }
                .gk-tab-btn.active {
                    background: #ffffff !important;
                    border: 2.5px solid #4f46e5 !important;
                    color: #4f46e5 !important;
                    font-weight: 900 !important;
                    box-shadow: 0 4px 14px rgba(79, 70, 229, 0.15) !important;
                }
                .gk-tab-btn.active .gk-tab-badge {
                    background: #4f46e5 !important;
                    color: #ffffff !important;
                }
                .gk-card-box {
                    padding: 16px 12px !important;
                    border-radius: 18px !important;
                }
                /* تبدیل ردیف‌های جدول نوآموزان به کارت‌های چندسطری شیک در موبایل */
                .gk-table-st {
                    min-width: 0 !important;
                    width: 100% !important;
                    border-spacing: 0 !important;
                }
                .gk-table-st thead {
                    display: none !important;
                }
                .gk-table-st, 
                .gk-table-st tbody, 
                .gk-t-st-row, 
                .gk-t-st-row td {
                    display: block !important;
                    width: 100% !important;
                    box-sizing: border-box !important;
                }
                .gk-t-st-row {
                    background: #ffffff !important;
                    border: 2px solid #e2e8f0 !important;
                    border-radius: 18px !important;
                    padding: 14px !important;
                    margin-bottom: 14px !important;
                    box-shadow: 0 3px 12px rgba(0,0,0,0.03) !important;
                    position: relative !important;
                }
                .gk-t-st-row td {
                    background: transparent !important;
                    border: none !important;
                    padding: 3px 0 !important;
                    border-radius: 0 !important;
                    text-align: right !important;
                }
                .gk-t-st-row td:nth-child(1) { /* رتبه */
                    position: absolute;
                    top: 14px;
                    left: 14px;
                    width: auto !important;
                    font-size: 11.5px;
                    color: #64748b;
                    background: #f1f5f9;
                    padding: 3px 8px;
                    border-radius: 8px;
                    font-weight: 900;
                }
                .gk-t-st-row td:nth-child(2) { /* نام */
                    font-size: 15px !important;
                    padding-left: 55px !important;
                    margin-bottom: 4px;
                }
                .gk-t-st-row td:nth-child(3) { /* سن */
                    display: inline-block !important;
                    width: auto !important;
                    color: #475569;
                    font-size: 11.5px;
                    background: #f8fafc;
                    padding: 2px 8px;
                    border-radius: 6px;
                    border: 1px solid #e2e8f0;
                    margin-left: 6px;
                    font-weight: bold;
                }
                .gk-t-st-row td:nth-child(4) { /* تلفن */
                    display: inline-block !important;
                    width: auto !important;
                    font-size: 12px;
                }
                .gk-t-st-row td:nth-child(5) { /* امتیاز کل */
                    margin: 8px 0 !important;
                }
                .gk-t-st-row td:nth-child(6) { /* دکمه‌های عملیات */
                    border-top: 1.5px dashed #e2e8f0 !important;
                    padding-top: 12px !important;
                    margin-top: 6px !important;
                }
                .gk-t-st-row .gk-action-icons-group {
                    display: flex !important;
                    justify-content: space-between !important;
                    width: 100% !important;
                    gap: 8px !important;
                }
                .gk-t-st-row .gk-icon-btn {
                    flex: 1 !important;
                    height: 42px !important;
                    border-radius: 12px !important;
                }

                /* تبدیل ردیف‌های جدول کارنامه به کارت‌های چندسطری شیک در موبایل */
                .gk-t-rc-row {
                    background: #ffffff !important;
                    border: 2px solid #e2e8f0 !important;
                    border-radius: 18px !important;
                    padding: 14px !important;
                    margin-bottom: 14px !important;
                    box-shadow: 0 3px 12px rgba(0,0,0,0.03) !important;
                    position: relative !important;
                    display: block !important;
                    width: 100% !important;
                    box-sizing: border-box !important;
                }
                .gk-t-rc-row td {
                    background: transparent !important;
                    border: none !important;
                    padding: 3px 0 !important;
                    border-radius: 0 !important;
                    text-align: right !important;
                    display: block !important;
                    width: 100% !important;
                    box-sizing: border-box !important;
                }
                .gk-t-rc-row td:nth-child(1) { /* رتبه */
                    position: absolute;
                    top: 14px;
                    left: 14px;
                    width: auto !important;
                    font-size: 11.5px;
                    color: #64748b;
                    background: #f1f5f9;
                    padding: 3px 8px;
                    border-radius: 8px;
                    font-weight: 900;
                }
                .gk-t-rc-row td:nth-child(2) { /* نام */
                    font-size: 15px !important;
                    padding-left: 55px !important;
                    margin-bottom: 4px;
                }
                .gk-t-rc-row td:nth-child(3) { /* سن */
                    display: inline-block !important;
                    width: auto !important;
                    color: #475569;
                    font-size: 11.5px;
                    background: #f8fafc;
                    padding: 2px 8px;
                    border-radius: 6px;
                    border: 1px solid #e2e8f0;
                    margin-left: 6px;
                    font-weight: bold;
                }
                .gk-t-rc-row td:nth-child(4) { /* تلفن */
                    display: inline-block !important;
                    width: auto !important;
                    font-size: 12px;
                }
                .gk-t-rc-row td:nth-child(5) { /* امتیاز کل */
                    margin: 8px 0 !important;
                }
                .gk-t-rc-row td:nth-child(6) { /* دکمه کارنامه */
                    border-top: 1.5px dashed #e2e8f0 !important;
                    padding-top: 12px !important;
                    margin-top: 6px !important;
                }
                .gk-t-rc-row .gk-btn-view-rc {
                    width: 100% !important;
                    justify-content: center !important;
                    height: 42px !important;
                    border-radius: 12px !important;
                    font-size: 13px !important;
                    box-sizing: border-box !important;
                }

                #gk-teacher-search-rc {
                    width: 100% !important;
                    box-sizing: border-box !important;
                    margin-top: 8px;
                }

                /* تبدیل جدول‌های آزمون‌ها و لیگ‌ها به کارت‌های چندسطری شیک در موبایل */
                .gk-table-exam-scores, 
                .gk-table-exam-links, 
                .gk-table-league-rankings {
                    min-width: 0 !important;
                    width: 100% !important;
                    border-spacing: 0 !important;
                }
                .gk-table-exam-scores thead,
                .gk-table-exam-links thead,
                .gk-table-league-rankings thead {
                    display: none !important;
                }
                .gk-table-exam-scores, 
                .gk-table-exam-scores tbody, 
                .gk-table-exam-scores tr, 
                .gk-table-exam-scores td,
                .gk-table-exam-links, 
                .gk-table-exam-links tbody, 
                .gk-table-exam-links tr, 
                .gk-table-exam-links td,
                .gk-table-league-rankings, 
                .gk-table-league-rankings tbody, 
                .gk-table-league-rankings tr, 
                .gk-table-league-rankings td {
                    display: block !important;
                    width: 100% !important;
                    box-sizing: border-box !important;
                }
                
                /* کارت‌های تابلوی نمرات آزمون در موبایل */
                .gk-table-exam-scores tbody tr {
                    background: #ffffff !important;
                    border: 1.5px solid #d8b4fe !important;
                    border-radius: 16px !important;
                    padding: 12px 14px !important;
                    margin-bottom: 12px !important;
                    box-shadow: 0 2px 8px rgba(124, 58, 237, 0.05) !important;
                    position: relative !important;
                }
                .gk-table-exam-scores td {
                    border: none !important;
                    background: transparent !important;
                    padding: 3px 0 !important;
                    text-align: right !important;
                }
                .gk-table-exam-scores td:nth-child(1) { /* رتبه */
                    position: absolute;
                    top: 12px;
                    left: 12px;
                    width: auto !important;
                    font-size: 13px;
                    background: #f3e8ff;
                    padding: 2px 8px;
                    border-radius: 8px;
                }
                .gk-table-exam-scores td:nth-child(2) { /* نام */
                    font-size: 15px !important;
                    padding-left: 50px !important;
                    margin-bottom: 4px;
                }
                .gk-table-exam-scores td:nth-child(3) { /* تعداد دروس */
                    display: inline-block !important;
                    width: auto !important;
                    font-size: 11.5px;
                    color: #64748b;
                    background: #f8fafc;
                    padding: 2px 8px;
                    border-radius: 6px;
                    border: 1px solid #e2e8f0;
                    margin-left: 6px;
                }
                .gk-table-exam-scores td:nth-child(4) { /* میانگین درصد */
                    display: inline-block !important;
                    width: auto !important;
                    margin-left: 6px;
                }
                .gk-table-exam-scores td:nth-child(5) { /* سطح توصیفی */
                    display: inline-block !important;
                    width: auto !important;
                    font-size: 12px;
                }
                .gk-table-exam-scores td:nth-child(6) { /* تاریخ */
                    font-size: 11px !important;
                    color: #94a3b8 !important;
                    margin-top: 6px !important;
                    border-top: 1px dashed #f3e8ff !important;
                    padding-top: 6px !important;
                }

                /* کارت‌های لینک‌های اختصاصی آزمون نوآموزان در موبایل */
                .gk-table-exam-links tbody tr {
                    background: #ffffff !important;
                    border: 1.5px solid #e2e8f0 !important;
                    border-radius: 16px !important;
                    padding: 12px 14px !important;
                    margin-bottom: 12px !important;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.02) !important;
                    position: relative !important;
                }
                .gk-table-exam-links td {
                    border: none !important;
                    background: transparent !important;
                    padding: 3px 0 !important;
                    text-align: right !important;
                }
                .gk-table-exam-links td:nth-child(1) { /* ردیف */
                    position: absolute;
                    top: 12px;
                    left: 12px;
                    width: auto !important;
                    font-size: 11.5px;
                    color: #64748b;
                    background: #f1f5f9;
                    padding: 2px 7px;
                    border-radius: 6px;
                }
                .gk-table-exam-links td:nth-child(2) { /* نام نوآموز */
                    font-size: 14.5px !important;
                    padding-left: 45px !important;
                    margin-bottom: 6px;
                }
                .gk-table-exam-links td:nth-child(3) { /* اینپوت لینک مستقیم در موبایل مخفی تا دکمه‌ها جا شوند */
                    display: none !important;
                }
                .gk-table-exam-links td:nth-child(4) { /* اکشن بار */
                    border-top: 1px dashed #e2e8f0 !important;
                    padding-top: 10px !important;
                    margin-top: 6px !important;
                }
                .gk-table-exam-links .gk-action-icons-group {
                    display: flex !important;
                    justify-content: space-between !important;
                    width: 100% !important;
                    gap: 8px !important;
                }
                .gk-table-exam-links .gk-icon-btn {
                    flex: 1 !important;
                    height: 40px !important;
                    border-radius: 10px !important;
                }

                /* کارت‌های رتبه‌بندی مسابقات کلاسی در موبایل */
                .gk-table-league-rankings tbody tr {
                    background: #ffffff !important;
                    border: 1.5px solid #e2e8f0 !important;
                    border-radius: 16px !important;
                    padding: 12px 14px !important;
                    margin-bottom: 12px !important;
                    position: relative !important;
                }
                .gk-table-league-rankings td {
                    border: none !important;
                    background: transparent !important;
                    padding: 3px 0 !important;
                    text-align: right !important;
                }
                .gk-table-league-rankings td:nth-child(1) { /* مدال/رتبه */
                    position: absolute;
                    top: 12px;
                    left: 12px;
                    width: auto !important;
                    font-size: 13px;
                }
                .gk-table-league-rankings td:nth-child(2) { /* نام */
                    font-size: 15px !important;
                    padding-left: 45px !important;
                    margin-bottom: 4px;
                }
                .gk-table-league-rankings td:nth-child(3) { /* رکورد */
                    display: inline-block !important;
                    width: auto !important;
                    margin-left: 6px;
                }
                .gk-table-league-rankings td:nth-child(4) { /* تعداد شرکت */
                    display: inline-block !important;
                    width: auto !important;
                    font-size: 11.5px;
                    color: #64748b;
                }
                .gk-table-league-rankings td:nth-child(5) { /* وضعیت */
                    display: inline-block !important;
                    width: auto !important;
                    font-size: 11.5px;
                    margin-right: 6px;
                }
                .gk-table-league-rankings td:nth-child(6) { /* اکشن‌ها */
                    border-top: 1px dashed #e2e8f0 !important;
                    padding-top: 10px !important;
                    margin-top: 6px !important;
                }
                .gk-table-league-rankings .gk-action-icons-group {
                    display: flex !important;
                    justify-content: space-between !important;
                    width: 100% !important;
                    gap: 8px !important;
                }
                .gk-table-league-rankings .gk-icon-btn {
                    flex: 1 !important;
                    height: 40px !important;
                    border-radius: 10px !important;
                }
                #gk-teacher-search-st {
                    width: 100% !important;
                    box-sizing: border-box !important;
                    margin-top: 8px;
                }
                #gk-new-league-modal > div {
                    max-width: 95% !important;
                    width: 95% !important;
                    margin: 20px auto !important;
                    padding: 20px 16px !important;
                }

                /* =================== استایل اختصاصی کارنامه لوکس در موبایل =================== */
                #gk-student-reportcard-modal > div {
                    max-width: 96% !important;
                    width: 96% !important;
                    margin: 12px auto !important;
                    padding: 16px 12px !important;
                    border-radius: 22px !important;
                }
                .gk-rc-letterhead {
                    flex-direction: column !important;
                    align-items: stretch !important;
                    text-align: center !important;
                    padding: 14px 12px !important;
                    gap: 12px !important;
                    background: linear-gradient(135deg, #f8fafc 0%, #eff6ff 100%) !important;
                    border: 1.5px solid #dbeafe !important;
                    border-radius: 16px !important;
                }
                .gk-rc-brand-meta {
                    flex-direction: column !important;
                    align-items: center !important;
                    gap: 8px !important;
                }
                .gk-rc-logo {
                    width: 50px !important;
                    height: 50px !important;
                    border-radius: 12px !important;
                }
                .gk-rc-logo-fallback {
                    width: 48px !important;
                    height: 48px !important;
                    font-size: 24px !important;
                }
                .gk-rc-org-title {
                    font-size: 16px !important;
                    margin-bottom: 2px !important;
                }
                .gk-rc-subhead {
                    justify-content: center !important;
                    font-size: 11.5px !important;
                }
                .gk-rc-actions-box {
                    align-items: stretch !important;
                    width: 100% !important;
                }
                .gk-rc-btn-print {
                    width: 100% !important;
                    padding: 10px 0 !important;
                    justify-content: center !important;
                    display: flex !important;
                    font-size: 13px !important;
                    border-radius: 10px !important;
                }
                .gk-rc-date-badge {
                    text-align: center !important;
                    margin-top: 4px !important;
                    font-size: 11px !important;
                }

                /* کارت مشخصات نوآموز */
                .gk-rc-student-card {
                    flex-direction: column !important;
                    align-items: center !important;
                    text-align: center !important;
                    padding: 14px 12px !important;
                    gap: 10px !important;
                    background: #ffffff !important;
                    border: 2px solid #e0e7ff !important;
                    border-radius: 16px !important;
                    box-shadow: 0 4px 14px rgba(99, 102, 241, 0.06) !important;
                }
                .gk-rc-st-avatar {
                    width: 48px !important;
                    height: 48px !important;
                    font-size: 24px !important;
                }
                .gk-rc-st-name-row {
                    justify-content: center !important;
                    gap: 6px !important;
                }
                .gk-rc-st-name {
                    font-size: 16.5px !important;
                    width: 100% !important;
                    margin-bottom: 2px !important;
                }
                .gk-rc-st-meta-row {
                    justify-content: center !important;
                    font-size: 11.5px !important;
                    gap: 6px !important;
                }

                /* کارت‌های آماری کلیدی ۲ در ۲ (2x2 Grid) */
                .gk-rc-kpi-grid {
                    grid-template-columns: 1fr 1fr !important;
                    gap: 8px !important;
                    margin-bottom: 16px !important;
                }
                .gk-rc-kpi-item {
                    padding: 10px 12px !important;
                    border-radius: 14px !important;
                    gap: 8px !important;
                }
                .gk-rc-kpi-icon {
                    width: 36px !important;
                    height: 36px !important;
                    font-size: 16px !important;
                    border-radius: 10px !important;
                }
                .gk-rc-kpi-label {
                    font-size: 10px !important;
                    margin-bottom: 1px !important;
                }
                .gk-rc-kpi-val {
                    font-size: 13.5px !important;
                }

                /* سکشن‌های کارنامه */
                .gk-rc-section {
                    padding: 14px 12px !important;
                    border-radius: 16px !important;
                    margin-bottom: 16px !important;
                }
                .gk-rc-sec-header {
                    flex-direction: column !important;
                    align-items: flex-start !important;
                    gap: 6px !important;
                }
                .gk-rc-sec-title h3 {
                    font-size: 13.5px !important;
                }
                .gk-rc-sec-badge {
                    font-size: 11px !important;
                    padding: 2px 8px !important;
                }

                /* تبدیل جداول درون کارنامه به کارت‌های چندسطری لوکس در موبایل */
                .gk-rc-table-responsive {
                    overflow: visible !important;
                }
                .gk-rc-table, 
                .gk-rc-table thead, 
                .gk-rc-table tbody, 
                .gk-rc-table tr, 
                .gk-rc-table td {
                    display: block !important;
                    width: 100% !important;
                    box-sizing: border-box !important;
                }
                .gk-rc-table thead {
                    display: none !important;
                }
                .gk-rc-table tbody tr {
                    background: #f8fafc !important;
                    border: 1.5px solid #e2e8f0 !important;
                    border-radius: 14px !important;
                    padding: 12px 14px !important;
                    margin-bottom: 10px !important;
                    box-shadow: 0 2px 6px rgba(0,0,0,0.02) !important;
                }
                .gk-rc-table td {
                    border: none !important;
                    background: transparent !important;
                    padding: 3px 0 !important;
                    text-align: right !important;
                }
                .gk-rc-table td strong {
                    font-size: 14px !important;
                }
                .gk-rc-score-pill {
                    margin: 4px 0 !important;
                    font-size: 12px !important;
                }
                .gk-rc-level-text {
                    font-size: 12px !important;
                    font-weight: 800 !important;
                    display: inline-block !important;
                    margin-right: 6px !important;
                }
                .gk-rc-topic-chips {
                    margin-top: 6px !important;
                    gap: 4px !important;
                }
                .gk-rc-topic-chip {
                    font-size: 10.5px !important;
                    padding: 2px 6px !important;
                }

                /* امضا و پاورقی */
                .gk-rc-footer-signature {
                    flex-direction: column !important;
                    align-items: center !important;
                    text-align: center !important;
                    gap: 16px !important;
                }
                .gk-rc-teacher-note {
                    text-align: center !important;
                }
            }
        </style>

        <div class="gk-teacher-wrap">
            
            <!-- هدر کلاس -->
            <div class="gk-teacher-header">
                <div>
                    <h1 class="gk-teacher-title">🏫 پنل اختصاصی کلاس: <?php echo esc_html($class->name); ?></h1>
                    <div class="gk-teacher-meta">
                        <span>🏢 مرکز: <strong><?php echo esc_html($org_name); ?></strong></span>
                        <?php if (!empty($class->teacher_name)): ?>
                            <span>👩‍🏫 مربی: <strong><?php echo esc_html($class->teacher_name); ?></strong></span>
                        <?php endif; ?>
                        <span>👶 <strong><?php echo $total_st; ?></strong> نوآموز</span>
                    </div>
                </div>
                <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                    <button type="button" class="gk-btn-tool" onclick="gkOpenExamModal();" style="background:linear-gradient(135deg,#7c3aed,#6d28d9); color:#ffffff !important; font-weight:900; padding:10px 18px; border-radius:12px; box-shadow:0 4px 14px rgba(124,58,237,0.35); border:none; cursor:pointer; font-size:13px;">
                        📝 + ایجاد امتحان کلاسی
                    </button>
                    <button type="button" class="gk-btn-tool" onclick="jQuery('#gk-new-league-modal').fadeIn(200);" style="background:linear-gradient(135deg,#f59e0b,#d97706); color:#ffffff !important; font-weight:900; padding:10px 18px; border-radius:12px; box-shadow:0 4px 14px rgba(245,158,11,0.35); border:none; cursor:pointer; font-size:13px;">
                        🏆 + ایجاد لیگ و مسابقه
                    </button>
                </div>
            </div>

            <!-- ناوبری تب‌ها -->
            <div class="gk-tabs-nav-bar">
                <button type="button" class="gk-tab-btn active" data-target="tab-students">
                    <span>🎓 <span class="gk-tab-lbl-full">لیست نوآموزان و دسترسی‌ها</span><span class="gk-tab-lbl-short">نوآموزان</span></span>
                    <span class="gk-tab-badge"><?php echo $total_st; ?></span>
                </button>
                <button type="button" class="gk-tab-btn" data-target="tab-leagues">
                    <span>🏆 <span class="gk-tab-lbl-full">مسابقات و لیگ‌ها</span><span class="gk-tab-lbl-short">مسابقات</span></span>
                    <span class="gk-tab-badge"><?php echo $total_leagues; ?></span>
                </button>
                <button type="button" class="gk-tab-btn" data-target="tab-exams">
                    <span>📝 <span class="gk-tab-lbl-full">آزمون‌ها و امتحانات کلاسی</span><span class="gk-tab-lbl-short">آزمون‌ها</span></span>
                    <span class="gk-tab-badge" id="gk-exams-count-badge"><?php echo $total_exams; ?></span>
                </button>
                <button type="button" class="gk-tab-btn" data-target="tab-reportcards">
                    <span>📊 <span class="gk-tab-lbl-full">کارنامه و گزارش پیشرفت</span><span class="gk-tab-lbl-short">کارنامه‌ها</span></span>
                    <span class="gk-tab-badge"><?php echo $total_st; ?></span>
                </button>
            </div>

            <!-- ===================== تب ۱: لیست نوآموزان ===================== -->
            <div class="gk-tab-pane active" id="tab-students">
                <div class="gk-card-box">
                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:18px;">
                        <div>
                            <h2 style="font-size:17px; font-weight:900; margin:0 0 4px 0; color:#1e293b;">
                                🎓 لیست نوآموزان کلاس و لینک‌های اختصاصی اولیا
                            </h2>
                            <p style="font-size:12.5px; color:#64748b; margin:0;">
                                می‌توانید لینک آزمون‌ها و بازی‌های هر نوآموز را مستقیماً به پیام‌رسان بله مادر ارسال کنید یا کپی نمایید.
                            </p>
                        </div>
                        <div>
                            <input type="text" id="gk-teacher-search-st" placeholder="🔍 جستجوی نام نوآموز..." style="padding:8px 14px; border-radius:12px; border:1.5px solid #cbd5e1; font-size:12.5px; width:220px;">
                        </div>
                    </div>

                    <div style="overflow-x:auto;">
                        <table class="gk-table-st">
                            <thead>
                                <tr>
                                    <th>رتبه</th>
                                    <th>نام نوآموز</th>
                                    <th>سن</th>
                                    <th>شماره همراه والد</th>
                                    <th>مجموع امتیازات</th>
                                    <th style="text-align:center;">دسترسی و ارسال اختصاصی</th>
                                </tr>
                            </thead>
                            <tbody id="gk-t-students-tbody">
                                <?php if (empty($students)): ?>
                                    <tr>
                                        <td colspan="6" style="text-align:center; padding:30px; color:#94a3b8;">
                                            هنوز دانـش‌آموزی در این کلاس ثبت نشده است.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($students as $idx => $st): 
                                        $test_link = home_url('/tests/?st_token=' . $st->student_token);
                                        $game_link = home_url('/games/?st_token=' . $st->student_token);
                                        $curr_tests_link = home_url('/curriculum-tests/?st_token=' . $st->student_token);
                                        
                                        $bale_msg = "سلام و احترام، اولیا گرامی نوآموز «" . $st->name . "»\nلینک‌های اختصاصی در " . $org_name . " (کلاس " . $class->name . "):\n🎮 سالن بازی‌ها: " . $game_link . "\n📝 آزمون‌های درسی دبستان: " . $curr_tests_link . "\n🧠 تست‌های روان‌شناختی: " . $test_link;
                                        $bale_url = "https://ble.ir/share/url?url=" . urlencode($curr_tests_link) . "&text=" . urlencode($bale_msg);
                                        $phone_display = !empty($st->parent_phone) ? $st->parent_phone : 'ثبت نشده';
                                    ?>
                                        <tr class="gk-t-st-row">
                                            <td><strong>#<?php echo $idx + 1; ?></strong></td>
                                            <td><strong style="font-size:14px; color:#1e293b;">👶 <?php echo esc_html($st->name); ?></strong></td>
                                            <td><?php echo esc_html($st->age); ?> ساله</td>
                                            <td>
                                                <span style="font-size:12px; font-weight:bold; color:#475569;">
                                                    📞 <?php echo esc_html($phone_display); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span style="background:#ecfdf5; color:#059669; border:1px solid #a7f3d0; padding:3px 8px; border-radius:8px; font-weight:900; font-size:12px;">
                                                    🏆 <?php echo number_format($st->total_game_score); ?>
                                                </span>
                                            </td>
                                            <td style="text-align:center;">
                                                <div class="gk-action-icons-group">
                                                    <a href="<?php echo esc_url($bale_url); ?>" target="_blank" class="gk-icon-btn gk-btn-bale" title="ارسال لینک بازی و آزمون‌ها به بله مادر">
                                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                                                    </a>
                                                    <a href="<?php echo esc_url($game_link); ?>" target="_blank" class="gk-icon-btn gk-btn-play-link" title="ورود مستقیم به بازی‌ها به عنوان <?php echo esc_attr($st->name); ?>">
                                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                                                    </a>
                                                    <a href="<?php echo esc_url($test_link); ?>" target="_blank" class="gk-icon-btn gk-btn-test-link" title="ورود مستقیم به آزمون‌های <?php echo esc_attr($st->name); ?>">
                                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                                                    </a>
                                                    <button type="button" class="gk-icon-btn gk-btn-copy" title="کپی لینک آزمون‌ها و بازی‌های <?php echo esc_attr($st->name); ?>" onclick="gkCopyText('<?php echo esc_js($curr_tests_link); ?>', this);">
                                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
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

            <!-- ===================== تب ۲: مسابقات و لیگ‌های کلاسی ===================== -->
            <div class="gk-tab-pane" id="tab-leagues">
                
                <!-- سکوی قهرمانان و تابلوی لیگ امتیازی کل کلاس درون تب مسابقات -->
                <div class="gk-card-box" style="margin-bottom:24px; border:2px solid #e0e7ff; background:#ffffff;">
                    <div style="border-bottom:2px dashed #e0e7ff; padding-bottom:12px; margin-bottom:18px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                        <div>
                            <h3 style="font-size:17px; font-weight:900; margin:0; color:#1e1b4b;">🥇 سکوی قهرمانان و تابلوی لیگ امتیازی کل کلاس</h3>
                            <p style="font-size:12.5px; color:#64748b; margin:4px 0 0 0;">رتبه‌بندی زنده کودکان بر اساس مجموع امتیازات کسب‌شده در تمام بازی‌ها</p>
                        </div>
                        <span style="background:#ede9fe; color:#6d28d9; padding:4px 12px; border-radius:10px; font-size:12px; font-weight:900;">
                            🏆 رتبه‌بندی کل کلاس (<?php echo count($students); ?> نفر)
                        </span>
                    </div>

                    <div class="gk-podium-grid">
                        <?php if (empty($students)): ?>
                            <p style="grid-column:1/-1; text-align:center; padding:30px; color:#94a3b8;">هنوز نوآموزی در این کلاس ثبت نشده است.</p>
                        <?php else: ?>
                            <?php foreach ($students as $idx => $st): 
                                $rank = $idx + 1;
                                $medals = [1 => '🥇', 2 => '🥈', 3 => '🥉'];
                                $medal_icon = $medals[$rank] ?? "🎖️ #$rank";
                                $card_class = ($rank <= 3) ? "rank-$rank" : "";
                            ?>
                                <div class="gk-podium-card <?php echo $card_class; ?>">
                                    <span style="font-size:36px; display:block; margin-bottom:6px;"><?php echo $medal_icon; ?></span>
                                    <div style="font-size:16px; font-weight:900; color:#0f172a; margin-bottom:4px;">👶 <?php echo esc_html($st->name); ?></div>
                                    <div style="font-size:12px; color:#64748b; margin-bottom:6px;">سن: <?php echo esc_html($st->age); ?> ساله</div>
                                    <div style="font-size:14px; font-weight:900; color:#6366f1;">🏆 <?php echo number_format($st->total_game_score); ?> امتیاز</div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; flex-wrap:wrap; gap:10px;">
                    <div>
                        <h2 style="font-size:18px; font-weight:900; margin:0 0 4px 0; color:#1e1b4b;">
                            🏆 مسابقات و چالش‌های اختصاصی کلاس
                        </h2>
                        <p style="font-size:12.5px; color:#64748b; margin:0;">
                            هر مسابقه شامل ۱ یا چند بازی منتخب و مهلت زمانی مشخص است.
                        </p>
                    </div>
                    <button type="button" class="gk-btn-tool" onclick="jQuery('#gk-new-league-modal').fadeIn(200);" style="background:linear-gradient(135deg,#f59e0b,#d97706);">
                        + ایجاد مسابقه جدید 🎯
                    </button>
                </div>

                <div id="gk-leagues-container">
                    <?php if (empty($leagues)): ?>
                        <div id="gk-no-leagues-msg" style="background:#ffffff; border:2px dashed #cbd5e1; border-radius:22px; padding:36px; text-align:center; color:#64748b;">
                            <div style="font-size:44px; margin-bottom:10px;">🎯</div>
                            <h3 style="margin:0 0 6px 0; color:#334155; font-size:17px;">هنوز مسابقه‌ای برای این کلاس تعریف نکرده‌اید!</h3>
                            <p style="margin:0 0 18px 0; font-size:13px;">می‌توانید با انتخاب بازی‌ها و تعیین مهلت زمانی، مسابقه کلاسی راه بیاندازید و لینک آن را ارسال کنید.</p>
                            <button type="button" class="gk-btn-tool" onclick="jQuery('#gk-new-league-modal').fadeIn(200);" style="background:linear-gradient(135deg,#f59e0b,#d97706); padding:10px 22px;">
                                🏆 ساخت اولین مسابقه کلاسی 🚀
                            </button>
                        </div>
                    <?php else: ?>
                        <?php foreach ($leagues as $l): 
                            $l_games = json_decode($l->games_list, true) ?: [];
                            $league_url = home_url('/league/?code=' . $l->league_code);
                            
                            $is_l_expired = false;
                            $l_expiry_badge = '<span style="background:#dcfce7; color:#15803d; border:1px solid #86efac; padding:3px 8px; border-radius:8px; font-weight:800; font-size:11.5px;">⏳ فعال (بدون محدودیت)</span>';
                            if (!empty($l->expires_at)) {
                                $exp_time = strtotime($l->expires_at);
                                if (time() > $exp_time) {
                                    $is_l_expired = true;
                                    $l_expiry_badge = '<span style="background:#fee2e2; color:#b91c1c; border:1px solid #fca5a5; padding:3px 8px; border-radius:8px; font-weight:800; font-size:11.5px;">🔒 منقضی شده</span>';
                                } else {
                                    $h_left = ceil(($exp_time - time()) / 3600);
                                    $l_expiry_badge = "<span style='background:#fef3c7; color:#b45309; border:1px solid #fde68a; padding:3px 8px; border-radius:8px; font-weight:800; font-size:11.5px;'>⏳ $h_left ساعت مانده</span>";
                                }
                            }

                            $league_bulk_msg = "🏆 لیست لینک‌های اختصاصی شرکت در مسابقه «" . $l->title . "» (کلاس " . $class->name . "):\n\n";
                            foreach ($students as $idx => $cs) {
                                $cs_league_url = home_url('/league/?code=' . $l->league_code . '&st_token=' . $cs->student_token);
                                $league_bulk_msg .= ($idx + 1) . ". 👶 " . $cs->name . ": " . $cs_league_url . "\n";
                            }
                            $bale_league_bulk_share = "https://ble.ir/share/url?url=" . urlencode(home_url()) . "&text=" . urlencode($league_bulk_msg);

                            $l_rankings = $wpdb->get_results($wpdb->prepare("
                                SELECT s.id, s.name, s.age, s.parent_phone, s.student_token, MAX(ls.score) as best_score, COUNT(ls.id) as attempts_count
                                FROM $table_students s
                                LEFT JOIN $table_scores ls ON s.id = ls.student_id AND ls.league_id = %d
                                WHERE s.class_id = %d
                                GROUP BY s.id, s.name, s.age, s.parent_phone, s.student_token
                                ORDER BY best_score DESC, attempts_count DESC, s.id ASC
                            ", $l->id, $class->id));
                        ?>
                            <div class="gk-card-box" id="gk-league-card-<?php echo $l->id; ?>" style="border-top:4px solid <?php echo $is_l_expired ? '#94a3b8' : '#6366f1'; ?>;">
                                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:16px; border-bottom:1.5px solid #f1f5f9; padding-bottom:12px;">
                                    <div>
                                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
                                            <h3 style="margin:0; font-size:17.5px; font-weight:900; color:#1e1b4b;">
                                                🏆 <?php echo esc_html($l->title); ?>
                                            </h3>
                                            <?php echo $l_expiry_badge; ?>
                                        </div>
                                        <span style="font-size:12px; color:#64748b;">
                                            🎮 شامل <?php echo count($l_games); ?> بازی منتخب | 📅 ایجاد شده: <?php echo date_i18n('j F Y', strtotime($l->created_at)); ?>
                                        </span>
                                    </div>
                                    <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                                        <a href="<?php echo esc_url($league_url); ?>" target="_blank" class="gk-btn-arena" title="ورود مستقیم به سالن مسابقه">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                                            <span>سالن مسابقه</span>
                                        </a>
                                        <div class="gk-action-icons-group">
                                            <a href="<?php echo esc_url($bale_league_bulk_share); ?>" target="_blank" class="gk-icon-btn gk-btn-bale" title="ارسال گروهی لینک‌های اختصاصی مسابقه به بله مادران">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                                            </a>
                                            <button type="button" class="gk-icon-btn gk-btn-copy" title="کپی لینک سالن مسابقه" onclick="gkCopyText('<?php echo esc_js($league_url); ?>', this);">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                                            </button>
                                            <button type="button" class="gk-icon-btn gk-btn-delete" title="حذف مسابقه" onclick="gkDeleteLeague(<?php echo $l->id; ?>);">
                                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <h4 style="margin:0 0 10px 0; font-size:13.5px; color:#334155; font-weight:800;">
                                    📊 رتبه‌بندی زنده و ارسال اختصاصی لینک مسابقه برای هر نوآموز:
                                </h4>
                                <div style="overflow-x:auto;">
                                    <table class="gk-table-st gk-table-league-rankings" style="width:100%; border-collapse:separate; border-spacing:0 6px;">
                                        <thead>
                                            <tr style="color:#64748b; font-size:12px;">
                                                <th style="padding:6px 10px;">رتبه</th>
                                                <th style="padding:6px 10px;">نام نوآموز</th>
                                                <th style="padding:6px 10px;">بهترین رکورد</th>
                                                <th style="padding:6px 10px;">تعداد شرکت</th>
                                                <th style="padding:6px 10px;">وضعیت</th>
                                                <th style="padding:6px 10px; text-align:center;">ارسال اختصاصی لینک مسابقه</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($l_rankings as $idx => $r): 
                                                $rank = $idx + 1;
                                                $medals = [1 => '🥇', 2 => '🥈', 3 => '🥉'];
                                                $medal = $medals[$rank] ?? "#$rank";
                                                $has_p = intval($r->attempts_count) > 0;

                                                $child_league_link = home_url('/league/?code=' . $l->league_code . '&st_token=' . $r->student_token);
                                                $bale_child_msg = "سلام و احترام، اولیا گرامی نوآموز «" . $r->name . "»\nلینک اختصاصی شرکت در مسابقه «" . $l->title . "» در " . $org_name . " (کلاس " . $class->name . "):\n" . $child_league_link;
                                                $bale_child_url = "https://ble.ir/share/url?url=" . urlencode($child_league_link) . "&text=" . urlencode($bale_child_msg);
                                            ?>
                                                <tr style="background:#f8fafc; font-size:13px;">
                                                    <td style="padding:8px 10px; font-weight:bold; border-right:1.5px solid #e2e8f0; border-top-right-radius:10px; border-bottom-right-radius:10px;">
                                                        <?php echo $medal; ?>
                                                    </td>
                                                    <td style="padding:8px 10px; font-weight:bold; color:#1e293b;">
                                                        👶 <?php echo esc_html($r->name); ?>
                                                    </td>
                                                    <td style="padding:8px 10px;">
                                                        <?php if ($has_p && intval($r->best_score) > 0): ?>
                                                            <span style="background:#ecfdf5; color:#059669; border:1px solid #a7f3d0; padding:3px 8px; border-radius:6px; font-weight:900; font-size:12px;">
                                                                🏆 <?php echo number_format(intval($r->best_score)); ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span style="color:#94a3b8; font-size:11.5px;">بدون رکورد</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td style="padding:8px 10px; color:#64748b;"><?php echo intval($r->attempts_count); ?> بار</td>
                                                    <td style="padding:8px 10px;">
                                                        <?php echo $has_p ? '<span style="color:#059669; font-weight:bold;">✅ شرکت کرده</span>' : '<span style="color:#d97706; font-weight:bold;">⏳ در انتظار</span>'; ?>
                                                    </td>
                                                    <td style="padding:8px 10px; text-align:center; border-left:1.5px solid #e2e8f0; border-top-left-radius:10px; border-bottom-left-radius:10px;">
                                                        <div class="gk-action-icons-group" style="justify-content:center;">
                                                            <a href="<?php echo esc_url($bale_child_url); ?>" target="_blank" class="gk-icon-btn gk-btn-bale" title="ارسال لینک این مسابقه به بله مادر <?php echo esc_attr($r->name); ?>">
                                                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                                                            </a>
                                                            <a href="<?php echo esc_url($child_league_link); ?>" target="_blank" class="gk-icon-btn gk-btn-play-link" title="ورود مستقیم به مسابقه به عنوان <?php echo esc_attr($r->name); ?>">
                                                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                                                            </a>
                                                            <button type="button" class="gk-icon-btn gk-btn-copy" title="کپی لینک اختصاصی مسابقه برای <?php echo esc_attr($r->name); ?>" onclick="gkCopyText('<?php echo esc_js($child_league_link); ?>', this);">
                                                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </div>

            <!-- ===================== تب ۳: آزمون‌ها و امتحانات کلاسی ===================== -->
            <div class="gk-tab-pane" id="tab-exams">
                
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; flex-wrap:wrap; gap:10px;">
                    <div>
                        <h2 style="font-size:18px; font-weight:900; margin:0 0 4px 0; color:#1e1b4b;">
                            📝 آزمون‌ها و امتحانات کلاسی اختصاصی
                        </h2>
                        <p style="font-size:12.5px; color:#64748b; margin:0;">
                            می‌توانید برای هر فصل یا چند درس مشخص امتحان کلاسی بسازید، مهلت تعیین کنید و نتایج دانش‌آموزان را به همراه کارنامه مشاهده نمایید.
                        </p>
                    </div>
                    <button type="button" class="gk-btn-tool" onclick="gkOpenExamModal();" style="background:linear-gradient(135deg,#7c3aed,#6d28d9); color:#fff;">
                        + ایجاد امتحان کلاسی جدید 📝
                    </button>
                </div>

                <div id="gk-exams-container">
                    <?php if (empty($class_exams)): ?>
                        <div id="gk-no-exams-msg" style="background:#ffffff; border:2px dashed #cbd5e1; border-radius:22px; padding:36px; text-align:center; color:#64748b;">
                            <div style="font-size:44px; margin-bottom:10px;">📝</div>
                            <h3 style="margin:0 0 6px 0; color:#334155; font-size:17px;">هنوز آزمونی برای این کلاس تعریف نکرده‌اید!</h3>
                            <p style="margin:0 0 18px 0; font-size:13px;">با انتخاب دروس کتب پایه اول (ریاضی، فارسی، علوم، هدیه‌ها) و تعیین مهلت، امتحان کلاسی را بسازید و برای مادران ارسال کنید.</p>
                            <button type="button" class="gk-btn-tool" onclick="gkOpenExamModal();" style="background:linear-gradient(135deg,#7c3aed,#6d28d9); color:#fff; padding:10px 22px;">
                                🚀 ساخت اولین آزمون کلاسی 📝
                            </button>
                        </div>
                    <?php else: ?>
                        <?php foreach ($class_exams as $ex): 
                            $table_exam_scores = $wpdb->prefix . 'gk_class_exam_scores';
                            $ex_scores = $wpdb->get_results($wpdb->prepare(
                                "SELECT s.student_id, COALESCE(st.name, 'نوآموز کلاس') as name, st.student_token, AVG(s.score) as avg_score, COUNT(DISTINCT s.quiz_id) as taken_count, MAX(s.created_at) as last_taken
                                 FROM $table_exam_scores s
                                 LEFT JOIN $table_students st ON s.student_id = st.id
                                 WHERE s.exam_id = %d
                                 GROUP BY s.student_id, st.name, st.student_token
                                 ORDER BY avg_score DESC, taken_count DESC",
                                $ex->id
                            )); 
                            $ex_tests = json_decode($ex->tests_list, true) ?: [];
                            $exam_url = home_url('/class-exam/?code=' . $ex->exam_code);
                            
                            $is_ex_expired = false;
                            $ex_time_left = 'نامحدود ♾️';
                            if (!empty($ex->expires_at)) {
                                $diff = strtotime($ex->expires_at) - current_time('timestamp');
                                if ($diff <= 0) {
                                    $is_ex_expired = true;
                                    $ex_time_left = 'منقضی شده ⛔';
                                } else {
                                    $h = floor($diff / 3600);
                                    $m = floor(($diff % 3600) / 60);
                                    $ex_time_left = $h > 0 ? "⏳ $h ساعت و $m دقیقه" : "⏳ $m دقیقه";
                                }
                            }

                            // Bulk Bale message for mothers
                            $bale_exam_bulk = "سلام و احترام🌷\nآزمون کلاسی «" . $ex->title . "» در " . $org_name . " (کلاس " . $class->name . ") آغاز شد!\n" .
                                             "📝 شامل " . count($ex_tests) . " آزمون درسی\n" .
                                             "⏳ مهلت: " . $ex_time_left . "\n\n" .
                                             "🔗 لینک سالن آزمون برای ورود نوآموزان:\n" . $exam_url;
                            $bale_exam_share = "https://ble.ir/share/url?url=" . urlencode($exam_url) . "&text=" . urlencode($bale_exam_bulk);
                        ?>
                            <div class="gk-card-box" id="gk-exam-card-<?php echo $ex->id; ?>" style="margin-bottom:16px; border-right:5px solid #7c3aed;">
                                <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px;">
                                    <div>
                                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                                            <h3 style="font-size:16px; font-weight:900; margin:0; color:#1e1b4b;">
                                                📝 <?php echo esc_html($ex->title); ?>
                                            </h3>
                                            <span style="background:#ede9fe; color:#6d28d9; border:1px solid #c4b5fd; font-size:11.5px; font-weight:800; padding:2px 8px; border-radius:6px;">
                                                کد: <?php echo esc_html($ex->exam_code); ?>
                                            </span>
                                            <?php if ($is_ex_expired): ?>
                                                <span style="background:#fee2e2; color:#dc2626; border:1px solid #fca5a5; font-size:11px; font-weight:800; padding:2px 8px; border-radius:6px;">
                                                    پایان یافته ⛔
                                                </span>
                                            <?php else: ?>
                                                <span style="background:#dcfce7; color:#15803d; border:1px solid #86efac; font-size:11px; font-weight:800; padding:2px 8px; border-radius:6px;">
                                                    🟢 در حال برگزاری
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <div style="display:flex; align-items:center; gap:12px; font-size:12.5px; color:#64748b; margin-bottom:12px; flex-wrap:wrap;">
                                            <span>📅 ساخت: <?php echo date_i18n('Y/m/d H:i', strtotime($ex->created_at)); ?></span>
                                            <span><?php echo esc_html($ex_time_left); ?></span>
                                            <span>📚 <?php echo count($ex_tests); ?> آزمون منتخب</span>
                                        </div>

                                        <!-- Selected Tests Badges -->
                                        <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                            <?php foreach ($all_curriculum_tests as $ct): 
                                                if (in_array($ct['id'], $ex_tests)): ?>
                                                    <span style="background:#f8fafc; border:1px solid #e2e8f0; font-size:11.5px; padding:3px 8px; border-radius:6px; color:#475569; font-weight:bold;">
                                                        <?php echo $ct['icon'] . ' ' . esc_html($ct['title']); ?>
                                                    </span>
                                                <?php endif; 
                                            endforeach; ?>
                                        </div>
                                    </div>

                                    <!-- Action Buttons with Compact Icons & Tooltips -->
                                    <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                                        <button type="button" class="gk-btn-tool" onclick="jQuery('#gk-ex-scores-<?php echo $ex->id; ?>').slideToggle(200);" style="background:linear-gradient(135deg,#7c3aed,#6d28d9); color:#ffffff !important; padding:8px 14px; font-size:12px; font-weight:900; border-radius:10px; border:none; cursor:pointer;">
                                            📊 تابلوی نمرات (<?php echo count($ex_scores); ?>) ▼
                                        </button>

                                        <button type="button" class="gk-btn-tool" onclick="jQuery('#gk-st-exam-links-<?php echo $ex->id; ?>').slideToggle(200);" style="background:linear-gradient(135deg,#4f46e5,#4338ca); color:#ffffff !important; padding:8px 14px; font-size:12px; font-weight:900; border-radius:10px; border:none; cursor:pointer;">
                                            👶 لینک اختصاصی نوآموزان ▼
                                        </button>

                                        <div class="gk-action-icons-group">
                                            <a href="<?php echo esc_url($exam_url); ?>" target="_blank" class="gk-icon-btn gk-btn-play-link" title="ورود مستقیم به سالن آزمون">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                                            </a>
                                            <a href="<?php echo esc_url($bale_exam_share); ?>" target="_blank" class="gk-icon-btn gk-btn-bale" title="ارسال گروهی لینک‌های اختصاصی آزمون به بله مادران">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                                            </a>
                                            <button type="button" class="gk-icon-btn gk-btn-copy" title="کپی لینک سالن آزمون" onclick="gkCopyText('<?php echo esc_js($exam_url); ?>', this);">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                                            </button>
                                            <button type="button" class="gk-icon-btn gk-btn-delete" title="حذف این آزمون" onclick="gkDeleteExam(<?php echo $ex->id; ?>);">
                                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- تابلوی اختصاصی نمرات این آزمون (فقط قابل مشاهده برای معلم) -->
                                <div id="gk-ex-scores-<?php echo $ex->id; ?>" style="display:none; margin-top:16px; padding:16px; background:#faf5ff; border:1.5px solid #d8b4fe; border-radius:16px;">
                                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                                        <div style="font-size:13.5px; font-weight:900; color:#581c87;">
                                            📊 تابلوی نمرات و ارزیابی توصیفی دانش‌آموزان در آزمون «<?php echo esc_html($ex->title); ?>»:
                                        </div>
                                        <span style="font-size:12px; color:#6b21a8; font-weight:bold; background:#ede9fe; padding:3px 10px; border-radius:8px;">
                                            تعداد کل شرکت‌کنندگان: <?php echo count($ex_scores); ?> نفر
                                        </span>
                                    </div>

                                    <?php if (empty($ex_scores)): ?>
                                        <div style="background:#ffffff; border:1.5px dashed #c084fc; border-radius:14px; padding:24px 16px; text-align:center; margin:8px 0;">
                                            <div style="font-size:32px; margin-bottom:8px;">⏳</div>
                                            <div style="font-size:14px; font-weight:900; color:#581c87; margin-bottom:4px;">
                                                هنوز هیچ دانش‌آموزی در این آزمون شرکت نکرده است.
                                            </div>
                                            <div style="font-size:12px; color:#6b21a8; opacity:0.8;">
                                                به محض اینکه نوآموزان از طریق لینک اختصاصی یا عمومی در این آزمون شرکت کنند، نمرات و ارزیابی توصیفی آن‌ها در این بخش نمایش داده خواهد شد.
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div style="overflow-x:auto;">
                                            <table class="gk-table-st gk-table-exam-scores" style="font-size:12.5px; background:#fff; border-radius:12px; width:100%;">
                                                <thead>
                                                    <tr style="background:#f3e8ff; color:#581c87;">
                                                        <th>رتبه</th>
                                                        <th>نام نوآموز</th>
                                                        <th style="text-align:center;">تعداد دروس آزمون‌داده</th>
                                                        <th style="text-align:center;">میانگین درصد تسلط</th>
                                                        <th style="text-align:center;">سطح توصیفی</th>
                                                        <th style="text-align:center;">آخرین ثبت</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($ex_scores as $s_idx => $sc): 
                                                        $s_medal = ($s_idx === 0) ? '🥇 ' : (($s_idx === 1) ? '🥈 ' : (($s_idx === 2) ? '🥉 ' : '#' . ($s_idx + 1)));
                                                        $s_pct = round($sc->avg_score);
                                                        $s_lvl = ($s_pct >= 85) ? '🌟 خیلی خوب' : (($s_pct >= 70) ? '🟢 خوب' : (($s_pct >= 50) ? '🟡 قابل قبول' : '🟠 نیازمند تلاش'));
                                                    ?>
                                                        <tr>
                                                            <td><strong><?php echo $s_medal; ?></strong></td>
                                                            <td><strong style="color:#6d28d9;">👶 <?php echo esc_html($sc->name); ?></strong></td>
                                                            <td style="text-align:center; color:#64748b;"><?php echo $sc->taken_count; ?> از <?php echo count($ex_tests); ?> درس</td>
                                                            <td style="text-align:center;">
                                                                <span style="background:#ede9fe; color:#6b21a8; font-weight:900; padding:3px 8px; border-radius:6px;">
                                                                    <?php echo $s_pct; ?>٪
                                                                </span>
                                                            </td>
                                                            <td style="text-align:center;">
                                                                <span style="font-weight:900; color:#1e1b4b;">
                                                                    <?php echo $s_lvl; ?>
                                                                </span>
                                                            </td>
                                                            <td style="text-align:center; font-size:11.5px; color:#64748b;">
                                                                <?php echo date_i18n('Y/m/d H:i', strtotime($sc->last_taken)); ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Student Specific Exam Links Dropdown -->
                                <div id="gk-st-exam-links-<?php echo $ex->id; ?>" style="display:none; margin-top:16px; padding-top:14px; border-top:1.5px dashed #cbd5e1;">
                                    <div style="font-size:12.5px; font-weight:bold; color:#475569; margin-bottom:8px;">
                                        👶 لینک اختصاصی ورود هر نوآموز به این آزمون (با ثبت خودکار نمره در کارنامه):
                                    </div>
                                    <div style="overflow-x:auto;">
                                        <table class="gk-table-st gk-table-exam-links" style="font-size:12.5px;">
                                            <thead>
                                                <tr>
                                                    <th style="width:50px;">ردیف</th>
                                                    <th>نام نوآموز</th>
                                                    <th>لینک مستقیم اختصاصی آزمون</th>
                                                    <th style="text-align:center; width:160px;">ارسال و دسترسی اختصاصی</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($students as $st_idx => $st_obj): 
                                                    $st_ex_url = home_url('/class-exam/?code=' . $ex->exam_code . '&st_token=' . $st_obj->student_token);
                                                    $st_bale_msg = "سلام و احترام، اولیا گرامی نوآموز «" . $st_obj->name . "»🌷\n" .
                                                                   "📝 لینک اختصاصی شرکت در امتحان کلاسی «" . $ex->title . "» در " . $org_name . " (کلاس " . $class->name . "):\n" .
                                                                   $st_ex_url . "\n" .
                                                                   "⏳ مهلت: " . $ex_time_left;
                                                    $st_bale_share = "https://ble.ir/share/url?url=" . urlencode($st_ex_url) . "&text=" . urlencode($st_bale_msg);
                                                ?>
                                                    <tr>
                                                        <td><strong>#<?php echo $st_idx + 1; ?></strong></td>
                                                        <td><strong style="color:#1e293b;">👶 <?php echo esc_html($st_obj->name); ?></strong></td>
                                                        <td>
                                                            <input type="text" readonly value="<?php echo esc_url($st_ex_url); ?>" style="width:100%; font-size:11px; padding:5px 10px; border-radius:8px; border:1px solid #cbd5e1; direction:ltr; background:#f8fafc; color:#334155;" onclick="this.select();">
                                                        </td>
                                                        <td style="text-align:center;">
                                                            <div class="gk-action-icons-group" style="justify-content:center;">
                                                                <a href="<?php echo esc_url($st_bale_share); ?>" target="_blank" class="gk-icon-btn gk-btn-bale" title="ارسال به بله مادر <?php echo esc_attr($st_obj->name); ?>">
                                                                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                                                                </a>
                                                                <a href="<?php echo esc_url($st_ex_url); ?>" target="_blank" class="gk-icon-btn gk-btn-play-link" title="ورود مستقیم به آزمون به عنوان <?php echo esc_attr($st_obj->name); ?>">
                                                                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                                                                </a>
                                                                <button type="button" class="gk-icon-btn gk-btn-copy" title="کپی لینک اختصاصی این آزمون برای <?php echo esc_attr($st_obj->name); ?>" onclick="gkCopyText('<?php echo esc_js($st_ex_url); ?>', this);">
                                                                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </div>

            <!-- ===================== تب ۴: کارنامه و گزارش پیشرفت نوآموزان ===================== -->
            <div class="gk-tab-pane" id="tab-reportcards">
                <div class="gk-card-box">
                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:18px;">
                        <div>
                            <h2 style="font-size:17px; font-weight:900; margin:0 0 4px 0; color:#1e293b;">
                                📊 کارنامه جامع، ریز نمرات و سوابق نوآموزان
                            </h2>
                            <p style="font-size:12.5px; color:#64748b; margin:0;">
                                مشاهده کارنامه تک‌تک دانش‌آموزان شامل تمامی امتحانات کلاسی، آزمون‌های کتب درسی دبستان، آزمون‌های هوش و رکوردهای بازی‌ها همراه با قابلیت چاپ رسمی.
                            </p>
                        </div>
                        <div>
                            <input type="text" id="gk-teacher-search-rc" placeholder="🔍 جستجوی نام نوآموز..." style="padding:8px 14px; border-radius:12px; border:1.5px solid #cbd5e1; font-size:12.5px; width:220px;">
                        </div>
                    </div>

                    <div style="overflow-x:auto;">
                        <table class="gk-table-st">
                            <thead>
                                <tr>
                                    <th>رتبه</th>
                                    <th>نام و نام خانوادگی</th>
                                    <th>سن</th>
                                    <th>شماره والد</th>
                                    <th>مجموع امتیاز بازی‌ها</th>
                                    <th style="text-align:center;">کارنامه و گزارش پیشرفت</th>
                                </tr>
                            </thead>
                            <tbody id="gk-t-reportcards-tbody">
                                <?php if (empty($students)): ?>
                                    <tr>
                                        <td colspan="6" style="text-align:center; padding:30px; color:#94a3b8;">
                                            هنوز دانـش‌آموزی در این کلاس ثبت نشده است.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($students as $idx => $st): ?>
                                        <tr class="gk-t-rc-row">
                                            <td><strong>#<?php echo $idx + 1; ?></strong></td>
                                            <td><strong style="font-size:14px; color:#1e293b;">👶 <?php echo esc_html($st->name); ?></strong></td>
                                            <td><?php echo esc_html($st->age); ?> ساله</td>
                                            <td>
                                                <span style="font-size:12px; font-weight:bold; color:#475569;">
                                                    📞 <?php echo esc_html($st->parent_phone ?: 'ثبت نشده'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span style="background:#ecfdf5; color:#059669; border:1px solid #a7f3d0; padding:3px 8px; border-radius:8px; font-weight:900; font-size:12px;">
                                                    🏆 <?php echo number_format($st->total_game_score); ?> امتیاز
                                                </span>
                                            </td>
                                            <td style="text-align:center;">
                                                <button type="button" class="gk-btn-view-rc" onclick="gkOpenStudentReportCard(<?php echo $st->id; ?>)">
                                                    <span>📊 مشاهده کارنامه جامع</span>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- مودال کارنامه جامع نوآموز -->
            <div id="gk-student-reportcard-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.75); z-index:9999999; backdrop-filter:blur(6px); direction:rtl; text-align:right;">
                <div style="max-width:920px; width:95%; margin:24px auto; background:#ffffff; border-radius:24px; padding:26px; box-shadow:0 25px 50px rgba(0,0,0,0.35); max-height:92vh; overflow-y:auto; border:2px solid #e2e8f0; position:relative;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; border-bottom:1.5px solid #f1f5f9; padding-bottom:12px;">
                        <h3 id="gk-reportcard-modal-title" style="margin:0; font-size:18px; color:#1e1b4b; font-weight:900;">📊 کارنامه جامع و سوابق نوآموز</h3>
                        <button type="button" onclick="gkCloseStudentReportCard();" style="background:#f1f5f9; border:none; width:34px; height:34px; border-radius:50%; font-size:16px; cursor:pointer; color:#64748b; display:flex; align-items:center; justify-content:center;">✕</button>
                    </div>
                    <div id="gk-reportcard-modal-body">
                        <!-- Dynamic AJAX content loaded here -->
                    </div>
                </div>
            </div>

            <!-- مدال ساخت لیگ کلاسی با تنظیم مهلت زمانی -->
            
            <!-- مودال ساخت آزمون و امتحان کلاسی جدید -->
            <div id="gk-new-exam-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.7); z-index:9999999; backdrop-filter:blur(6px); direction:rtl; text-align:right;">
                <div style="max-width:640px; margin:24px auto; background:#ffffff; border-radius:24px; padding:26px; box-shadow:0 25px 50px rgba(0,0,0,0.35); max-height:92vh; overflow-y:auto; border:2px solid #e2e8f0;">
                    
                    <!-- Modal Header -->
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; border-bottom:1.5px solid #f1f5f9; padding-bottom:12px;">
                        <h3 style="margin:0; font-size:18px; color:#1e1b4b; font-weight:900;">📝 ایجاد آزمون و امتحان کلاسی جدید</h3>
                        <button type="button" onclick="gkCloseExamModal();" style="background:#f1f5f9; border:none; width:32px; height:32px; border-radius:50%; font-size:16px; cursor:pointer; color:#64748b; display:flex; align-items:center; justify-content:center;">✕</button>
                    </div>

                    <!-- 1. Exam Title -->
                    <div style="margin-bottom:12px;">
                        <label style="font-size:13px; font-weight:900; color:#334155; display:block; margin-bottom:6px;">عنوان آزمون کلاسی:</label>
                        <input type="text" id="gk-exam-title" placeholder="مثلاً: آزمون ۳ فصل اول ریاضی و فارسی..." style="width:100%; padding:10px 14px; border-radius:12px; border:1.5px solid #cbd5e1; font-size:13px; box-sizing:border-box; font-family:inherit;">
                    </div>

                    <!-- 2. Validity / Expiration -->
                    <div style="margin-bottom:14px;">
                        <label style="font-size:13px; font-weight:900; color:#334155; display:block; margin-bottom:6px;">⏳ مهلت شرکت در آزمون (مدت اعتبار لینک):</label>
                        <select id="gk-exam-validity" style="width:100%; padding:10px 14px; border-radius:12px; border:1.5px solid #cbd5e1; font-size:13px; box-sizing:border-box; background:#fff; font-weight:bold; font-family:inherit;">
                            <option value="6">⏳ ۶ ساعت آینده</option>
                            <option value="12">⏳ ۱۲ ساعت آینده</option>
                            <option value="24" selected>⏳ ۲۴ ساعت (۱ روز) - استاندارد</option>
                            <option value="48">⏳ ۴۸ ساعت (۲ روز)</option>
                            <option value="72">⏳ ۷۲ ساعت (۳ روز)</option>
                            <option value="168">⏳ ۱ هفته (۷ روز)</option>
                            <option value="0">♾️ نامحدود (بدون انقضا)</option>
                        </select>
                    </div>

                    <!-- 3. Smart Search & Filter Box Inside Modal -->
                    <div style="background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:16px; padding:14px; margin-bottom:14px;">
                        
                        <!-- Search Input -->
                        <div style="position:relative; margin-bottom:10px;">
                            <input type="text" id="gk-exam-modal-search" placeholder="🔍 جستجوی سریع نام درس، موضوع، کتاب یا فصل..." oninput="gkFilterExamModalTests()" style="width:100%; padding:9px 12px 9px 32px; border-radius:10px; border:1.5px solid #cbd5e1; font-size:12.5px; box-sizing:border-box; font-family:inherit;">
                            <span style="position:absolute; left:10px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:14px;">🔍</span>
                        </div>

                        <!-- 1. Subject Chips (کتاب‌ها) -->
                        <div style="margin-bottom:12px;">
                            <div style="font-size:12px; font-weight:900; color:#334155; margin-bottom:6px; display:flex; align-items:center; gap:6px;">
                                <span>📚</span> انتخاب کتاب درسی:
                            </div>
                            <div style="display:flex; flex-wrap:wrap; gap:6px;" id="gkExamModalSubjectChips">
                                <button type="button" class="gk-chip-btn is-active" data-subj="all" onclick="gkSetExamModalSubj('all', this)" style="padding:6px 12px; border-radius:10px; font-size:12px; font-weight:900; cursor:pointer; border:2px solid #7c3aed; background:#7c3aed; color:#ffffff; box-shadow:0 2px 6px rgba(124,58,237,0.3);">🌟 همه کتاب‌ها</button>
                                <button type="button" class="gk-chip-btn" data-subj="hedyeh" onclick="gkSetExamModalSubj('hedyeh', this)" style="padding:6px 12px; border-radius:10px; font-size:12px; font-weight:900; cursor:pointer; border:2px solid #fbcfe8; background:#fdf2f8; color:#9d174d;">🌸 هدیه‌ها</button>
                                <button type="button" class="gk-chip-btn" data-subj="oloom" onclick="gkSetExamModalSubj('oloom', this)" style="padding:6px 12px; border-radius:10px; font-size:12px; font-weight:900; cursor:pointer; border:2px solid #a7f3d0; background:#ecfdf5; color:#065f46;">🔬 علوم</button>
                                <button type="button" class="gk-chip-btn" data-subj="riazi" onclick="gkSetExamModalSubj('riazi', this)" style="padding:6px 12px; border-radius:10px; font-size:12px; font-weight:900; cursor:pointer; border:2px solid #bfdbfe; background:#eff6ff; color:#1e40af;">🔢 ریاضی</button>
                                <button type="button" class="gk-chip-btn" data-subj="farsi" onclick="gkSetExamModalSubj('farsi', this)" style="padding:6px 12px; border-radius:10px; font-size:12px; font-weight:900; cursor:pointer; border:2px solid #fde68a; background:#fffbeb; color:#92400e;">📖 فارسی</button>
                            </div>
                        </div>

                        <!-- 2. Chapter / Lesson Number Chips (شماره درس / فصل / تم) -->
                        <div style="margin-bottom:12px;">
                            <div style="font-size:12px; font-weight:900; color:#334155; margin-bottom:6px; display:flex; align-items:center; gap:6px;">
                                <span>🏷️</span> انتخاب شماره درس یا فصل:
                            </div>
                            <div style="display:flex; flex-wrap:wrap; gap:6px;" id="gkExamModalChapterChips">
                                <button type="button" class="gk-chap-btn is-active" data-chap="all" onclick="gkSetExamModalChap('all', this)" style="padding:5px 11px; border-radius:10px; font-size:11.5px; font-weight:900; cursor:pointer; border:2px solid #0284c7; background:#0284c7; color:#ffffff; box-shadow:0 2px 6px rgba(2,132,199,0.3);">🌟 همه درس‌ها</button>
                                <button type="button" class="gk-chap-btn" data-chap="1" onclick="gkSetExamModalChap('1', this)" style="padding:5px 11px; border-radius:10px; font-size:11.5px; font-weight:900; cursor:pointer; border:2px solid #e2e8f0; background:#ffffff; color:#334155;">1️⃣ درس ۱ / فصل ۱ / تم ۱</button>
                                <button type="button" class="gk-chap-btn" data-chap="2" onclick="gkSetExamModalChap('2', this)" style="padding:5px 11px; border-radius:10px; font-size:11.5px; font-weight:900; cursor:pointer; border:2px solid #e2e8f0; background:#ffffff; color:#334155;">2️⃣ درس ۲ / فصل ۲ / تم ۲</button>
                                <button type="button" class="gk-chap-btn" data-chap="3" onclick="gkSetExamModalChap('3', this)" style="padding:5px 11px; border-radius:10px; font-size:11.5px; font-weight:900; cursor:pointer; border:2px solid #e2e8f0; background:#ffffff; color:#334155;">3️⃣ درس ۳ / فصل ۳ / تم ۳</button>
                            </div>
                        </div>

                        <!-- Quick Select Buttons -->
                        <div style="display:flex; justify-content:space-between; align-items:center; font-size:11.5px; color:#64748b; border-top:1px dashed #cbd5e1; padding-top:8px;">
                            <span id="gkExamModalMatchCount" style="font-weight:900; color:#1e1b4b;">۱۲ آزمون منطبق</span>
                            <div style="display:flex; gap:8px;">
                                <button type="button" onclick="gkSelectAllVisibleExamTests(true)" style="background:#e0f2fe; border:none; color:#0369a1; padding:3px 8px; border-radius:6px; font-size:11px; font-weight:800; cursor:pointer;">☑️ انتخاب همه نمایان</button>
                                <button type="button" onclick="gkSelectAllVisibleExamTests(false)" style="background:#fee2e2; border:none; color:#dc2626; padding:3px 8px; border-radius:6px; font-size:11px; font-weight:800; cursor:pointer;">⬜ لغو انتخاب</button>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Tests Checklist -->
                    <div style="margin-bottom:18px;">
                        <label style="font-size:13px; font-weight:900; color:#334155; display:block; margin-bottom:8px;">لیست آزمون‌های منتخب برای این امتحان:</label>
                        <div id="gkExamModalTestsList" style="max-height:240px; overflow-y:auto; border:1.5px solid #cbd5e1; border-radius:14px; padding:10px; background:#f8fafc;">
                            
                            <?php 
                            $subjects_group = [
                                'hedyeh' => [
                                    'title' => '🌸 هدیه‌های آسمان (پایه اول)', 
                                    'tests' => [
                                        'test-hedyeh1-dars1' => '1', 
                                        'test-hedyeh1-dars2' => '2', 
                                        'test-hedyeh1-dars3' => '3'
                                    ]
                                ],
                                'oloom'  => [
                                    'title' => '🔬 علوم تجربی (پایه اول)',   
                                    'tests' => [
                                        'test-oloom1-fasl1' => '1', 
                                        'test-oloom1-fasl2' => '2', 
                                        'test-oloom1-fasl3' => '3'
                                    ]
                                ],
                                'riazi'  => [
                                    'title' => '🔢 ریاضی هوشمند (پایه اول)', 
                                    'tests' => [
                                        'test-riazi1-tem1' => '1', 
                                        'test-riazi1-tem2' => '2', 
                                        'test-riazi1-tem3' => '3'
                                    ]
                                ],
                                'farsi'  => [
                                    'title' => '📖 فارسی و نگاره‌ها (پایه اول)', 
                                    'tests' => [
                                        'test-farsi1-negare1' => '1', 
                                        'test-farsi1-negare2' => '2', 
                                        'test-farsi1-negare3' => '3'
                                    ]
                                ],
                            ];

                            foreach ($subjects_group as $s_key => $s_data): ?>
                                <div class="gk-exam-subj-group" data-subj="<?php echo $s_key; ?>" style="margin-bottom: 12px;">
                                    <div style="font-weight:900; font-size:12.5px; color:#1e1b4b; margin-bottom:6px; background:#e0e7ff; padding:4px 8px; border-radius:6px;">
                                        <?php echo $s_data['title']; ?>
                                    </div>
                                    <?php foreach ($all_curriculum_tests as $ct_item): 
                                        if (array_key_exists($ct_item['id'], $s_data['tests'])): 
                                            $chap_num = $s_data['tests'][$ct_item['id']];
                                            $search_text = mb_strtolower($ct_item['title'] . ' ' . $ct_item['desc'] . ' ' . $ct_item['subject_name'] . ' ' . $ct_item['chapter'] . ' درس ' . $chap_num . ' فصل ' . $chap_num . ' تم ' . $chap_num . ' نگاره ' . $chap_num . ' پایه اول کلاس اول دبستان');
                                        ?>
                                            <label class="gk-exam-test-item" 
                                                   data-subj="<?php echo $s_key; ?>"
                                                   data-chap="<?php echo $chap_num; ?>"
                                                   data-search="<?php echo esc_attr($search_text); ?>"
                                                   style="display:flex; align-items:center; gap:8px; padding:6px 8px; font-size:12.5px; cursor:pointer; border-radius:6px; margin-bottom:2px; font-weight:700; color:#334155; transition:background 0.15s;">
                                                <input type="checkbox" name="gk_exam_tests[]" value="<?php echo esc_attr($ct_item['id']); ?>" style="width:16px; height:16px; cursor:pointer;">
                                                <span><?php echo $ct_item['icon'] . ' ' . esc_html($ct_item['title']); ?></span>
                                            </label>
                                        <?php endif;
                                    endforeach; ?>
                                </div>
                            <?php endforeach; ?>

                        </div>
                    </div>

                    <button type="button" id="gk-btn-save-new-exam" onclick="gkSubmitCreateExam(this);" class="gk-btn-tool" style="width:100%; justify-content:center; background:linear-gradient(135deg,#7c3aed,#6d28d9); color:#fff; padding:12px; font-size:14px; border-radius:14px; font-weight:900;">
                        🚀 ایجاد آزمون کلاسی و دریافت لینک‌ها
                    </button>
                </div>
            </div>

            <div id="gk-new-league-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.7); z-index:9999999; backdrop-filter:blur(5px); direction:rtl; text-align:right;">
                <div style="max-width:550px; margin:50px auto; background:#ffffff; border-radius:24px; padding:28px; box-shadow:0 20px 40px rgba(0,0,0,0.3); max-height:85vh; overflow-y:auto;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; border-bottom:1.5px solid #f1f5f9; padding-bottom:12px;">
                        <h3 style="margin:0; font-size:18px; color:#1e293b; font-weight:900;">🏆 ایجاد لیگ و مسابقه کلاسی جدید</h3>
                        <button type="button" onclick="jQuery('#gk-new-league-modal').fadeOut(200);" style="background:none; border:none; font-size:22px; cursor:pointer; color:#64748b;">✕</button>
                    </div>
                    <div style="margin-bottom:14px;">
                        <label style="font-size:13px; font-weight:bold; color:#334155; display:block; margin-bottom:6px;">عنوان مسابقه (مثلاً: چالش آخر هفته برج معمار):</label>
                        <input type="text" id="gk-league-title" placeholder="عنوان مسابقه..." style="width:100%; padding:10px 12px; border-radius:12px; border:1.5px solid #cbd5e1; font-size:13px; box-sizing:border-box;">
                    </div>
                    <div style="margin-bottom:14px;">
                        <label style="font-size:13px; font-weight:bold; color:#334155; display:block; margin-bottom:6px;">⏳ مهلت شرکت در مسابقه (مدت اعتبار لینک):</label>
                        <select id="gk-league-validity" style="width:100%; padding:10px 12px; border-radius:12px; border:1.5px solid #cbd5e1; font-size:13px; box-sizing:border-box; background:#fff; font-weight:bold;">
                            <option value="6">⏳ ۶ ساعت آینده</option>
                            <option value="12">⏳ ۱۲ ساعت آینده</option>
                            <option value="24" selected>⏳ ۲۴ ساعت (۱ روز) - استاندارد</option>
                            <option value="48">⏳ ۴۸ ساعت (۲ روز)</option>
                            <option value="72">⏳ ۷۲ ساعت (۳ روز)</option>
                            <option value="168">⏳ ۱ هفته (۷ روز)</option>
                            <option value="0">♾️ نامحدود (بدون انقضا)</option>
                        </select>
                    </div>
                    <div style="margin-bottom:18px;">
                        <label style="font-size:13px; font-weight:bold; color:#334155; display:block; margin-bottom:8px;">انتخاب بازی‌های این مسابقه:</label>
                        <div style="max-height:200px; overflow-y:auto; border:1.5px solid #e2e8f0; border-radius:14px; padding:10px;">
                            <?php foreach ($all_games as $g): 
                                $g_icon = function_exists('gk_get_game_icon') ? gk_get_game_icon($g->ID) : ['bg' => '#0284c7', 'svg' => ''];
                            ?>
                                <label style="display:flex; align-items:center; gap:10px; padding:8px; border-radius:8px; cursor:pointer; font-size:13px; border-bottom:1px solid #f1f5f9;">
                                    <input type="checkbox" class="gk-league-game-cb" value="<?php echo $g->ID; ?>" style="width:17px; height:17px;">
                                    <div style="width:28px; height:28px; border-radius:8px; background:<?php echo esc_attr($g_icon['bg']); ?>; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                        <div style="transform:scale(0.5); display:flex; align-items:center; justify-content:center;">
                                            <?php echo $g_icon['svg']; ?>
                                        </div>
                                    </div>
                                    <span style="font-weight:bold; color:#1e293b;"><?php echo esc_html($g->post_title); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div style="display:flex; gap:10px;">
                        <button type="button" id="gk-btn-submit-create-league" style="flex:1; background:linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color:#fff; font-weight:900; font-size:13.5px; padding:12px 0; border-radius:12px; border:none; cursor:pointer;">
                            🚀 ایجاد مسابقه و دریافت لینک
                        </button>
                        <button type="button" onclick="jQuery('#gk-new-league-modal').fadeOut(200);" style="background:#f1f5f9; border:1.5px solid #cbd5e1; border-radius:12px; padding:0 18px; font-weight:bold; font-size:13px; cursor:pointer;">
                            انصراف
                        </button>
                    </div>
                </div>
            </div>

        </div>

        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
        // موتور دیالوگ‌ها و پیام‌های شکیل و لوکس قربانی کیدز (GK Modal & Toast Engine)
        window.gkAlert = function(msg, title, icon, onOk) {
            if (typeof title === 'undefined') title = 'پیام سامانه';
            if (typeof icon === 'undefined') icon = 'info';
            if (typeof Swal !== 'undefined') {
                return Swal.fire({
                    title: title,
                    html: (typeof msg === 'string') ? msg.replace(/\n/g, '<br>') : msg,
                    icon: icon,
                    confirmButtonText: 'متوجه شدم ✨',
                    customClass: {
                        popup: 'gk-swal-popup',
                        title: 'gk-swal-title',
                        htmlContainer: 'gk-swal-html',
                        confirmButton: 'gk-swal-confirm'
                    },
                    buttonsStyling: false
                }).then(function(result) {
                    if (typeof onOk === 'function') onOk();
                });
            } else {
                window.alert(msg);
                if (typeof onOk === 'function') onOk();
            }
        };

        window.gkToast = function(msg, icon) {
            if (typeof icon === 'undefined') icon = 'success';
            if (typeof Swal !== 'undefined') {
                return Swal.fire({
                    toast: true,
                    position: 'top',
                    icon: icon,
                    title: msg,
                    showConfirmButton: false,
                    timer: 2500,
                    timerProgressBar: true,
                    customClass: {
                        popup: 'gk-swal-toast'
                    }
                });
            } else {
                window.alert(msg);
            }
        };

        window.gkConfirm = function(msg, title, onConfirm, onCancel) {
            if (typeof title === 'undefined') title = 'تأیید عملیات';
            if (typeof Swal !== 'undefined') {
                return Swal.fire({
                    title: title,
                    html: (typeof msg === 'string') ? msg.replace(/\n/g, '<br>') : msg,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'بله، مطمئنم ✅',
                    cancelButtonText: 'انصراف ❌',
                    reverseButtons: true,
                    customClass: {
                        popup: 'gk-swal-popup',
                        title: 'gk-swal-title',
                        htmlContainer: 'gk-swal-html',
                        confirmButton: 'gk-swal-confirm gk-swal-danger',
                        cancelButton: 'gk-swal-cancel'
                    },
                    buttonsStyling: false
                }).then(function(result) {
                    if (result.isConfirmed) {
                        if (typeof onConfirm === 'function') onConfirm();
                    } else if (result.isDismissed) {
                        if (typeof onCancel === 'function') onCancel();
                    }
                });
            } else {
                if (window.confirm(msg)) {
                    if (typeof onConfirm === 'function') onConfirm();
                } else {
                    if (typeof onCancel === 'function') onCancel();
                }
            }
        };

        // بازنویسی آلرت خام مرورگر به عنوان فال‌بک ایمن
        window.alert = function(msg) {
            window.gkAlert(msg);
        };

        function gkSwitchTeacherTab(targetTab) {
            if (!targetTab) return;
            var $btn = jQuery('.gk-tab-btn[data-target="' + targetTab + '"]');
            var $pane = jQuery('#' + targetTab);
            if ($btn.length && $pane.length) {
                jQuery('.gk-tab-btn').removeClass('active');
                $btn.addClass('active');
                jQuery('.gk-tab-pane').removeClass('active');
                $pane.addClass('active');
                try {
                    if (window.history && window.history.replaceState) {
                        window.history.replaceState(null, null, '#' + targetTab);
                    }
                    localStorage.setItem('gk_teacher_active_tab_<?php echo $class->id; ?>', targetTab);
                } catch(e) {}
            }
        }

        jQuery(document).ready(function($) {
            $('.gk-tab-btn').on('click', function() {
                var target = $(this).data('target');
                gkSwitchTeacherTab(target);
            });

            // بازیابی تب فعال از روی هش آدرس یا حافظه محلی
            var initialTab = '';
            if (window.location.hash) {
                var h = window.location.hash.replace('#', '');
                if ($('#' + h).length) {
                    initialTab = h;
                }
            }
            if (!initialTab) {
                try {
                    var saved = localStorage.getItem('gk_teacher_active_tab_<?php echo $class->id; ?>');
                    if (saved && $('#' + saved).length) {
                        initialTab = saved;
                    }
                } catch(e) {}
            }
            if (initialTab && initialTab !== 'tab-students') {
                gkSwitchTeacherTab(initialTab);
            }

            $('#gk-teacher-search-st').on('input', function() {
                var q = $(this).val().trim().toLowerCase();
                $('.gk-t-st-row').each(function() {
                    var text = $(this).text().toLowerCase();
                    $(this).toggle(text.indexOf(q) !== -1);
                });
            });

            $('#gk-btn-submit-create-league').on('click', function(e) {
                e.preventDefault();
                var title = $('#gk-league-title').val().trim();
                var validity = $('#gk-league-validity').val();
                var selectedGames = [];
                $('.gk-league-game-cb:checked').each(function() {
                    selectedGames.push($(this).val());
                });

                if (!title) { gkAlert('لطفاً عنوان مسابقه را وارد فرمایید.', 'عنوان الزامی است', 'warning'); return; }
                if (selectedGames.length === 0) { gkAlert('لطفاً حداقل یک بازی را برای این مسابقه انتخاب نمایید.', 'انتخاب بازی', 'warning'); return; }

                var btn = $(this).text('در حال ساخت مسابقه...').prop('disabled', true);

                $.post('<?php echo $ajax_url; ?>', {
                    action: 'gk_teacher_create_league',
                    nonce: '<?php echo $nonce; ?>',
                    class_id: <?php echo $class->id; ?>,
                    title: title,
                    validity_hours: validity,
                    game_ids: selectedGames
                }, function(res) {
                    btn.text('🚀 ایجاد مسابقه و دریافت لینک').prop('disabled', false);
                    if (res.success && res.data) {
                        $('#gk-new-league-modal').fadeOut(200);
                        try {
                            localStorage.setItem('gk_teacher_active_tab_<?php echo $class->id; ?>', 'tab-leagues');
                            window.location.hash = 'tab-leagues';
                        } catch(e) {}
                        
                        var modalHtml = '<div style="text-align:center; padding:10px 0;">' +
                            '<p style="font-size:14.5px; font-weight:bold; color:#1e1b4b; margin-bottom:12px;">مسابقه «' + res.data.title + '» با موفقیت ساخته شد! 🏆</p>' +
                            '<div style="background:#f1f5f9; border:1px solid #cbd5e1; padding:10px 12px; border-radius:12px; font-size:12px; word-break:break-all; color:#334155; margin-bottom:10px;">' +
                                '🔗 لینک مسابقه:<br><strong>' + res.data.league_url + '</strong>' +
                            '</div>' +
                        '</div>';

                        gkAlert(modalHtml, 'مسابقه فعال شد', 'success', function() {
                            window.location.reload(true);
                        });
                    } else {
                        gkAlert(res.data || 'خطا در ایجاد لیگ.', 'خطا', 'error');
                    }
                }).fail(function() {
                    btn.text('🚀 ایجاد مسابقه و دریافت لینک').prop('disabled', false);
                    gkAlert('خطای ارتباط با سرور.', 'خطا', 'error');
                });
            });
        });

        // تابع سراسری و هوشمند کپی لینک در کلیپ‌بورد
        function gkCopyText(text, btnEl) {
            if (!text) return;
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(function() {
                    gkShowCopySuccess(btnEl);
                }).catch(function() {
                    gkFallbackCopy(text, btnEl);
                });
            } else {
                gkFallbackCopy(text, btnEl);
            }
        }

        function gkFallbackCopy(text, btnEl) {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.top = '0';
            ta.style.left = '0';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.focus();
            ta.select();
            try {
                var successful = document.execCommand('copy');
                if (successful) {
                    gkShowCopySuccess(btnEl);
                } else {
                    gkAlert('لطفاً لینک را به صورت دستی کپی فرمایید:<br><br><code>' + text + '</code>', 'کپی دستی لینک', 'info');
                }
            } catch (err) {
                gkAlert('لطفاً لینک را به صورت دستی کپی فرمایید:<br><br><code>' + text + '</code>', 'کپی دستی لینک', 'info');
            }
            document.body.removeChild(ta);
        }

        function gkShowCopySuccess(btnEl) {
            gkToast('لینک با موفقیت کپی شد! 📋', 'success');
            if (btnEl) {
                var oldHtml = btnEl.innerHTML;
                var oldBg = btnEl.style.background;
                btnEl.innerHTML = '✅ کپی شد!';
                btnEl.style.background = '#10b981';
                btnEl.style.color = '#ffffff';
                setTimeout(function() {
                    btnEl.innerHTML = oldHtml;
                    btnEl.style.background = oldBg;
                }, 2000);
            }
        }

                                                var gkActiveModalSubj = 'all';
        var gkActiveModalChap = 'all';

        function gkSetExamModalSubj(subj, btn) {
            gkActiveModalSubj = subj;
            var chips = document.querySelectorAll('#gkExamModalSubjectChips button');
            chips.forEach(function(c) {
                c.classList.remove('is-active');
                var s = c.getAttribute('data-subj');
                if (s === 'all') { c.style.background = '#ffffff'; c.style.color = '#334155'; c.style.borderColor = '#cbd5e1'; }
                else if (s === 'hedyeh') { c.style.background = '#fdf2f8'; c.style.color = '#9d174d'; c.style.borderColor = '#fbcfe8'; }
                else if (s === 'oloom') { c.style.background = '#ecfdf5'; c.style.color = '#065f46'; c.style.borderColor = '#a7f3d0'; }
                else if (s === 'riazi') { c.style.background = '#eff6ff'; c.style.color = '#1e40af'; c.style.borderColor = '#bfdbfe'; }
                else if (s === 'farsi') { c.style.background = '#fffbeb'; c.style.color = '#92400e'; c.style.borderColor = '#fde68a'; }
            });
            if (btn) {
                btn.classList.add('is-active');
                btn.style.background = '#7c3aed';
                btn.style.color = '#ffffff';
                btn.style.borderColor = '#7c3aed';
            }
            gkFilterExamModalTests();
        }

        function gkSetExamModalChap(chap, btn) {
            gkActiveModalChap = chap;
            var chips = document.querySelectorAll('#gkExamModalChapterChips button');
            chips.forEach(function(c) {
                c.classList.remove('is-active');
                c.style.background = '#ffffff';
                c.style.color = '#334155';
                c.style.borderColor = '#e2e8f0';
            });
            if (btn) {
                btn.classList.add('is-active');
                btn.style.background = '#0284c7';
                btn.style.color = '#ffffff';
                btn.style.borderColor = '#0284c7';
            }
            gkFilterExamModalTests();
        }

        function gkFilterExamModalTests() {
            var searchInp = document.getElementById('gk-exam-modal-search');
            var q = searchInp ? searchInp.value.trim().toLowerCase() : '';
            var activeSubj = gkActiveModalSubj;
            var activeChap = gkActiveModalChap;

            var items = document.querySelectorAll('.gk-exam-test-item');
            var groups = document.querySelectorAll('.gk-exam-subj-group');
            var visibleCount = 0;

            items.forEach(function(item) {
                var iSubj = item.getAttribute('data-subj');
                var iChap = item.getAttribute('data-chap');
                var iSearch = item.getAttribute('data-search') || '';

                var matchSubj = (activeSubj === 'all' || iSubj === activeSubj);
                var matchChap = (activeChap === 'all' || iChap === activeChap);
                var matchQuery = (!q || iSearch.indexOf(q) !== -1);

                if (matchSubj && matchChap && matchQuery) {
                    item.style.display = 'flex';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });

            groups.forEach(function(grp) {
                var hasVisibleChild = grp.querySelectorAll('.gk-exam-test-item[style*="display: flex"]').length > 0;
                grp.style.display = (hasVisibleChild ? 'block' : 'none');
            });

            var countBadge = document.getElementById('gkExamModalMatchCount');
            if (countBadge) {
                countBadge.textContent = Number(visibleCount).toLocaleString('fa-IR') + ' آزمون منطبق';
            }
        }

        function gkSelectAllVisibleExamTests(selectState) {
            var items = document.querySelectorAll('.gk-exam-test-item');
            items.forEach(function(item) {
                if (item.style.display !== 'none') {
                    var cb = item.querySelector('input[type="checkbox"]');
                    if (cb) cb.checked = selectState;
                }
            });
        }
        var gkExamSubmitting = false;
        function gkSubmitCreateExam(btnEl) {
            if (gkExamSubmitting) return;

            var titleEl = document.getElementById('gk-exam-title');
            var title = titleEl ? titleEl.value.trim() : '';
            var validityEl = document.getElementById('gk-exam-validity');
            var validity = validityEl ? validityEl.value : '24';
            var selectedTests = [];
            var checkboxes = document.querySelectorAll('#gkExamModalTestsList input[name="gk_exam_tests[]"]:checked');
            for (var i = 0; i < checkboxes.length; i++) {
                selectedTests.push(checkboxes[i].value);
            }

            if (!title) {
                gkAlert('لطفاً عنوان آزمون کلاسی را وارد فرمایید.', 'عنوان الزامی است', 'warning');
                if (titleEl) titleEl.focus();
                return;
            }
            if (selectedTests.length === 0) {
                gkAlert('لطفاً حداقل یک آزمون/درس را برای این امتحان انتخاب نمایید.', 'انتخاب آزمون', 'warning');
                return;
            }

            gkExamSubmitting = true;
            var $btn = jQuery(btnEl);
            $btn.text('⏳ در حال ایجاد آزمون...').prop('disabled', true);

            jQuery.ajax({
                url: '<?php echo esc_url(home_url('/?gk_ajax=1')); ?>',
                type: 'POST',
                data: {
                    action: 'gk_teacher_create_class_exam',
                    nonce: '<?php echo $nonce; ?>',
                    class_id: <?php echo $class->id; ?>,
                    title: title,
                    validity_hours: validity,
                    test_ids: selectedTests
                },
                dataType: 'json',
                success: function(res) {
                    $btn.text('🚀 ایجاد آزمون کلاسی و دریافت لینک‌ها').prop('disabled', false);
                    gkExamSubmitting = false;
                    if (res.success && res.data) {
                        gkCloseExamModal();
                        try {
                            localStorage.setItem('gk_teacher_active_tab_<?php echo $class->id; ?>', 'tab-exams');
                            window.location.hash = 'tab-exams';
                        } catch(e) {}

                        var modalHtml = '<div style="text-align:center; padding:10px 0;">' +
                            '<p style="font-size:14.5px; font-weight:bold; color:#1e1b4b; margin-bottom:12px;">آزمون کلاسی «' + res.data.title + '» با موفقیت ساخته شد! 📝</p>' +
                            '<div style="background:#f1f5f9; border:1px solid #cbd5e1; padding:10px 12px; border-radius:12px; font-size:12px; word-break:break-all; color:#334155; margin-bottom:10px;">' +
                                '🔗 لینک سالن آزمون:<br><strong>' + res.data.exam_url + '</strong>' +
                            '</div>' +
                        '</div>';

                        gkAlert(modalHtml, 'آزمون فعال شد', 'success', function() {
                            window.location.reload(true);
                        });
                    } else {
                        var err = (res.data && res.data.message) ? res.data.message : (res.data || 'خطا در ایجاد آزمون کلاسی.');
                        gkAlert(err, 'خطا در ایجاد آزمون', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    $btn.text('🚀 ایجاد آزمون کلاسی و دریافت لینک‌ها').prop('disabled', false);
                    gkExamSubmitting = false;
                    console.error('Create exam error:', xhr.responseText);
                    gkAlert('خطای ارتباط با سرور.', 'خطا', 'error');
                }
            });
        }
        function gkOpenExamModal() {
            var m = document.getElementById('gk-new-exam-modal');
            if (m) {
                m.style.display = 'block';
            }
        }
        function gkCloseExamModal() {
            var m = document.getElementById('gk-new-exam-modal');
            if (m) {
                m.style.display = 'none';
            }
        }
        function gkDeleteExam(examId) {
            gkConfirm('آیا از حذف این آزمون کلاسی اطمینان دارید؟', 'حذف آزمون کلاسی', function() {
                jQuery.post('<?php echo $ajax_url; ?>', {
                    action: 'gk_teacher_delete_class_exam',
                    nonce: '<?php echo $nonce; ?>',
                    exam_id: examId
                }, function(res) {
                    if (res.success) {
                        jQuery('#gk-exam-card-' + examId).fadeOut(300, function() {
                            jQuery(this).remove();
                        });
                        gkToast('آزمون کلاسی با موفقیت حذف شد. 🗑️', 'info');
                    } else {
                        gkAlert(res.data || 'خطا در حذف آزمون.', 'خطا', 'error');
                    }
                });
            });
        }
        function gkDeleteLeague(leagueId) {
            gkConfirm('آیا از حذف این مسابقه و لیگ کلاسی اطمینان دارید؟', 'حذف مسابقه', function() {
                jQuery.post('<?php echo $ajax_url; ?>', {
                    action: 'gk_teacher_delete_league',
                    nonce: '<?php echo $nonce; ?>',
                    league_id: leagueId
                }, function(res) {
                    if (res.success) {
                        jQuery('#gk-league-card-' + leagueId).fadeOut(200, function() { jQuery(this).remove(); });
                        gkToast('مسابقه با موفقیت حذف شد. 🗑️', 'info');
                    } else {
                        gkAlert(res.data || 'خطا در حذف مسابقه.', 'خطا', 'error');
                    }
                });
            });
        }

        // توابع باز و بسته کردن کارنامه جامع نوآموز
        function gkOpenStudentReportCard(studentId) {
            var modal = document.getElementById('gk-student-reportcard-modal');
            var body = document.getElementById('gk-reportcard-modal-body');
            var title = document.getElementById('gk-reportcard-modal-title');
            if (!modal || !body) return;

            if (title) title.textContent = '📊 کارنامه جامع و سوابق نوآموز';
            body.innerHTML = '<div style="text-align:center; padding:60px 20px;"><div style="font-size:42px; margin-bottom:12px;">⏳</div><h3 style="color:#4f46e5; margin:0 0 6px 0; font-size:17px; font-weight:900;">در حال دریافت و تنظیم کارنامه جامع نوآموز...</h3><p style="color:#64748b; font-size:13px; margin:0;">سوابق امتحانات، آزمون‌های هوش و بازی‌ها در حال آماده‌سازی است.</p></div>';
            modal.style.display = 'block';

            jQuery.ajax({
                url: '<?php echo esc_url(home_url('/?gk_ajax=1')); ?>',
                type: 'POST',
                data: {
                    action: 'gk_teacher_get_student_reportcard',
                    nonce: '<?php echo $nonce; ?>',
                    student_id: studentId
                },
                dataType: 'json',
                success: function(res) {
                    if (res.success && res.data) {
                        if (title) title.textContent = '📊 کارنامه جامع نوآموز: ' + res.data.student_name;
                        body.innerHTML = res.data.html;
                    } else {
                        var msg = (res.data && res.data.message) ? res.data.message : (res.data || 'خطا در بارگذاری کارنامه.');
                        body.innerHTML = '<div style="text-align:center; padding:40px; color:#b91c1c; font-weight:bold;">' + msg + '</div>';
                    }
                },
                error: function(xhr, status, error) {
                    body.innerHTML = '<div style="text-align:center; padding:40px; color:#b91c1c; font-weight:bold;">خطای ارتباط با سرور در دریافت کارنامه.</div>';
                }
            });
        }

        function gkCloseStudentReportCard() {
            var modal = document.getElementById('gk-student-reportcard-modal');
            if (modal) {
                modal.style.display = 'none';
            }
        }

        // تابع پرینت ایزوله و کامل کارنامه رسمی نوآموز
        function gkPrintReportCard() {
            var printContent = document.getElementById('gkReportCardPrintArea');
            if (!printContent) {
                window.print();
                return;
            }
            
            var iframe = document.createElement('iframe');
            iframe.style.position = 'fixed';
            iframe.style.right = '0';
            iframe.style.bottom = '0';
            iframe.style.width = '0';
            iframe.style.height = '0';
            iframe.style.border = '0';
            document.body.appendChild(iframe);
            
            var doc = iframe.contentWindow.document;
            doc.open();
            doc.write('<!DOCTYPE html><html dir="rtl" lang="fa"><head><meta charset="UTF-8"><title>کارنامه رسمی نوآموز</title>');
            
            var styles = document.querySelectorAll('style, link[rel="stylesheet"]');
            styles.forEach(function(s) {
                doc.write(s.outerHTML);
            });
            
            doc.write('<style>');
            doc.write('@page { size: A4 portrait; margin: 8mm 10mm; }');
            doc.write('body { font-family: "IRANSansXFaNum", Tahoma, sans-serif !important; background: #ffffff !important; margin: 0; padding: 10px; direction: rtl; text-align: right; color: #1e293b; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }');
            doc.write('.gk-rc-btn-print { display: none !important; }');
            doc.write('.gk-reportcard-view { width: 100% !important; max-width: 100% !important; margin: 0 !important; padding: 0 !important; }');
            doc.write('.gk-rc-section, .gk-rc-letterhead, .gk-rc-student-card, .gk-rc-kpi-item { break-inside: avoid !important; page-break-inside: avoid !important; }');
            doc.write('</style>');
            doc.write('</head><body>');
            doc.write('<div class="gk-reportcard-view">');
            doc.write(printContent.innerHTML);
            doc.write('</div>');
            doc.write('</body></html>');
            doc.close();
            
            iframe.contentWindow.focus();
            setTimeout(function() {
                iframe.contentWindow.print();
                setTimeout(function() {
                    if (iframe.parentNode) {
                        iframe.parentNode.removeChild(iframe);
                    }
                }, 2000);
            }, 350);
        }

        // فیلتر جستجوی زنده در تب کارنامه‌ها
        jQuery(document).ready(function($) {
            $('#gk-teacher-search-rc').on('input', function() {
                var val = $(this).val().trim().toLowerCase();
                $('#gk-t-reportcards-tbody tr.gk-t-rc-row').each(function() {
                    var txt = $(this).text().toLowerCase();
                    if (txt.indexOf(val) !== -1) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
}