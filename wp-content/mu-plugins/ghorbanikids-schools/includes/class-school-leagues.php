<?php
/**
 * Class GK_School_Leagues
 * Handles Custom Class Tournaments, Isolated Game Arena, Total & Best Score Aggregations, and Tournament Leaderboards
 */
if (!defined('ABSPATH')) exit;

class GK_School_Leagues {

    public static function init() {
        add_shortcode('gk_league_arena', [__CLASS__, 'render_league_arena']);
        add_action('template_redirect', [__CLASS__, 'disable_caching_on_league']);
        add_action('wp_ajax_gk_teacher_create_league', [__CLASS__, 'ajax_create_league']);
        add_action('wp_ajax_gk_teacher_delete_league', [__CLASS__, 'ajax_delete_league']);
        add_action('wp_ajax_gk_submit_league_game_score', [__CLASS__, 'ajax_submit_league_score']);
        add_action('wp_ajax_nopriv_gk_submit_league_game_score', [__CLASS__, 'ajax_submit_league_score']);
    }

    public static function disable_caching_on_league() {
        if (is_page('league') || is_page('teacher-class')) {
            nocache_headers();
            if (!defined('DONOTCACHEPAGE')) define('DONOTCACHEPAGE', true);
            if (function_exists('litespeed_control_set_nocache')) {
                do_action('litespeed_control_set_nocache', 'dynamic league page');
            }
        }
    }

