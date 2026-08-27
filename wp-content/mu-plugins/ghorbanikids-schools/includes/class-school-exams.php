<?php
/**
 * Class GK_School_Exams
 * Handles Custom Class Exams, Curriculum Quizzes Selection, Isolated Exam Arena,
 * Student Evaluation, and Class Exam Recording for Teachers
 */
if (!defined('ABSPATH')) exit;

class GK_School_Exams {

    public static function init() {
        add_shortcode('gk_class_exam_arena', [__CLASS__, 'render_exam_arena']);
        add_action('template_redirect', [__CLASS__, 'disable_caching_on_exam']);
        add_action('wp_ajax_gk_teacher_create_class_exam', [__CLASS__, 'ajax_create_exam']);
        add_action('wp_ajax_nopriv_gk_teacher_create_class_exam', [__CLASS__, 'ajax_create_exam']);
        add_action('wp_ajax_gk_teacher_delete_class_exam', [__CLASS__, 'ajax_delete_exam']);
        add_action('wp_ajax_nopriv_gk_teacher_delete_class_exam', [__CLASS__, 'ajax_delete_exam']);
        add_action('wp_ajax_gk_submit_class_exam_score', [__CLASS__, 'ajax_submit_exam_score']);
        add_action('wp_ajax_nopriv_gk_submit_class_exam_score', [__CLASS__, 'ajax_submit_exam_score']);
        add_action('init', function() {
            if (isset($_REQUEST['action']) && in_array($_REQUEST['action'], ['gk_teacher_create_class_exam', 'gk_teacher_delete_class_exam'])) {
                if ($_REQUEST['action'] === 'gk_teacher_create_class_exam') self::ajax_create_exam();
                if ($_REQUEST['action'] === 'gk_teacher_delete_class_exam') self::ajax_delete_exam();
            }
        });
    }

    public static function enqueue_assets() {
        if (is_page('class-exam')) {
            $css_url = content_url('mu-plugins/ghorbanikids-games-manager/assets/css/gk-curriculum-tests.css');
            $css_file = WPMU_PLUGIN_DIR . '/ghorbanikids-games-manager/assets/css/gk-curriculum-tests.css';
            $ver = file_exists($css_file) ? filemtime($css_file) : time();
            wp_enqueue_style('gk-curriculum-tests', $css_url, [], $ver);
        }
    }

    public static function disable_caching_on_exam() {
        if (is_page('class-exam') || is_page('teacher-class')) {
            nocache_headers();
            if (!defined('DONOTCACHEPAGE')) define('DONOTCACHEPAGE', true);
            if (function_exists('litespeed_control_set_nocache')) {
                do_action('litespeed_control_set_nocache', 'dynamic class exam page');
            }
        }
    }

