<?php
/**
 * Class GK_School_Reports
 * Handles School Branded Reports, Token Auto-Fill in Assessments, and Student Result Mapping
 */
if (!defined('ABSPATH')) exit;

class GK_School_Reports {

    public static function init() {
        add_action('wp_head', [__CLASS__, 'inject_assessment_auto_fill']);
        add_action('wp_footer', [__CLASS__, 'inject_test_links_auto_appender']);
        add_filter('gk_assessment_user_can_access', [__CLASS__, 'grant_student_vip_access'], 10, 2);
        add_action('wp_ajax_gk_submit_assessment', [__CLASS__, 'intercept_assessment_submission'], 1);
        add_action('wp_ajax_nopriv_gk_submit_assessment', [__CLASS__, 'intercept_assessment_submission'], 1);
        add_filter('gk_report_header_branding_html', [__CLASS__, 'render_school_branding_on_report'], 10, 2);
    }

    public static function get_active_student() {
        $token = $_GET['st_token'] ?? ($_COOKIE['gk_active_student_token'] ?? null);
        if (!$token) return null;

        global $wpdb;
        $table_students = $wpdb->prefix . 'gk_students';
        $table_classes  = $wpdb->prefix . 'gk_classes';
        $table_orgs     = $wpdb->prefix . 'gk_organizations';

        return $wpdb->get_row($wpdb->prepare("
            SELECT s.*, c.name as class_name, o.name as org_name, o.logo_url, o.city, o.phone, o.expires_at, o.status as org_status
            FROM $table_students s
            LEFT JOIN $table_classes c ON s.class_id = c.id
            LEFT JOIN $table_orgs o ON s.org_id = o.id
            WHERE s.student_token = %s
            LIMIT 1
        ", $token));
    }

    public static function grant_student_vip_access($can_access, $slug) {
        $student = self::get_active_student();
        if ($student && $student->org_status === 'active') {
            return true; // Full VIP access to all 6 assessments!
        }
        return $can_access;
    }

    public static function inject_assessment_auto_fill() {
        $student = self::get_active_student();
        if (!$student) return;

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

            .gk-school-prefill-badge {
                background: linear-gradient(135deg, #ede9fe 0%, #f5f3ff 100%);
                border: 1.5px solid #c4b5fd;
                color: #5b21b6;
                padding: 8px 16px;
                border-radius: 14px;
                font-size: 13px;
                font-weight: 800;
                margin-bottom: 14px;
                display: flex;
                align-items: center;
                gap: 8px;
            }
        </style>
        <script>
        jQuery(document).ready(function($) {
            var stName = '<?php echo esc_js($student->name); ?>';
            var stAge = '<?php echo intval($student->age); ?>';
            var stOrg = '<?php echo esc_js($student->org_name); ?>';
            var stClass = '<?php echo esc_js($student->class_name); ?>';
            var stToken = '<?php echo esc_js($student->student_token); ?>';

            // پیش‌پر کردن خودکار نام و سن کودک
            if ($('#gk-child-name').length) {
                $('#gk-child-name').val(stName).attr('readonly', true).css({
                    'background': '#f8fafc',
                    'font-weight': 'bold',
                    'color': '#1e293b'
                });

                if ($('#gk-child-age').length) {
                    $('#gk-child-age').val(stAge);
                }

                // اضافه کردن نشان مهدکودک بالای فرم
                if (!$('.gk-school-prefill-badge').length) {
                    $('#gk-child-name').closest('.gk-input-group').before(`
                        <div class="gk-school-prefill-badge">
                            <span>🏢 ارزیابی اختصاصی: <strong>${stOrg}</strong> (کلاس: ${stClass})</span>
                        </div>
                    `);
                }
            }

            // ضمیمه کردن توکن به درخواست ارسال آزمون
            $.ajaxPrefilter(function(options, originalOptions, jqXHR) {
                if (options.data && typeof options.data === 'string' && options.data.indexOf('action=gk_submit_assessment') !== -1) {
                    options.data += '&st_token=' + encodeURIComponent(stToken);
                }
            });

            // وقتی کارنامه در صفحه آزمون لود شد، توکن کودک را به دکمه‌های بازی‌های پیشنهادی متصل کن
            $(document).ajaxSuccess(function(event, xhr, settings) {
                if (settings.data && settings.data.indexOf('action=gk_submit_assessment') !== -1) {
                    setTimeout(function() {
                        $('#gk-report-container a[href*="/game/"], #gk-report-container a[href*="/games/"]').each(function() {
                            var href = $(this).attr('href');
                            if (href && href.indexOf('st_token=') === -1) {
                                var sep = (href.indexOf('?') !== -1) ? '&' : '?';
                                $(this).attr('href', href + sep + 'st_token=' + stToken);
                            }
                        });
                    }, 200);
                }
            });
        });
        </script>
        <?php
    }

    public static function inject_test_links_auto_appender() {
        $student = self::get_active_student();
        if (!$student) return;

        $token = esc_js($student->student_token);
        ?>
        <script>
        jQuery(document).ready(function($) {
            var stToken = '<?php echo $token; ?>';
            // اتصال خودکار توکن به تمام لینک‌های تست‌ها در صفحه کاتالوگ
            $('a[href*="/tests/"]').each(function() {
                var href = $(this).attr('href');
                if (href && href.indexOf('st_token=') === -1 && href.indexOf('#') === -1 && href.indexOf('javascript') === -1) {
                    var sep = (href.indexOf('?') !== -1) ? '&' : '?';
                    $(this).attr('href', href + sep + 'st_token=' + stToken);
                }
            });
        });
        </script>
        <?php
    }

    public static function intercept_assessment_submission() {
        $token = sanitize_text_field($_POST['st_token'] ?? ($_GET['st_token'] ?? ($_COOKIE['gk_active_student_token'] ?? '')));
        if (empty($token)) return;

        global $wpdb;
        $table_students = $wpdb->prefix . 'gk_students';
        $student = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_students WHERE student_token = %s", $token));

        if ($student) {
            // وقتی آزمون سابمیت شد، در جدول ارزیابی‌های مدرسه ثبت شود
            add_action('gk_assessment_saved', function($result_id, $slug, $scores_json) use ($wpdb, $student) {
                $table_student_assessments = $wpdb->prefix . 'gk_student_assessments';
                $wpdb->insert($table_student_assessments, [
                    'student_id'      => $student->id,
                    'org_id'          => $student->org_id,
                    'class_id'        => $student->class_id,
                    'assessment_slug' => $slug,
                    'result_id'       => $result_id,
                    'scores_data'     => $scores_json,
                    'created_at'      => current_time('mysql')
                ]);
            }, 10, 3);
        }
    }

    public static function render_school_branding_on_report($html, $result_id) {
        global $wpdb;
        $table_student_assessments = $wpdb->prefix . 'gk_student_assessments';
        $table_orgs = $wpdb->prefix . 'gk_organizations';
        $table_classes = $wpdb->prefix . 'gk_classes';

        $record = $wpdb->get_row($wpdb->prepare("
            SELECT sa.*, o.name as org_name, o.logo_url, o.phone, o.city, c.name as class_name
            FROM $table_student_assessments sa
            JOIN $table_orgs o ON sa.org_id = o.id
            LEFT JOIN $table_classes c ON sa.class_id = c.id
            WHERE sa.result_id = %d
            LIMIT 1
        ", $result_id));

        if (!$record) return $html;

        ob_start();
        ?>
        <div class="gk-school-branded-letterhead" style="background:#ffffff; border:2px solid #cbd5e1; border-radius:20px; padding:20px 24px; margin-bottom:24px; display:flex; justify-content:space-between; align-items:center; box-shadow:0 4px 15px rgba(0,0,0,0.04); direction:rtl;">
            <div style="display:flex; align-items:center; gap:16px;">
                <?php if (!empty($record->logo_url)): ?>
                    <img src="<?php echo esc_url($record->logo_url); ?>" alt="<?php echo esc_attr($record->org_name); ?>" style="width:65px; height:65px; border-radius:14px; object-fit:cover; border:1.5px solid #e2e8f0;">
                <?php else: ?>
                    <div style="width:60px; height:60px; background:#f1f5f9; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:28px;">🏢</div>
                <?php endif; ?>
                <div>
                    <h2 style="margin:0 0 4px 0; font-size:18px; color:#1e293b; font-weight:900;">کارنامه ارزیابی و استعدادیابی رسمی</h2>
                    <div style="font-size:13.5px; color:#64748b; font-weight:800;">
                        🏢 صادر شده توسط: <span style="color:#4f46e5;"><?php echo esc_html($record->org_name); ?></span>
                        <?php if (!empty($record->class_name)): ?>
                            | 🏫 کلاس: <strong><?php echo esc_html($record->class_name); ?></strong>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div style="text-align:left; font-size:12px; color:#64748b;">
                <?php if (!empty($record->phone)): ?>
                    <div>📞 تلفن: <?php echo esc_html($record->phone); ?></div>
                <?php endif; ?>
                <div>📅 تاریخ صدور: <?php echo date_i18n('j F Y'); ?></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}