    public static function render_league_arena() {
        $code = sanitize_text_field($_GET['code'] ?? '');
        if (empty($code)) {
            return '<div class="gk-alert-box" style="text-align:center; max-width:600px; margin:40px auto; padding:40px 20px; background:#fffbeb; border:2px solid #fde68a; border-radius:24px; direction:rtl;">
                <div style="font-size:48px; margin-bottom:12px;">🏆</div>
                <h2 style="margin:0 0 8px 0; color:#92400e;">کد مسابقه مشخص نشده است!</h2>
                <p style="color:#b45309; margin:0;">لطفاً از طریق لینک اختصاصی مسابقه که توسط مربی ارسال شده وارد شوید.</p>
            </div>';
        }

        global $wpdb;
        $table_leagues = $wpdb->prefix . 'gk_leagues';
        $table_classes = $wpdb->prefix . 'gk_classes';
        $table_orgs    = $wpdb->prefix . 'gk_organizations';
        $table_students= $wpdb->prefix . 'gk_students';
        $table_scores  = $wpdb->prefix . 'gk_league_scores';

        $league = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_leagues WHERE league_code = %s", $code));
        if (!$league) {
            return '<div class="gk-alert-box" style="text-align:center; padding:30px; background:#fef2f2; border:2px solid #fecaca; border-radius:18px; color:#b91c1c; direction:rtl;">مسابقه یا لیگ کلاسی یافت نشد یا حذف شده است.</div>';
        }

        $class = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_classes WHERE id = %d", $league->class_id));
        $org = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_orgs WHERE id = %d", $league->org_id));
        $selected_game_ids = json_decode($league->games_list, true) ?: [];

        // بررسی انقضای مسابقه
        $is_expired = false;
        $expiry_text = '⏳ بدون محدودیت زمانی';
        if (!empty($league->expires_at)) {
            $expiry_time = strtotime($league->expires_at);
            if (time() > $expiry_time) {
                $is_expired = true;
                $expiry_text = '🔒 مهلت مسابقه به پایان رسیده است';
            } else {
                $hours_left = ceil(($expiry_time - time()) / 3600);
                $expiry_text = "⏳ $hours_left ساعت تا پایان مسابقه";
            }
        }

        // All students in this class
        $all_class_students = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_students WHERE class_id = %d ORDER BY name ASC", $league->class_id));

        // Fetch selected games posts
        $games_query = !empty($selected_game_ids) ? get_posts([
            'post_type' => 'gk_game',
            'post__in'  => $selected_game_ids,
            'numberposts' => 50
        ]) : [];

        // Identify Student
        $st_token = $_GET['st_token'] ?? ($_COOKIE['gk_active_student_token'] ?? null);
        $student = null;
        if ($st_token) {
            $student = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_students WHERE student_token = %s AND class_id = %d", $st_token, $league->class_id));
        }

        // Leaderboard for this specific league (both MAX best score and SUM total score)
        $league_rankings = $wpdb->get_results($wpdb->prepare("
            SELECT 
                s.id, 
                s.name, 
                s.age, 
                s.student_token,
                s.total_game_score,
                MAX(ls.score) as best_score, 
                COALESCE(SUM(ls.score), 0) as total_league_score,
                COUNT(ls.id) as attempts_count
            FROM $table_students s
            LEFT JOIN $table_scores ls ON s.id = ls.student_id AND ls.league_id = %d
            WHERE s.class_id = %d
            GROUP BY s.id, s.name, s.age, s.student_token, s.total_game_score
            ORDER BY best_score DESC, total_league_score DESC, attempts_count DESC, s.id ASC
        ", $league->id, $league->class_id));

        // Student's specific stats in this league
        $student_best_score = 0;
        $student_total_league_score = 0;
        $student_rank = 0;
        $student_attempts = 0;

        if ($student) {
            foreach ($league_rankings as $idx => $r) {
                if ($r->id == $student->id) {
                    $student_rank = $idx + 1;
                    $student_best_score = intval($r->best_score);
                    $student_total_league_score = intval($r->total_league_score);
                    $student_attempts = intval($r->attempts_count);
                    break;
                }
            }
        }

        ob_start();
        ?>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        </style>
        <script>
        window.gkAlert = function(msg, title, icon, onOk) {
            if (typeof title === 'undefined') title = 'پیام سامانه';
            if (typeof icon === 'undefined') icon = 'info';
            if (typeof Swal !== 'undefined') {
                return Swal.fire({
                    title: title,
                    html: (typeof msg === 'string') ? msg.replace(/\n/g, '<br>') : msg,
                    icon: icon,
                    confirmButtonText: 'متوجه شدم ✨',
                    customClass: { popup: 'gk-swal-popup', title: 'gk-swal-title', htmlContainer: 'gk-swal-html', confirmButton: 'gk-swal-confirm' },
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
                    customClass: { popup: 'gk-swal-toast' }
                });
            } else {
                window.alert(msg);
            }
        };
        window.alert = function(msg) { window.gkAlert(msg); };
        </script>

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

            .gk-arena-wrap {
                direction: rtl !important;
                text-align: right !important;
                font-family: inherit;
                max-width: 1120px;
                margin: 25px auto 60px auto;
                padding: 0 15px;
                box-sizing: border-box;
            }
            .gk-arena-hero {
                background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%);
                color: #ffffff;
                border-radius: 26px;
                padding: 26px 30px;
                margin-bottom: 24px;
                box-shadow: 0 14px 35px rgba(49, 46, 129, 0.25);
                border: 1.5px solid rgba(255, 255, 255, 0.25);
            }
            .gk-arena-badge {
                background: rgba(255, 255, 255, 0.2);
                padding: 4px 14px;
                border-radius: 20px;
                font-size: 12.5px;
                font-weight: 800;
                border: 1px solid rgba(255,255,255,0.3);
            }
            .gk-student-stat-strip {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
                gap: 12px;
                margin-top: 20px;
                padding-top: 18px;
                border-top: 1.5px dashed rgba(255, 255, 255, 0.25);
            }
            .gk-stat-pill {
                background: rgba(255, 255, 255, 0.12);
                border: 1px solid rgba(255, 255, 255, 0.2);
                border-radius: 16px;
                padding: 12px 16px;
                text-align: center;
                backdrop-filter: blur(5px);
            }
            .gk-stat-pill-label {
                font-size: 12px;
                opacity: 0.85;
                margin-bottom: 4px;
            }
            .gk-stat-pill-val {
                font-size: 20px;
                font-weight: 900;
                color: #fef08a;
            }
            .gk-select-student-box {
                background: #fffbeb;
                border: 2px solid #fde68a;
                border-radius: 20px;
                padding: 18px 22px;
                margin-bottom: 24px;
                box-shadow: 0 4px 15px rgba(245, 158, 11, 0.08);
            }
            .gk-arena-games-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 20px;
                margin-bottom: 35px;
            }
            .gk-arena-game-card {
                background: #ffffff;
                border: 2.5px solid #e0e7ff;
                border-radius: 22px;
                padding: 22px;
                text-align: center;
                box-shadow: 0 8px 24px rgba(99, 102, 241, 0.06);
                transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
            }
            .gk-arena-game-card:hover {
                transform: translateY(-4px);
                border-color: #6366f1;
                box-shadow: 0 12px 30px rgba(99, 102, 241, 0.15);
            }
            .gk-arena-play-btn {
                background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                color: #ffffff !important;
                font-size: 15px;
                font-weight: 900;
                padding: 13px 0;
                border-radius: 14px;
                display: block;
                text-decoration: none !important;
                margin-top: 14px;
                box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);
                transition: transform 0.2s;
            }
            .gk-arena-play-btn:hover {
                transform: scale(1.03);
            }
        </style>