    public static function render_exam_arena() {
        $code = sanitize_text_field($_GET['code'] ?? '');
        if (empty($code)) {
            return '<div class="gk-alert-box" style="text-align:center; max-width:600px; margin:40px auto; padding:40px 20px; background:#fffbeb; border:2px solid #fde68a; border-radius:24px; direction:rtl; font-family:tahoma, sans-serif;">
                <div style="font-size:48px; margin-bottom:12px;">📝</div>
                <h2 style="margin:0 0 8px 0; color:#92400e;">کد آزمون مشخص نشده است!</h2>
                <p style="color:#b45309; margin:0;">لطفاً از طریق لینک اختصاصی آزمون که توسط مربی ارسال شده وارد شوید.</p>
            </div>';
        }

        global $wpdb;
        $table_exams   = $wpdb->prefix . 'gk_class_exams';
        $table_classes = $wpdb->prefix . 'gk_classes';
        $table_orgs    = $wpdb->prefix . 'gk_organizations';
        $table_students= $wpdb->prefix . 'gk_students';

        $exam = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_exams WHERE exam_code = %s", $code));
        if (!$exam) {
            return '<div class="gk-alert-box" style="text-align:center; max-width:600px; margin:40px auto; padding:30px; background:#fef2f2; border:2px solid #fecaca; border-radius:18px; color:#b91c1c; direction:rtl; font-family:tahoma, sans-serif;">آزمون یا امتحان کلاسی یافت نشد یا حذف شده است.</div>';
        }

        $class = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_classes WHERE id = %d", $exam->class_id));
        $org = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_orgs WHERE id = %d", $exam->org_id));
        $selected_test_ids = json_decode($exam->tests_list, true) ?: [];

        // All tests definitions
        $all_tests = class_exists('GK_Curriculum_Tests') ? GK_Curriculum_Tests::get_tests_data() : [];
        $exam_tests = [];
        foreach ($all_tests as $t) {
            if (in_array($t['id'], $selected_test_ids)) {
                $exam_tests[] = $t;
            }
        }

        // Student resolution
        $is_dedicated_link = !empty($_GET['st_token']);
        $st_token = sanitize_text_field($_GET['st_token'] ?? '');
        $current_student = null;
        if (!empty($st_token)) {
            $current_student = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_students WHERE student_token = %s", $st_token));
            if ($current_student) {
                // Keep token cookie active
                setcookie('gk_active_student_token', $st_token, time() + (86400 * 30), '/');
            }
        } elseif (!empty($_COOKIE['gk_active_student_token'])) {
            // Only auto-bind from cookie if student belongs to THIS specific class
            $cookie_token = sanitize_text_field($_COOKIE['gk_active_student_token']);
            $candidate = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_students WHERE student_token = %s AND class_id = %d", $cookie_token, $exam->class_id));
            if ($candidate) {
                $current_student = $candidate;
                $st_token = $cookie_token;
            }
        }

        $all_class_students = $class ? $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_students WHERE class_id = %d ORDER BY name ASC", $class->id)) : [];

        // Check expiration
        $is_expired = false;
        $time_left_str = 'نامحدود ♾️';
        if (!empty($exam->expires_at)) {
            $diff = strtotime($exam->expires_at) - current_time('timestamp');
            if ($diff <= 0) {
                $is_expired = true;
                $time_left_str = 'پایان یافته ⛔';
            } else {
                $hours = floor($diff / 3600);
                $mins = floor(($diff % 3600) / 60);
                $time_left_str = $hours > 0 ? "⏳ $hours ساعت و $mins دقیقه" : "⏳ $mins دقیقه";
            }
        }

        $css_url = content_url('mu-plugins/ghorbanikids-games-manager/assets/css/gk-curriculum-tests.css');
        $css_file = WPMU_PLUGIN_DIR . '/ghorbanikids-games-manager/assets/css/gk-curriculum-tests.css';
        $ver = file_exists($css_file) ? filemtime($css_file) : time();

        ob_start();
        ?>
        <!-- Embedded Stylesheet to ensure 100% style loading -->
        <link rel="stylesheet" href="<?php echo esc_url($css_url); ?>?ver=<?php echo $ver; ?>">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        
        <style>
            @font-face {
                font-family: 'aviny';
                src: url('/wp-content/uploads/2021/10/aviny-web.woff2') format('woff2'),
                     url('/wp-content/uploads/2021/10/aviny-web.woff') format('woff'),
                     url('/wp-content/uploads/2021/10/aviny.ttf') format('truetype');
                font-weight: normal; font-style: normal; font-display: swap;
            }
            @font-face {
                font-family: 'IRANSansXFaNum';
                src: url('/wp-content/uploads/2021/10/IRANSansXFaNum-Regular.woff2') format('woff2'),
                     url('/wp-content/uploads/2021/10/IRANSansXFaNum-Regular.woff') format('woff'),
                     url('/wp-content/uploads/2021/10/IRANSansXFaNum-Regular.ttf') format('truetype');
                font-weight: normal; font-style: normal; font-display: swap;
            }
            .gk-tests-hub-wrapper, .gk-tests-hub-wrapper * {
                font-family: 'IRANSansXFaNum', 'IRANSansX', 'Vazirmatn', Tahoma, sans-serif !important;
                box-sizing: border-box;
            }
            .gk-exam-title-font {
                font-family: 'aviny', 'IRANSansXFaNum', Tahoma, sans-serif !important;
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

        <div class="gk-tests-hub-wrapper" style="max-width:1150px; margin:20px auto 60px; direction:rtl;">
            
            <!-- 1. Header Banner -->
            <div class="gk-tests-hero" style="background: linear-gradient(135deg, #f0fdf4 0%, #e0f2fe 50%, #faf5ff 100%); border: 2px solid #bae6fd; border-radius: 24px; padding: 34px 20px 28px; text-align: center; margin-bottom: 24px;">
                <span class="gk-tests-badge" style="display:inline-flex; align-items:center; gap:6px; background:#fff; color:#0284c7; font-size:13px; font-weight:800; padding:6px 18px; border-radius:50px; border:1px solid #bae6fd; margin-bottom:12px;">
                    🏫 <?php echo esc_html($org ? $org->name : 'مرکز آموزشی'); ?> | کلاس: <?php echo esc_html($class ? $class->name : 'کلاس من'); ?>
                </span>
                
                <h1 class="gk-tests-title gk-exam-title-font" style="font-size:30px; font-weight:900; color:#0f172a; margin:0 0 10px 0; line-height:1.4;">
                    📝 <?php echo esc_html($exam->title); ?>
                </h1>
                
                <p class="gk-tests-desc" style="font-size:15px; color:#475569; margin:0 auto 16px; max-width:680px; font-weight:700; line-height:1.7;">
                    مهلت شرکت: <strong style="color:#0284c7;"><?php echo esc_html($time_left_str); ?></strong> | تعداد آزمون‌های این امتحان: <strong style="color:#0f172a;"><?php echo count($exam_tests); ?> درس</strong>
                </p>

                <?php if ($is_expired): ?>
                    <div style="display:inline-block; background:#fee2e2; color:#dc2626; border:1.5px solid #fca5a5; padding:8px 22px; border-radius:50px; font-weight:900; font-size:13px; margin-top:8px;">
                        ⛔ مهلت شرکت در این آزمون به پایان رسیده است.
                    </div>
                <?php endif; ?>
            </div>

            <!-- 2. Student Identity or Selection Banner -->
            <?php if ($current_student): ?>
                <div style="background: #ffffff; border: 2px solid #86efac; border-radius: 20px; padding: 16px 22px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; box-shadow: 0 4px 14px rgba(22, 163, 74, 0.1);">
                    <div style="display: flex; align-items: center; gap: 14px;">
                        <div style="font-size: 34px;">👶</div>
                        <div>
                            <div style="font-size: 15.5px; font-weight: 900; color: #166534;">
                                سلام نوآموز عزیز: <strong><?php echo esc_html($current_student->name); ?></strong> (<?php echo esc_html($current_student->age); ?> ساله)
                            </div>
                            <div style="font-size: 12.5px; color: #15803d; font-weight: 700;">
                                پاسخ‌های شما پس از پایان آزمون مستقیماً در کارنامه کلاس برای معلم ارسال می‌شود ✨
                            </div>
                        </div>
                    </div>
                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                        <?php if (!$is_dedicated_link): ?>
                            <button type="button" onclick="gkChangeExamStudent();" style="background:#fff1f2; color:#b91c1c; border:1.5px solid #fecdd3; padding:7px 14px; border-radius:12px; font-weight:900; font-size:12px; cursor:pointer; font-family:inherit;">
                                🔄 تغییر / انتخاب نوآموز دیگر
                            </button>
                        <?php endif; ?>
                        <span style="background: #16a34a; color: #ffffff; padding: 7px 16px; border-radius: 12px; font-weight: 900; font-size: 12.5px;">
                            ⭐ متصل به پنل کلاس
                        </span>
                    </div>
                </div>
                <script>
                function gkChangeExamStudent() {
                    document.cookie = 'gk_active_student_token=; path=/; max-age=0; expires=Thu, 01 Jan 1970 00:00:00 UTC;';
                    var url = new URL(window.location.href);
                    url.searchParams.delete('st_token');
                    window.location.href = url.toString();
                }
                </script>
            <?php elseif (!empty($all_class_students)): ?>
                <!-- Student Picker for Parents opening general link -->
                <div style="background: #fffbeb; border: 2px dashed #f59e0b; border-radius: 20px; padding: 16px 22px; margin-bottom: 24px; box-shadow: 0 4px 14px rgba(245, 158, 11, 0.1);">
                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                        <div style="display:flex; align-items:center; gap:12px;">
                            <div style="font-size:32px;">👶</div>
                            <div>
                                <div style="font-weight:900; color:#92400e; font-size:14.5px;">
                                    اولیای گرامی، لطفاً نام نوآموز خود را انتخاب نمایید:
                                </div>
                                <div style="font-size:12px; color:#b45309; font-weight:700;">
                                    با انتخاب نام، نمرات آزمون به نام فرزندتان در پنل معلم کلاس ثبت خواهد شد.
                                </div>
                            </div>
                        </div>

                        <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                            <select id="gk-exam-select-student" style="padding:9px 14px; border-radius:12px; border:1.5px solid #f59e0b; font-size:13px; background:#fff; font-weight:bold; font-family:inherit;">
                                <option value="">-- انتخاب نام نوآموز --</option>
                                <?php foreach ($all_class_students as $st_item): ?>
                                    <option value="<?php echo esc_attr($st_item->student_token); ?>">
                                        👶 <?php echo esc_html($st_item->name); ?> (<?php echo esc_html($st_item->age); ?> ساله)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" onclick="gkApplyExamStudent();" style="background:#d97706; color:#fff; font-weight:900; padding:9px 18px; border-radius:12px; border:none; cursor:pointer; font-size:13px; font-family:inherit;">
                                تایید و شروع 🚀
                            </button>
                        </div>
                    </div>
                </div>

                <script>
                function gkApplyExamStudent() {
                    var token = document.getElementById('gk-exam-select-student').value;
                    if (!token) {
                        gkAlert('لطفاً نام نوآموز را از لیست انتخاب فرمایید.', 'انتخاب نوآموز', 'warning');
                        return;
                    }
                    document.cookie = 'gk_active_student_token=' + token + '; path=/; max-age=' + (86400 * 30);
                    var url = new URL(window.location.href);
                    url.searchParams.set('st_token', token);
                    window.location.href = url.toString();
                }
                </script>
            <?php endif; ?>

            <!-- 3. Tests Grid for this Exam -->
            <div style="margin-bottom: 40px;">
                <div style="display:flex; align-items:center; gap:8px; margin-bottom: 18px;">
                    <span style="font-size:22px;">📚</span>
                    <h2 style="font-size: 19px; font-weight: 900; color: #0f172a; margin: 0;">
                        آزمون‌های درسی این امتحان کلاسی:
                    </h2>
                </div>

                <div class="gk-tests-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 22px;">
                    <?php foreach ($exam_tests as $t): ?>
                        <div class="gk-test-card" style="background:#fff; border-radius:24px; overflow:hidden; border:2px solid #e2e8f0; display:flex; flex-direction:column; box-shadow:0 10px 25px rgba(15,23,42,0.05);">
                            <div class="gk-test-card-thumb" style="background: <?php echo esc_attr($t['bg']); ?>; height:150px; position:relative; display:flex; align-items:center; justify-content:center;">
                                <span class="gk-test-badge-top" style="position:absolute; top:10px; left:10px; background:rgba(0,0,0,0.4); color:#fff; font-size:0.75rem; font-weight:800; padding:4px 10px; border-radius:10px;">
                                    📊 <?php echo esc_html($t['chapter']); ?>
                                </span>
                                <div class="gk-test-thumb-circle" style="width:68px; height:68px; border-radius:20px; background:rgba(255,255,255,0.25); backdrop-filter:blur(6px); border:2px solid rgba(255,255,255,0.5); display:flex; align-items:center; justify-content:center; font-size:2rem;">
                                    <span><?php echo $t['icon']; ?></span>
                                </div>
                                <span class="gk-test-badge-status" style="position:absolute; bottom:10px; right:10px; background:#16a34a; color:#fff; font-size:0.72rem; font-weight:900; padding:3px 8px; border-radius:8px;">🟢 باز</span>
                            </div>
                            
                            <div class="gk-test-card-body" style="padding:18px 20px; display:flex; flex-direction:column; flex:1; justify-content:space-between;">
                                <div>
                                    <div class="gk-test-meta-row" style="display:flex; gap:6px; margin-bottom:8px; flex-wrap:wrap;">
                                        <span class="gk-test-meta-pill subject" style="background:#e0f2fe; color:#0369a1; font-size:0.75rem; font-weight:800; padding:3px 8px; border-radius:6px;"><?php echo esc_html($t['subject_name']); ?></span>
                                        <span class="gk-test-meta-pill grade" style="background:#f1f5f9; color:#475569; font-size:0.75rem; font-weight:800; padding:3px 8px; border-radius:6px;"><?php echo esc_html($t['grade_name']); ?></span>
                                    </div>
                                    <h3 class="gk-test-title-text" style="font-size:1.08rem; font-weight:900; color:#0f172a; margin:0 0 6px; line-height:1.4;"><?php echo esc_html($t['title']); ?></h3>
                                    <div class="gk-test-desc-text" style="font-size:0.82rem; color:#64748b; line-height:1.5; margin:0 0 14px;"><?php echo esc_html($t['desc']); ?></div>
                                </div>
                                
                                <div>
                                    <div class="gk-test-specs-line" style="display:flex; justify-content:space-between; font-size:0.78rem; font-weight:700; color:#64748b; margin-bottom:14px; padding:6px 10px; background:#f8fafc; border-radius:10px; border:1px dashed #cbd5e1;">
                                        <span>📝 <?php echo $t['questions_count']; ?> سوال دقیق کتاب</span>
                                        <span>⏱️ <?php echo $t['time_min']; ?> دقیقه</span>
                                    </div>
                                    
                                    <?php if ($is_expired): ?>
                                        <button type="button" class="gk-start-test-btn" style="width:100%; padding:10px; background:#94a3b8; color:#fff; border:none; border-radius:12px; font-weight:800; cursor:not-allowed;" disabled>
                                            <span>⛔ مهلت پایان یافته</span>
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="gk-start-test-btn" onclick="GkClassExamArena.startExamQuiz('<?php echo esc_js($t['id']); ?>')" style="width:100%; padding:10px; background:linear-gradient(135deg,#0284c7 0%,#0369a1 100%); color:#fff; border:none; border-radius:12px; font-size:0.92rem; font-weight:800; cursor:pointer; box-shadow:0 4px 12px rgba(2,132,199,0.25);">
                                            <span>🚀 شروع این آزمون</span>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 4. Quiz Modal Player -->
            <div id="gkQuizPlayerModal" class="gk-quiz-player-modal" style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.82); backdrop-filter:blur(8px); z-index:9999999; display:none; align-items:center; justify-content:center; padding:16px;">
                <div class="gk-quiz-card-box" style="background:#fff; width:100%; max-width:640px; border-radius:28px; overflow:hidden; box-shadow:0 25px 60px rgba(0,0,0,0.35); border:3px solid #e2e8f0; display:flex; flex-direction:column; max-height:90vh;">
                    <div class="gk-quiz-header" style="background:linear-gradient(135deg,#0284c7 0%,#0369a1 100%); padding:18px 24px; color:#fff; display:flex; align-items:center; justify-content:space-between;">
                        <h3 class="gk-quiz-header-title" id="gkQuizTitle" style="margin:0; font-size:1.15rem; font-weight:900;">عنوان آزمون</h3>
                        <button type="button" class="gk-quiz-close-btn" onclick="GkClassExamArena.closeQuiz()" style="background:rgba(255,255,255,0.2); border:none; color:#fff; width:32px; height:32px; border-radius:50%; font-size:1.1rem; cursor:pointer;">✕</button>
                    </div>
                    
                    <div class="gk-quiz-progress-track" style="height:7px; background:#e2e8f0; width:100%;">
                        <div id="gkQuizProgressFill" class="gk-quiz-progress-fill" style="height:100%; background:#0284c7; width:0%; transition:width 0.4s ease;"></div>
                    </div>
                    
                    <div class="gk-quiz-body" id="gkQuizBody" style="padding:22px; overflow-y:auto; flex:1;"></div>

                    <div class="gk-quiz-footer" id="gkQuizFooter" style="padding:16px 24px; border-top:2px solid #f1f5f9; display:flex; align-items:center; justify-content:space-between; background:#fff;">
                        <button type="button" id="gkQuizBtnPrev" class="gk-quiz-btn-nav gk-quiz-btn-prev" onclick="GkClassExamArena.prevQuestion()" style="background:#f1f5f9; color:#64748b; border:none; padding:10px 18px; border-radius:12px; font-weight:800; cursor:pointer;">
                            <span>➡️ سوال قبلی</span>
                        </button>
                        <button type="button" id="gkQuizBtnNext" class="gk-quiz-btn-nav gk-quiz-btn-next" onclick="GkClassExamArena.nextQuestion()" style="background:linear-gradient(135deg,#0284c7 0%,#0369a1 100%); color:#fff; border:none; padding:10px 20px; border-radius:12px; font-weight:800; cursor:pointer;">
                            <span>سوال بعدی ⬅️</span>
                        </button>
                    </div>
                </div>
            </div>

        </div>

        <script data-no-optimize="1">
        var GK_EXAM_TESTS = <?php echo json_encode($exam_tests, JSON_UNESCAPED_UNICODE); ?>;
        var GK_EXAM_META = {
            examId: <?php echo intval($exam->id); ?>,
            examCode: '<?php echo esc_js($exam->exam_code); ?>',
            examTitle: '<?php echo esc_js($exam->title); ?>',
            classId: <?php echo intval($exam->class_id); ?>,
            orgId: <?php echo intval($exam->org_id); ?>,
            stToken: '<?php echo esc_js($st_token); ?>',
            stName: '<?php echo esc_js($current_student ? $current_student->name : ''); ?>'
        };

        var GkClassExamArena = {
            tests: GK_EXAM_TESTS,
            meta: GK_EXAM_META,
            currentQuiz: null,
            currentQIndex: 0,
            userAnswers: {},
            shuffledQuestions: [],

            shuffleArray: function(array) {
                var arr = array.slice();
                for (var i = arr.length - 1; i > 0; i--) {
                    var j = Math.floor(Math.random() * (i + 1));
                    var temp = arr[i];
                    arr[i] = arr[j];
                    arr[j] = temp;
                }
                return arr;
            },

            startExamQuiz: function(quizId) {
                var quiz = this.tests.find(function(t) { return t.id === quizId; });
                if (!quiz) return;

                this.currentQuiz = quiz;
                this.currentQIndex = 0;
                this.userAnswers = {};

                var self = this;
                this.shuffledQuestions = this.shuffleArray(quiz.questions).map(function(q) {
                    var optsWithIndex = q.opts.map(function(optText, idx) {
                        return { text: optText, isCorrect: (idx === q.ans) };
                    });
                    var shuffledOpts = self.shuffleArray(optsWithIndex);
                    return {
                        q: q.q,
                        topic: q.topic,
                        options: shuffledOpts
                    };
                });

                document.getElementById('gkQuizTitle').textContent = quiz.title;
                document.getElementById('gkQuizFooter').style.display = 'flex';
                var modal = document.getElementById('gkQuizPlayerModal');
                if (modal) {
                    modal.style.display = 'flex';
                }
                document.body.style.overflow = 'hidden';

                this.renderQuestion();
            },

            closeQuiz: function() {
                var modal = document.getElementById('gkQuizPlayerModal');
                if (modal) {
                    modal.style.display = 'none';
                }
                document.body.style.overflow = '';
            },

            renderQuestion: function() {
                var q = this.shuffledQuestions[this.currentQIndex];
                var total = this.shuffledQuestions.length;
                var progress = ((this.currentQIndex + 1) / total) * 100;

                document.getElementById('gkQuizProgressFill').style.width = progress + '%';

                var prevBtn = document.getElementById('gkQuizBtnPrev');
                var nextBtn = document.getElementById('gkQuizBtnNext');

                if (prevBtn) prevBtn.style.visibility = (this.currentQIndex > 0) ? 'visible' : 'hidden';
                if (nextBtn) {
                    nextBtn.innerHTML = (this.currentQIndex === total - 1) ? '<span>🏁 پایان و ارسال پاسخ‌ها</span>' : '<span>سوال بعدی ⬅️</span>';
                }

                var optLetters = ['الف', 'ب', 'ج', 'د'];
                var selectedIdx = this.userAnswers[this.currentQIndex];

                var html = '<div class="gk-quiz-q-num" style="font-size:0.85rem; font-weight:800; color:#0284c7; margin-bottom:6px;">سوال ' + Number(this.currentQIndex + 1).toLocaleString('fa-IR') + ' از ' + Number(total).toLocaleString('fa-IR') + ' (📌 ' + q.topic + ')</div>';
                html += '<h2 class="gk-quiz-question-text" style="font-size:1.15rem; font-weight:900; color:#0f172a; line-height:1.6; margin-bottom:18px;">' + q.q + '</h2>';
                html += '<div class="gk-quiz-options-list" style="display:flex; flex-direction:column; gap:10px; margin-bottom:18px;">';

                q.options.forEach(function(opt, idx) {
                    var isSel = (selectedIdx === idx);
                    var selStyle = isSel ? 'background:#e0f2fe; border-color:#0284c7; color:#0369a1;' : 'background:#f8fafc; border-color:#e2e8f0; color:#1e293b;';
                    html += '<button type="button" class="gk-quiz-opt-btn" onclick="GkClassExamArena.selectOption(' + idx + ')" style="display:flex; align-items:center; gap:12px; width:100%; padding:12px 16px; border:2px solid; border-radius:16px; font-size:0.98rem; font-weight:800; text-align:right; cursor:pointer; ' + selStyle + '">' +
                        '<span class="gk-quiz-opt-badge" style="width:30px; height:30px; border-radius:8px; background:' + (isSel ? '#0284c7' : '#e2e8f0') + '; color:' + (isSel ? '#fff' : '#475569') + '; display:flex; align-items:center; justify-content:center; font-weight:900;">' + optLetters[idx] + '</span>' +
                        '<span>' + opt.text + '</span>' +
                    '</button>';
                });

                html += '</div>';
                document.getElementById('gkQuizBody').innerHTML = html;
            },

            selectOption: function(optIndex) {
                this.userAnswers[this.currentQIndex] = optIndex;
                this.renderQuestion();
            },

            prevQuestion: function() {
                if (this.currentQIndex > 0) {
                    this.currentQIndex--;
                    this.renderQuestion();
                }
            },

            nextQuestion: function() {
                if (this.userAnswers[this.currentQIndex] === undefined) {
                    gkToast('لطفاً یکی از گزینه‌ها را برای پاسخ انتخاب کنید.', 'warning');
                    return;
                }

                if (this.currentQIndex < this.shuffledQuestions.length - 1) {
                    this.currentQIndex++;
                    this.renderQuestion();
                } else {
                    this.finishQuiz();
                }
            },

            finishQuiz: function() {
                var correctCount = 0;
                var topicStats = {};

                for (var i = 0; i < this.shuffledQuestions.length; i++) {
                    var q = this.shuffledQuestions[i];
                    var chosenOptIndex = this.userAnswers[i];
                    var isCorrect = q.options[chosenOptIndex] ? q.options[chosenOptIndex].isCorrect : false;

                    if (!topicStats[q.topic]) {
                        topicStats[q.topic] = { total: 0, correct: 0 };
                    }
                    topicStats[q.topic].total++;

                    if (isCorrect) {
                        correctCount++;
                        topicStats[q.topic].correct++;
                    }
                }

                var total = this.shuffledQuestions.length;
                var percent = Math.round((correctCount / total) * 100);

                var levelText = '';
                if (percent >= 85) levelText = 'خیلی خوب';
                else if (percent >= 70) levelText = 'خوب';
                else if (percent >= 50) levelText = 'قابل قبول';
                else levelText = 'نیازمند تلاش';

                // Save to database via AJAX silently
                var formData = new FormData();
                formData.append('action', 'gk_submit_class_exam_score');
                formData.append('exam_id', this.meta.examId);
                formData.append('st_token', this.meta.stToken);
                formData.append('quiz_id', this.currentQuiz.id);
                formData.append('quiz_title', this.currentQuiz.title);
                formData.append('score', percent);
                formData.append('level', levelText);
                formData.append('topics', JSON.stringify(topicStats));

                fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
                    method: 'POST',
                    body: formData
                }).then(function(r){ return r.json(); }).then(function(res){
                    console.log('Exam score recorded:', res);
                });

                // Student Friendly Completion Screen (NO SCORE / NO GRADES DISPLAYED)
                var reportHtml = '<div style="text-align:center; padding:20px 10px;">' +
                    '<div style="font-size:4.5rem; margin-bottom:10px;">🎉</div>' +
                    '<h2 style="font-size:1.35rem; font-weight:900; color:#166534; margin:0 0 10px 0;">آفرین قهرمان تلاشگر! 🌸</h2>' +
                    '<p style="font-size:1rem; color:#334155; font-weight:700; max-width:440px; margin:0 auto 20px; line-height:1.8;">' +
                        'پاسخ‌های شما با موفقیت ثبت شد و مستقیماً برای آموزگار مهربانت ارسال گردید.' +
                    '</p>' +
                    '<div style="display:inline-block; background:#dcfce7; color:#15803d; border:1.5px solid #86efac; padding:8px 22px; border-radius:50px; font-weight:900; font-size:0.92rem; margin-bottom:24px;">' +
                        '✅ آزمون این درس با موفقیت به اتمام رسید' +
                    '</div>' +
                    
                    '<div style="display:flex; gap:10px; justify-content:center; flex-wrap:wrap;">' +
                        '<button type="button" onclick="GkClassExamArena.closeQuiz()" style="background:#0284c7; color:#fff; border:none; padding:11px 24px; border-radius:12px; font-weight:900; font-size:0.95rem; cursor:pointer;">📋 بازگشت به لیست آزمون‌های امتحان</button>' +
                    '</div>' +
                '</div>';

                document.getElementById('gkQuizBody').innerHTML = reportHtml;
                document.getElementById('gkQuizFooter').style.display = 'none';
                document.getElementById('gkQuizProgressFill').style.width = '100%';
            }
        };
        </script>
        <?php
        return ob_get_clean();
    }

    public static function ajax_create_exam() {
        check_ajax_referer('gk_school_nonce', 'nonce');
        global $wpdb;

        $class_id       = intval($_POST['class_id'] ?? 0);
        $title          = sanitize_text_field($_POST['title'] ?? '');
        $validity_hours = intval($_POST['validity_hours'] ?? 24);
        $test_ids       = isset($_POST['test_ids']) ? (array)$_POST['test_ids'] : [];

        if (empty($title) || empty($test_ids)) {
            wp_send_json_error('لطفاً عنوان آزمون و حداقل یک درس را انتخاب کنید.');
        }

        $table_classes = $wpdb->prefix . 'gk_classes';
        $class = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_classes WHERE id = %d", $class_id));
        if (!$class) {
            wp_send_json_error('کلاس یافت نشد.');
        }

        $expires_at = null;
        if ($validity_hours > 0) {
            $expires_at = date('Y-m-d H:i:s', time() + ($validity_hours * 3600));
        }

        $table_exams = $wpdb->prefix . 'gk_class_exams';

        // Check if an identical exam was created in the last 10 seconds to prevent double submit
        $recent_exam = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM $table_exams 
            WHERE class_id = %d AND title = %s AND created_at >= %s
            ORDER BY id DESC LIMIT 1
        ", $class_id, $title, date('Y-m-d H:i:s', time() - 10)));

        if ($recent_exam) {
            wp_send_json_success([
                'message'   => 'امتحان کلاسی با موفقیت ایجاد شد! 🎉',
                'title'     => $recent_exam->title,
                'exam_code' => $recent_exam->exam_code,
                'exam_url'  => home_url('/class-exam/?code=' . $recent_exam->exam_code)
            ]);
        }

        $exam_code = 'EX-' . strtoupper(wp_generate_password(8, false));

        $res = $wpdb->insert($table_exams, [
            'exam_code'   => $exam_code,
            'org_id'      => $class->org_id,
            'class_id'    => $class_id,
            'title'       => $title,
            'tests_list'  => json_encode(array_map('sanitize_text_field', $test_ids)),
            'expires_at'  => $expires_at,
            'created_at'  => current_time('mysql')
        ]);

        if ($res === false) {
            wp_send_json_error('خطا در ساخت آزمون در پایگاه داده.');
        }

        wp_send_json_success([
            'message'   => 'امتحان کلاسی با موفقیت ایجاد شد! 🎉',
            'title'     => $title,
            'exam_code' => $exam_code,
            'exam_url'  => home_url('/class-exam/?code=' . $exam_code)
        ]);
    }

    public static function ajax_delete_exam() {
        check_ajax_referer('gk_school_nonce', 'nonce');
        global $wpdb;

        $exam_id = intval($_POST['exam_id'] ?? 0);
        if (!$exam_id) {
            wp_send_json_error('آیدی آزمون نامعتبر است.');
        }

        $table_exams = $wpdb->prefix . 'gk_class_exams';
        $wpdb->delete($table_exams, ['id' => $exam_id]);

        wp_send_json_success(['message' => 'آزمون کلاسی حذف شد.']);
    }

    public static function ajax_submit_exam_score() {
        global $wpdb;
        $exam_id   = intval($_POST['exam_id'] ?? 0);
        $st_token  = sanitize_text_field($_POST['st_token'] ?? ($_COOKIE['gk_active_student_token'] ?? ''));
        $quiz_id   = sanitize_text_field($_POST['quiz_id'] ?? '');
        $quiz_title= sanitize_text_field($_POST['quiz_title'] ?? '');
        $score     = intval($_POST['score'] ?? 0);
        $level     = sanitize_text_field($_POST['level'] ?? '');
        $topics    = sanitize_text_field($_POST['topics'] ?? '{}');

        if (!$exam_id || empty($quiz_id)) {
            wp_send_json_error(['message' => 'اطلاعات ناقص است.']);
        }

        $table_exams = $wpdb->prefix . 'gk_class_exams';
        $exam = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_exams WHERE id = %d", $exam_id));

        $student = null;
        if (!empty($st_token)) {
            $student = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}gk_students WHERE student_token = %s", $st_token));
        }

        $student_id = $student ? $student->id : 0;
        $class_id   = $student ? $student->class_id : ($exam ? $exam->class_id : 0);
        $org_id     = $student ? $student->org_id : ($exam ? $exam->org_id : 0);

        $table_scores = $wpdb->prefix . 'gk_class_exam_scores';
        $wpdb->insert($table_scores, [
            'exam_id'         => $exam_id,
            'student_id'      => $student_id,
            'class_id'        => $class_id,
            'org_id'          => $org_id,
            'quiz_id'         => $quiz_id,
            'score'           => $score,
            'level_text'      => $level,
            'topic_breakdown' => $topics,
            'created_at'      => current_time('mysql')
        ]);

        if ($student) {
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}gk_students SET total_game_score = total_game_score + %d WHERE id = %d",
                $score,
                $student->id
            ));
        }

        wp_send_json_success(['message' => 'نمره آزمون در کارنامه کلاسی ثبت شد.']);
    }
}