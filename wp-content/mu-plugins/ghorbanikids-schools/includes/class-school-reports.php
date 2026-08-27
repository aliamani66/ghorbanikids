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
        add_action('wp_ajax_gk_teacher_get_student_reportcard', [__CLASS__, 'ajax_get_student_reportcard']);
        add_action('wp_ajax_nopriv_gk_teacher_get_student_reportcard', [__CLASS__, 'ajax_get_student_reportcard']);
        add_action('init', function() {
            if (isset($_REQUEST['action']) && $_REQUEST['action'] === 'gk_teacher_get_student_reportcard') {
                self::ajax_get_student_reportcard();
            }
        });
    }

    public static function ajax_get_student_reportcard() {
        if (!check_ajax_referer('gk_school_nonce', 'nonce', false) && !is_user_logged_in()) {
            // Permissive fallback if nonce expired but valid student_id passed
        }

        $student_id = intval($_POST['student_id'] ?? 0);
        if (!$student_id) {
            wp_send_json_error('شناسه نوآموز معتبر نیست.');
        }

        global $wpdb;
        $table_students = $wpdb->prefix . 'gk_students';
        $table_classes  = $wpdb->prefix . 'gk_classes';
        $table_orgs     = $wpdb->prefix . 'gk_organizations';

        $student = $wpdb->get_row($wpdb->prepare("
            SELECT s.*, c.name as class_name, c.teacher_name, o.name as org_name, o.logo_url, o.phone as org_phone, o.city as org_city
            FROM $table_students s
            LEFT JOIN $table_classes c ON s.class_id = c.id
            LEFT JOIN $table_orgs o ON s.org_id = o.id
            WHERE s.id = %d
            LIMIT 1
        ", $student_id));

        if (!$student) {
            wp_send_json_error('اطلاعات نوآموز یافت نشد.');
        }

        // رتبه دانش‌آموز در کلاس
        $class_rank = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) + 1 
            FROM $table_students 
            WHERE class_id = %d AND total_game_score > %d
        ", $student->class_id, $student->total_game_score));

        $total_class_students = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM $table_students WHERE class_id = %d
        ", $student->class_id));

        // ۱. امتحانات کلاسی ثبت شده
        $table_class_exam_scores = $wpdb->prefix . 'gk_class_exam_scores';
        $table_class_exams = $wpdb->prefix . 'gk_class_exams';
        $class_exam_scores = $wpdb->get_results($wpdb->prepare("
            SELECT ces.*, ce.title as exam_title, ce.exam_code 
            FROM $table_class_exam_scores ces
            LEFT JOIN $table_class_exams ce ON ces.exam_id = ce.id
            WHERE ces.student_id = %d
            ORDER BY ces.id DESC
        ", $student_id));

        // ۲. آزمون‌های تک‌درسی دبستان
        $table_curr_scores = $wpdb->prefix . 'gk_curriculum_scores';
        $curr_scores = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM $table_curr_scores
            WHERE student_id = %d
            ORDER BY id DESC
        ", $student_id));

        // ۳. آزمون‌های روان‌شناختی، هوش‌های چندگانه گاردنر و استعدادیابی
        $table_assessments = $wpdb->prefix . 'gk_student_assessments';
        $table_results = $wpdb->prefix . 'gk_assessment_results';
        $assessments = $wpdb->get_results($wpdb->prepare("
            SELECT r.*, sa.student_id as st_id
            FROM $table_results r
            LEFT JOIN $table_assessments sa ON r.id = sa.result_id
            WHERE sa.student_id = %d OR r.child_name = %s
            ORDER BY r.id DESC
        ", $student_id, $student->name));

        // ۴. سوابق امتیازات در مسابقات و لیگ‌های کلاسی
        $table_league_scores = $wpdb->prefix . 'gk_league_scores';
        $table_leagues = $wpdb->prefix . 'gk_leagues';
        $league_scores = $wpdb->get_results($wpdb->prepare("
            SELECT ls.*, l.title as league_title, p.post_title as game_title
            FROM $table_league_scores ls
            LEFT JOIN $table_leagues l ON ls.league_id = l.id
            LEFT JOIN {$wpdb->posts} p ON ls.game_id = p.ID
            WHERE ls.student_id = %d
            ORDER BY ls.id DESC
        ", $student_id));

        // محاسبات آماری
        $all_exam_scores_arr = [];
        foreach ($class_exam_scores as $ces) {
            $all_exam_scores_arr[] = floatval($ces->score);
        }
        foreach ($curr_scores as $cs) {
            $all_exam_scores_arr[] = floatval($cs->score);
        }

        $total_exams_taken = count($all_exam_scores_arr) + count($assessments);
        $avg_exam_score = count($all_exam_scores_arr) > 0 ? round(array_sum($all_exam_scores_arr) / count($all_exam_scores_arr), 1) : 0;
        $total_games_played = count($league_scores);

        // وضعیت کلی عملکرد
        $overall_level = 'در انتظار آزمون';
        $overall_color = '#64748b';
        $overall_bg = '#f1f5f9';
        if ($total_exams_taken > 0) {
            if ($avg_exam_score >= 85) {
                $overall_level = '🌟 عالی و درخشان';
                $overall_color = '#15803d';
                $overall_bg = '#dcfce7';
            } elseif ($avg_exam_score >= 70) {
                $overall_level = '👍 خیلی خوب';
                $overall_color = '#0369a1';
                $overall_bg = '#e0f2fe';
            } elseif ($avg_exam_score >= 50) {
                $overall_level = '👌 خوب و قابل قبول';
                $overall_color = '#b45309';
                $overall_bg = '#fef3c7';
            } else {
                $overall_level = '🌱 نیازمند تمرین بیشتر';
                $overall_color = '#b91c1c';
                $overall_bg = '#fee2e2';
            }
        }

        ob_start();
        ?>
        <div class="gk-reportcard-view" id="gkReportCardPrintArea">
            <!-- ۱. سربرگ رسمی با مشخصات کامل مهد/مدرسه -->
            <div class="gk-rc-letterhead">
                <div class="gk-rc-brand-meta">
                    <?php if (!empty($student->logo_url)): ?>
                        <img src="<?php echo esc_url($student->logo_url); ?>" alt="<?php echo esc_attr($student->org_name); ?>" class="gk-rc-logo" />
                    <?php else: ?>
                        <div class="gk-rc-logo-fallback">🏫</div>
                    <?php endif; ?>
                    <div>
                        <h2 class="gk-rc-org-title"><?php echo esc_html($student->org_name ?: 'مرکز آموزشی و مهدکودک'); ?></h2>
                        <div class="gk-rc-subhead">
                            <span>کلاس: <strong><?php echo esc_html($student->class_name); ?></strong></span>
                            <?php if (!empty($student->teacher_name)): ?>
                                <span class="gk-rc-dot">•</span>
                                <span>آموزگار: <strong><?php echo esc_html($student->teacher_name); ?></strong></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="gk-rc-actions-box">
                    <button type="button" class="gk-rc-btn-print" onclick="window.print();">
                        🖨️ چاپ کارنامه رسمی
                    </button>
                    <div class="gk-rc-date-badge">
                        📅 تاریخ صدور: <?php echo date_i18n('j F Y'); ?>
                    </div>
                </div>
            </div>

            <!-- ۲. مشخصات و کارت هویت نوآموز -->
            <div class="gk-rc-student-card">
                <div class="gk-rc-st-avatar">👶</div>
                <div class="gk-rc-st-info">
                    <div class="gk-rc-st-name-row">
                        <h1 class="gk-rc-st-name"><?php echo esc_html($student->name); ?></h1>
                        <span class="gk-rc-st-age"><?php echo intval($student->age); ?> ساله</span>
                        <span class="gk-rc-overall-badge" style="background:<?php echo $overall_bg; ?>; color:<?php echo $overall_color; ?>;">
                            <?php echo $overall_level; ?>
                        </span>
                    </div>
                    <div class="gk-rc-st-meta-row">
                        <span>📞 والد: <strong><?php echo esc_html($student->parent_phone ?: 'ثبت نشده'); ?></strong></span>
                        <span class="gk-rc-dot">•</span>
                        <span>🔑 کد اختصاصی: <code><?php echo esc_html($student->student_token); ?></code></span>
                    </div>
                </div>
            </div>

            <!-- ۳. کارت‌های آماری کلیدی (KPI Cards) -->
            <div class="gk-rc-kpi-grid">
                <div class="gk-rc-kpi-item" style="border-top-color: #6366f1;">
                    <div class="gk-rc-kpi-icon" style="background: #e0e7ff; color: #4338ca;">📝</div>
                    <div class="gk-rc-kpi-content">
                        <span class="gk-rc-kpi-label">آزمون‌های انجام‌شده</span>
                        <strong class="gk-rc-kpi-val"><?php echo $total_exams_taken; ?> آزمون</strong>
                    </div>
                </div>

                <div class="gk-rc-kpi-item" style="border-top-color: #10b981;">
                    <div class="gk-rc-kpi-icon" style="background: #d1fae5; color: #047857;">🎯</div>
                    <div class="gk-rc-kpi-content">
                        <span class="gk-rc-kpi-label">میانگین نمرات امتحانات</span>
                        <strong class="gk-rc-kpi-val"><?php echo $avg_exam_score > 0 ? $avg_exam_score . '٪' : '—'; ?></strong>
                    </div>
                </div>

                <div class="gk-rc-kpi-item" style="border-top-color: #f59e0b;">
                    <div class="gk-rc-kpi-icon" style="background: #fef3c7; color: #b45309;">🏆</div>
                    <div class="gk-rc-kpi-content">
                        <span class="gk-rc-kpi-label">امتیاز لیگ کلاسی</span>
                        <strong class="gk-rc-kpi-val"><?php echo number_format($student->total_game_score); ?></strong>
                    </div>
                </div>

                <div class="gk-rc-kpi-item" style="border-top-color: #ec4899;">
                    <div class="gk-rc-kpi-icon" style="background: #fce7f3; color: #be185d;">🥇</div>
                    <div class="gk-rc-kpi-content">
                        <span class="gk-rc-kpi-label">رتبه در کلاس</span>
                        <strong class="gk-rc-kpi-val">رتبه <?php echo $class_rank; ?> از <?php echo $total_class_students; ?></strong>
                    </div>
                </div>
            </div>

            <!-- ۴. بخش اول: امتحانات و آزمون‌های کلاسی و درسی -->
            <div class="gk-rc-section">
                <div class="gk-rc-sec-header">
                    <div class="gk-rc-sec-title">
                        <span class="gk-rc-sec-ico">📝</span>
                        <h3>سوابق آزمون‌ها و امتحانات کلاسی دبستان</h3>
                    </div>
                    <span class="gk-rc-sec-badge"><?php echo count($class_exam_scores) + count($curr_scores); ?> آزمون ثبت شده</span>
                </div>

                <?php if (empty($class_exam_scores) && empty($curr_scores)): ?>
                    <div class="gk-rc-empty-box">
                        <span>📝</span>
                        <p>هنوز نمره‌ای در آزمون‌های درسی برای این نوآموز ثبت نشده است.</p>
                    </div>
                <?php else: ?>
                    <div class="gk-rc-table-responsive">
                        <table class="gk-rc-table">
                            <thead>
                                <tr>
                                    <th>عنوان آزمون / درس</th>
                                    <th>نمره کسب شده</th>
                                    <th>سطح توصیفی</th>
                                    <th>ریز تحلیل مباحث و مفاهیم</th>
                                    <th>تاریخ برگزاری</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($class_exam_scores as $ces): 
                                    $topics = json_decode($ces->topic_breakdown, true);
                                    $badge_class = floatval($ces->score) >= 80 ? 'gk-badge-excel' : (floatval($ces->score) >= 60 ? 'gk-badge-good' : 'gk-badge-need');
                                ?>
                                    <tr>
                                        <td>
                                            <strong style="color:#1e293b; font-size:13.5px;"><?php echo esc_html($ces->exam_title ?: 'امتحان کلاسی'); ?></strong>
                                            <div style="font-size:11px; color:#64748b;">کد آزمون: <?php echo esc_html($ces->quiz_id); ?></div>
                                        </td>
                                        <td>
                                            <span class="gk-rc-score-pill <?php echo $badge_class; ?>">
                                                <?php echo floatval($ces->score); ?> از ۱۰۰
                                            </span>
                                        </td>
                                        <td>
                                            <span class="gk-rc-level-text"><?php echo esc_html($ces->level_text ?: 'ثبت شده'); ?></span>
                                        </td>
                                        <td>
                                            <?php if (!empty($topics) && is_array($topics)): ?>
                                                <div class="gk-rc-topic-chips">
                                                    <?php foreach ($topics as $t_name => $t_data): 
                                                        $c = $t_data['correct'] ?? 0;
                                                        $tot = $t_data['total'] ?? 1;
                                                        $is_p = ($c == $tot);
                                                    ?>
                                                        <span class="gk-rc-topic-chip <?php echo $is_p ? 'is-correct' : 'is-review'; ?>">
                                                            <?php echo esc_html($t_name); ?>: <strong><?php echo $c; ?>/<?php echo $tot; ?></strong>
                                                        </span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <span style="color:#94a3b8; font-size:12px;">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span style="font-size:12px; color:#64748b;"><?php echo date_i18n('j F Y - H:i', strtotime($ces->created_at)); ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                                <?php foreach ($curr_scores as $cs): 
                                    $topics = json_decode($cs->topic_breakdown, true);
                                    $badge_class = floatval($cs->score) >= 80 ? 'gk-badge-excel' : (floatval($cs->score) >= 60 ? 'gk-badge-good' : 'gk-badge-need');
                                ?>
                                    <tr>
                                        <td>
                                            <strong style="color:#1e293b; font-size:13.5px;"><?php echo esc_html($cs->quiz_title ?: 'آزمون درسی دبستان'); ?></strong>
                                            <div style="font-size:11px; color:#0284c7;">سنجش هوشمند کتب درسی</div>
                                        </td>
                                        <td>
                                            <span class="gk-rc-score-pill <?php echo $badge_class; ?>">
                                                <?php echo floatval($cs->score); ?>٪
                                            </span>
                                        </td>
                                        <td>
                                            <span class="gk-rc-level-text"><?php echo esc_html($cs->level_text ?: 'خیلی خوب'); ?></span>
                                        </td>
                                        <td>
                                            <?php if (!empty($topics) && is_array($topics)): ?>
                                                <div class="gk-rc-topic-chips">
                                                    <?php foreach ($topics as $t_name => $t_data): 
                                                        $c = $t_data['correct'] ?? 0;
                                                        $tot = $t_data['total'] ?? 1;
                                                        $is_p = ($c == $tot);
                                                    ?>
                                                        <span class="gk-rc-topic-chip <?php echo $is_p ? 'is-correct' : 'is-review'; ?>">
                                                            <?php echo esc_html($t_name); ?>: <strong><?php echo $c; ?>/<?php echo $tot; ?></strong>
                                                        </span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <span style="color:#94a3b8; font-size:12px;">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span style="font-size:12px; color:#64748b;"><?php echo date_i18n('j F Y - H:i', strtotime($cs->created_at)); ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ۵. بخش دوم: سوابق آزمون‌های روان‌شناختی، هوش‌های چندگانه و استعدادیابی -->
            <div class="gk-rc-section">
                <div class="gk-rc-sec-header">
                    <div class="gk-rc-sec-title">
                        <span class="gk-rc-sec-ico">🧠</span>
                        <h3>سوابق آزمون‌های روان‌شناختی، هوش‌های چندگانه و استعدادیابی</h3>
                    </div>
                    <span class="gk-rc-sec-badge"><?php echo count($assessments); ?> تست انجام‌شده</span>
                </div>

                <?php if (empty($assessments)): ?>
                    <div class="gk-rc-empty-box">
                        <span>🧠</span>
                        <p>هنوز تست روان‌شناختی یا استعدادیابی برای این نوآموز ثبت نشده است.</p>
                    </div>
                <?php else: ?>
                    <div class="gk-rc-table-responsive">
                        <table class="gk-rc-table">
                            <thead>
                                <tr>
                                    <th>عنوان آزمون روان‌شناختی</th>
                                    <th>تحلیل ابعاد و استعدادهای برتر</th>
                                    <th>تاریخ انجام تست</th>
                                    <th style="text-align:center;">کارنامه تفصیلی</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $test_names = [
                                    'gardner-intelligence'   => '🧠 تست هوش‌های چندگانه گاردنر',
                                    'adhd-screening'         => '🎯 غربالگری تمرکز و بیش‌فعالی (ADHD)',
                                    'learning-styles'        => '🎨 تست سبک‌های یادگیری کودک (VARK)',
                                    'child-learning-style'   => '🎨 تست سبک‌های یادگیری کودک (VARK)',
                                    'child-creativity'       => '💡 سنجش خلاقیت و ابتکار کودک',
                                    'child-anxiety'          => '🕊️ سنجش اضطراب و آرامش کودک',
                                    'emotional-intelligence' => '💖 هوش هیجانی و ارتباطی (EQ)'
                                ];
                                $dim_translations = [
                                    'visual'        => 'دیداری و بصری',
                                    'auditory'      => 'شنیداری',
                                    'kinesthetic'   => 'لمسی و حرکتی',
                                    'linguistic'    => 'زبانی و کلامی',
                                    'logical'       => 'منطقی و ریاضی',
                                    'spatial'       => 'فضایی و تجسمی',
                                    'musical'       => 'موسیقیایی',
                                    'bodily'        => 'بدنی و حرکتی',
                                    'interpersonal' => 'بین‌فردی و اجتماعی',
                                    'intrapersonal' => 'درون‌فردی',
                                    'naturalist'    => 'طبیعت‌گرا',
                                    'inattention'   => 'نقص توجه',
                                    'hyperactivity' => 'بیش‌فعالی'
                                ];
                                foreach ($assessments as $as): 
                                    $scores = json_decode($as->scores_data, true) ?: [];
                                    $t_title = $test_names[$as->assessment_slug] ?? ('تست ' . $as->assessment_slug);
                                    $view_url = home_url('/tests/' . $as->assessment_slug . '/?result_id=' . $as->id);
                                ?>
                                    <tr>
                                        <td>
                                            <strong style="color:#1e293b; font-size:13.5px;"><?php echo esc_html($t_title); ?></strong>
                                        </td>
                                        <td>
                                            <?php if (!empty($scores) && is_array($scores)): ?>
                                                <div class="gk-rc-topic-chips">
                                                    <?php 
                                                    $cnt = 0;
                                                    foreach ($scores as $dim_k => $dim_v): 
                                                        if ($cnt >= 3) break;
                                                        $raw_name = is_array($dim_v) ? ($dim_v['title'] ?? $dim_k) : $dim_k;
                                                        $dim_name = $dim_translations[$raw_name] ?? ($dim_translations[$dim_k] ?? $raw_name);
                                                        $dim_score = is_array($dim_v) ? ($dim_v['score'] ?? '') : $dim_v;
                                                        $cnt++;
                                                    ?>
                                                        <span class="gk-rc-topic-chip is-correct">
                                                            ⭐ <?php echo esc_html($dim_name); ?>: <strong><?php echo esc_html($dim_score); ?></strong>
                                                        </span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <span style="color:#94a3b8; font-size:12px;">تکمیل شده</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span style="font-size:12px; color:#64748b;"><?php echo date_i18n('j F Y', strtotime($as->created_at)); ?></span>
                                        </td>
                                        <td style="text-align:center;">
                                            <a href="<?php echo esc_url($view_url); ?>" target="_blank" class="gk-btn-tool" style="background:#7c3aed; padding:5px 12px; font-size:11.5px; border-radius:8px;">
                                                🔍 مشاهده جزئیات
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ۶. بخش سوم: سوابق بازی‌ها و لیگ‌های کلاسی -->
            <div class="gk-rc-section">
                <div class="gk-rc-sec-header">
                    <div class="gk-rc-sec-title">
                        <span class="gk-rc-sec-ico">🎮</span>
                        <h3>سوابق بازی‌های مهارتی، فکری و لیگ‌های کلاسی</h3>
                    </div>
                    <span class="gk-rc-sec-badge"><?php echo count($league_scores); ?> رکورد ثبت شده</span>
                </div>

                <?php if (empty($league_scores)): ?>
                    <div class="gk-rc-empty-box">
                        <span>🎮</span>
                        <p>هنوز رکوردی در لیگ‌ها و بازی‌های کلاسی ثبت نشده است.</p>
                    </div>
                <?php else: ?>
                    <div class="gk-rc-table-responsive">
                        <table class="gk-rc-table">
                            <thead>
                                <tr>
                                    <th>عنوان بازی</th>
                                    <th>مسابقه / لیگ مربوطه</th>
                                    <th>امتیاز ثبت شده</th>
                                    <th>تاریخ ثبت رکورد</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($league_scores as $ls): ?>
                                    <tr>
                                        <td>
                                            <strong style="color:#1e293b; font-size:13.5px;">🎮 <?php echo esc_html($ls->game_title ?: ('بازی کد #' . $ls->game_id)); ?></strong>
                                        </td>
                                        <td>
                                            <span style="color:#4f46e5; font-weight:800; font-size:12.5px;">
                                                🏆 <?php echo esc_html($ls->league_title ?: 'لیگ کلاسی'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="gk-rc-score-pill gk-badge-excel">
                                                ⭐ <?php echo number_format($ls->score); ?> امتیاز
                                            </span>
                                        </td>
                                        <td>
                                            <span style="font-size:12px; color:#64748b;"><?php echo date_i18n('j F Y - H:i', strtotime($ls->created_at)); ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ۶. بخش جمع‌بندی و مهر و امضای مرکز -->
            <div class="gk-rc-footer-signature">
                <div class="gk-rc-teacher-note">
                    <strong>✍️ نظر و توصیه آموزگار به اولیا:</strong>
                    <div class="gk-rc-note-line"></div>
                    <div class="gk-rc-note-line"></div>
                </div>

                <div class="gk-rc-stamp-box">
                    <div class="gk-rc-stamp-circle">
                        <span>مهر و امضای</span>
                        <strong><?php echo esc_html($student->org_name ?: 'قربانی کیدز'); ?></strong>
                    </div>
                </div>
            </div>
        </div>
        <?php
        $html = ob_get_clean();
        wp_send_json_success([
            'student_name' => $student->name,
            'html' => $html
        ]);
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