        <div class="gk-arena-wrap">
            
            <!-- بنر هدر چالش و مسابقه همراه با آمار امتیازات دانش‌آموز -->
            <div class="gk-arena-hero">
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px;">
                    <div>
                        <div style="font-size:13.5px; margin-bottom:6px; opacity:0.9;">
                            🏆 سالن چالش و مسابقه کلاسی
                        </div>
                        <h1 style="font-size:23px; font-weight:900; margin:0 0 8px 0; color:#fff;">
                            <?php echo esc_html($league->title); ?>
                        </h1>
                        <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                            <span class="gk-arena-badge">🏫 کلاس: <?php echo esc_html($class->name); ?></span>
                            <span class="gk-arena-badge">🏢 <?php echo esc_html($org->name); ?></span>
                            <span class="gk-arena-badge" style="background:<?php echo $is_expired ? '#ef4444' : '#10b981'; ?>;">
                                <?php echo esc_html($expiry_text); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <?php if ($student): ?>
                    <!-- نوار وضعیت امتیازات اختصاصی کودک در این مسابقه -->
                    <div class="gk-student-stat-strip">
                        <div class="gk-stat-pill">
                            <div class="gk-stat-pill-label">👶 نوآموز شرکت‌کننده:</div>
                            <div class="gk-stat-pill-val" style="font-size:17px; color:#fff;">
                                <?php echo esc_html($student->name); ?>
                            </div>
                        </div>
                        <div class="gk-stat-pill">
                            <div class="gk-stat-pill-label">🥇 رتبه فعلی در مسابقه:</div>
                            <div class="gk-stat-pill-val">
                                <?php echo $student_rank > 0 ? "رتبه $student_rank" : "در انتظار"; ?>
                            </div>
                        </div>
                        <div class="gk-stat-pill">
                            <div class="gk-stat-pill-label">🏆 بهترین رکورد مسابقه:</div>
                            <div class="gk-stat-pill-val">
                                <?php echo number_format($student_best_score); ?>
                            </div>
                        </div>
                        <div class="gk-stat-pill">
                            <div class="gk-stat-pill-label">🌟 مجموع امتیازات مسابقه:</div>
                            <div class="gk-stat-pill-val" style="color:#86efac;">
                                <?php echo number_format($student_total_league_score); ?>
                            </div>
                        </div>
                        <div class="gk-stat-pill">
                            <div class="gk-stat-pill-label">🔄 دفعات شرکت:</div>
                            <div class="gk-stat-pill-val" style="font-size:16px;">
                                <?php echo $student_attempts; ?> مرتبه
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- در صورت اتمام مهلت مسابقه -->
            <?php if ($is_expired): ?>
                <div style="background:#fef2f2; border:2px solid #fecaca; border-radius:20px; padding:22px; margin-bottom:24px; text-align:center;">
                    <div style="font-size:36px; margin-bottom:6px;">🔒</div>
                    <h3 style="margin:0 0 6px 0; color:#991b1b; font-size:17px; font-weight:900;">مهلت شرکت در این مسابقه به پایان رسیده است!</h3>
                    <p style="margin:0; color:#b91c1c; font-size:13px;">امکان ثبت رکورد جدید بسته شده است. نتایج نهایی در جدول زیر قابل مشاهده است.</p>
                </div>
            <?php endif; ?>

            <!-- انتخاب نوآموز در صورت ورود با لینک عمومی -->
            <?php if (!$student && !$is_expired): ?>
                <div class="gk-select-student-box">
                    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
                        <div>
                            <h3 style="margin:0 0 4px 0; font-size:15px; font-weight:900; color:#92400e;">
                                👶 لطفاً نام نوآموز خود را برای ثبت رکورد انتخاب کنید:
                            </h3>
                            <p style="margin:0; font-size:12.5px; color:#b45309;">
                                برای اینکه امتیاز مسابقه به نام فرزند شما ثبت شود، نام او را از لیست زیر انتخاب کنید:
                            </p>
                        </div>
                        <div style="display:flex; gap:8px; align-items:center;">
                            <select id="gk-arena-select-student" style="padding:9px 14px; border-radius:12px; border:1.5px solid #f59e0b; font-size:13px; background:#fff; font-weight:bold;">
                                <option value="">-- انتخاب نام نوآموز --</option>
                                <?php foreach ($all_class_students as $st_item): ?>
                                    <option value="<?php echo esc_attr($st_item->student_token); ?>">
                                        👶 <?php echo esc_html($st_item->name); ?> (<?php echo esc_html($st_item->age); ?> ساله)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" onclick="gkApplyArenaStudent();" style="background:#d97706; color:#fff; font-weight:900; padding:9px 16px; border-radius:12px; border:none; cursor:pointer;">
                                تایید و شروع 🚀
                            </button>
                        </div>
                    </div>
                </div>
                <script>
                function gkApplyArenaStudent() {
                    var token = document.getElementById('gk-arena-select-student').value;
                    if (!token) {
                        gkAlert('لطفاً نام نوآموز را از لیست انتخاب فرمایید.', 'انتخاب نوآموز', 'warning');
                        return;
                    }
                    var url = new URL(window.location.href);
                    url.searchParams.set('st_token', token);
                    window.location.href = url.toString();
                }
                </script>
            <?php endif; ?>

            <!-- بازی‌های منتخب این مسابقه -->
            <div style="margin-bottom:18px;">
                <h2 style="font-size:18px; font-weight:900; color:#1e1b4b; margin:0 0 6px 0;">
                    🎮 بازی‌های منتخب این مسابقه:
                </h2>
                <p style="color:#64748b; font-size:13px; margin:0;">
                    فقط امتیازات کسب‌شده در این بازی‌ها برای مسابقه کلاسی شما ثبت و محاسبه می‌شود.
                </p>
            </div>

            <div class="gk-arena-games-grid">
                <?php foreach ($games_query as $g): 
                    $play_url = get_permalink($g->ID);
                    if ($student) {
                        $play_url = add_query_arg(['st_token' => $student->student_token, 'league_code' => $league->league_code], $play_url);
                    } else {
                        $play_url = add_query_arg(['league_code' => $league->league_code], $play_url);
                    }
                ?>
                    <div class="gk-arena-game-card" style="display:flex; flex-direction:column; align-items:center; text-align:center;">
                        <?php $g_icon = gk_get_game_icon($g->ID); ?>
                        <div style="width:72px; height:72px; border-radius:18px; background:<?php echo esc_attr($g_icon['bg']); ?>; display:flex; align-items:center; justify-content:center; margin:0 auto 14px auto; box-shadow:0 8px 20px rgba(0,0,0,0.08);">
                            <div style="width:44px; height:44px; display:flex; align-items:center; justify-content:center;">
                                <?php echo $g_icon['svg']; ?>
                            </div>
                        </div>
                        <h3 style="font-size:17px; font-weight:900; color:#1e293b; margin:0 0 8px 0;">
                            <?php echo esc_html($g->post_title); ?>
                        </h3>
                        <?php if ($is_expired): ?>
                            <button disabled style="width:100%; background:#cbd5e1; color:#64748b; font-size:14px; font-weight:bold; padding:12px 0; border-radius:14px; border:none; margin-top:14px; cursor:not-allowed;">
                                🔒 مسابقه به پایان رسیده است
                            </button>
                        <?php else: ?>
                            <a href="<?php echo esc_url($play_url); ?>" class="gk-arena-play-btn">
                                🚀 ورود و انجام بازی مسابقه
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- جدول زنده رتبه‌بندی مسابقه شامل بهترین رکورد و مجموع امتیازات -->
            <div style="background:#ffffff; border:2px solid #e0e7ff; border-radius:24px; padding:24px; box-shadow:0 8px 25px rgba(0,0,0,0.03);">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:10px;">
                    <h3 style="font-size:17px; font-weight:900; margin:0; color:#1e1b4b;">
                        📊 تابلوی رتبه‌بندی و مجموع امتیازات مسابقه «<?php echo esc_html($league->title); ?>»:
                    </h3>
                    <button type="button" onclick="location.reload();" style="background:#f1f5f9; border:1px solid #cbd5e1; border-radius:10px; padding:6px 12px; font-size:12px; font-weight:bold; cursor:pointer;">
                        🔄 بروزرسانی جدول
                    </button>
                </div>

                <div style="overflow-x:auto;">
                    <table style="width:100%; border-collapse:separate; border-spacing:0 8px;">
                        <thead>
                            <tr style="color:#64748b; font-size:13px;">
                                <th style="padding:8px 12px;">رتبه</th>
                                <th style="padding:8px 12px;">نام نوآموز</th>
                                <th style="padding:8px 12px;">🏆 بهترین رکورد</th>
                                <th style="padding:8px 12px;">🌟 مجموع امتیازات مسابقه</th>
                                <th style="padding:8px 12px;">تعداد دفعات بازی</th>
                                <th style="padding:8px 12px;">وضعیت</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($league_rankings as $idx => $r): 
                                $rank = $idx + 1;
                                $medals = [1 => '🥇', 2 => '🥈', 3 => '🥉'];
                                $medal = $medals[$rank] ?? "#$rank";
                                $has_played = intval($r->attempts_count) > 0;
                                $is_current = ($student && $student->id == $r->id);
                                $row_bg = $is_current ? '#eff6ff' : '#f8fafc';
                                $border_color = $is_current ? '#93c5fd' : '#e2e8f0';
                            ?>
                                <tr style="background:<?php echo $row_bg; ?>; font-size:13.5px;">
                                    <td style="padding:12px; font-weight:bold; border-right:2px solid <?php echo $border_color; ?>; border-top-right-radius:12px; border-bottom-right-radius:12px;">
                                        <?php echo $medal; ?>
                                    </td>
                                    <td style="padding:12px; font-weight:900; color:#1e293b;">
                                        👶 <?php echo esc_html($r->name); ?>
                                        <?php if ($is_current): ?>
                                            <span style="background:#dbeafe; color:#1d4ed8; font-size:11px; padding:2px 6px; border-radius:6px; margin-right:4px;">(شما)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding:12px;">
                                        <?php if ($has_played && intval($r->best_score) > 0): ?>
                                            <span style="background:#ecfdf5; color:#059669; border:1px solid #a7f3d0; padding:4px 10px; border-radius:8px; font-weight:900;">
                                                🏆 <?php echo number_format(intval($r->best_score)); ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color:#94a3b8; font-size:12px;">بدون رکورد</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding:12px;">
                                        <?php if ($has_played && intval($r->total_league_score) > 0): ?>
                                            <span style="background:#f5f3ff; color:#7c3aed; border:1px solid #ddd6fe; padding:4px 10px; border-radius:8px; font-weight:900;">
                                                🌟 <?php echo number_format(intval($r->total_league_score)); ?> امتیاز
                                            </span>
                                        <?php else: ?>
                                            <span style="color:#94a3b8; font-size:12px;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding:12px; color:#475569;">
                                        <?php echo intval($r->attempts_count); ?> بار
                                    </td>
                                    <td style="padding:12px; border-left:2px solid <?php echo $border_color; ?>; border-top-left-radius:12px; border-bottom-left-radius:12px;">
                                        <?php if ($has_played): ?>
                                            <span style="color:#059669; font-weight:800;">✅ شرکت کرده</span>
                                        <?php else: ?>
                                            <span style="color:#d97706; font-weight:800;">⏳ در انتظار مسابقه</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
        <?php
        return ob_get_clean();
    }

    public static function ajax_create_league() {
        check_ajax_referer('gk_school_nonce', 'nonce');
        $class_id = intval($_POST['class_id']);
        $title = sanitize_text_field($_POST['title']);
        $validity_hours = intval($_POST['validity_hours'] ?? 24);
        $game_ids = isset($_POST['game_ids']) ? array_map('intval', (array)$_POST['game_ids']) : [];

        if (empty($title) || empty($game_ids)) {
            wp_send_json_error('لطفاً عنوان لیگ و حداقل یک بازی را انتخاب کنید.');
        }

        global $wpdb;
        $table_classes = $wpdb->prefix . 'gk_classes';
        $class = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_classes WHERE id = %d", $class_id));

        if (!$class) wp_send_json_error('کلاس یافت نشد.');

        $table_leagues = $wpdb->prefix . 'gk_leagues';
        $league_code = 'LG-' . strtoupper(wp_generate_password(8, false));
        
        $expires_at = null;
        if ($validity_hours > 0) {
            $expires_at = date('Y-m-d H:i:s', time() + ($validity_hours * 3600));
        }

        $wpdb->insert($table_leagues, [
            'org_id'      => $class->org_id,
            'class_id'    => $class_id,
            'title'       => $title,
            'games_list'  => json_encode($game_ids),
            'league_code' => $league_code,
            'status'      => 'active',
            'expires_at'  => $expires_at,
            'created_at'  => current_time('mysql')
        ], ['%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s']);

        $league_id = $wpdb->insert_id;
        $league_url = home_url('/league/?code=' . $league_code);

        wp_send_json_success([
            'id'          => $league_id,
            'title'       => $title,
            'league_code' => $league_code,
            'league_url'  => $league_url,
            'games_count' => count($game_ids)
        ]);
    }

    public static function ajax_delete_league() {
        check_ajax_referer('gk_school_nonce', 'nonce');
        $league_id = intval($_POST['league_id']);

        global $wpdb;
        $table_leagues = $wpdb->prefix . 'gk_leagues';
        $table_scores  = $wpdb->prefix . 'gk_league_scores';

        $wpdb->delete($table_scores, ['league_id' => $league_id], ['%d']);
        $wpdb->delete($table_leagues, ['id' => $league_id], ['%d']);

        wp_send_json_success('لیگ حذف شد.');
    }

    public static function ajax_submit_league_score() {
        $token = sanitize_text_field($_POST['student_token'] ?? '');
        $league_code = sanitize_text_field($_POST['league_code'] ?? '');
        $game_id = intval($_POST['game_id'] ?? 0);
        $score = intval($_POST['score'] ?? 0);

        if (!$token || !$league_code || $score <= 0) {
            wp_send_json_error('Invalid payload');
        }

        global $wpdb;
        $table_leagues = $wpdb->prefix . 'gk_leagues';
        $table_students = $wpdb->prefix . 'gk_students';
        $table_scores   = $wpdb->prefix . 'gk_league_scores';

        $league = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_leagues WHERE league_code = %s", $league_code));
        $student = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_students WHERE student_token = %s", $token));

        if ($league && $student) {
            if (!empty($league->expires_at) && time() > strtotime($league->expires_at)) {
                wp_send_json_error('Tournament expired');
            }

            $wpdb->insert($table_scores, [
                'league_id'  => $league->id,
                'student_id' => $student->id,
                'game_id'    => $game_id,
                'score'      => $score,
                'created_at' => current_time('mysql')
            ], ['%d', '%d', '%d', '%d', '%s']);
        }

        wp_send_json_success('League score logged.');
    }
}