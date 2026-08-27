<?php
/**
 * Class GK_School_Games
 * Seamlessly connects school students to games, student priority over WP logins,
 * auto-appends tokens ONLY to game iframe, and NEVER touches website navigation menus.
 */
if (!defined('ABSPATH')) exit;

class GK_School_Games {

    public static function init() {
        add_action('init', [__CLASS__, 'handle_student_session']);
        add_action('wp_footer', [__CLASS__, 'inject_game_token_auto_appender']);
        add_action('wp_footer', [__CLASS__, 'inject_student_game_celebration_modal']);
        add_action('wp_ajax_gk_submit_student_game_score', [__CLASS__, 'ajax_submit_score']);
        add_action('wp_ajax_nopriv_gk_submit_student_game_score', [__CLASS__, 'ajax_submit_score']);
    }

    public static function handle_student_session() {
        if (isset($_GET['st_token']) && !empty($_GET['st_token'])) {
            $token = sanitize_text_field($_GET['st_token']);
            setcookie('gk_active_student_token', $token, 0, '/');
            $_COOKIE['gk_active_student_token'] = $token;
        }

        if (isset($_GET['league_code']) && !empty($_GET['league_code'])) {
            $l_code = sanitize_text_field($_GET['league_code']);
            setcookie('gk_active_league_code', $l_code, 0, '/');
            $_COOKIE['gk_active_league_code'] = $l_code;
        }
    }

    public static function get_active_student() {
        $token = $_GET['st_token'] ?? null;
        if (!$token) return null;

        global $wpdb;
        $table_students = $wpdb->prefix . 'gk_students';
        $table_classes  = $wpdb->prefix . 'gk_classes';
        $table_orgs     = $wpdb->prefix . 'gk_organizations';

        return $wpdb->get_row($wpdb->prepare("
            SELECT s.*, c.name as class_name, c.invite_code, o.name as org_name, o.logo_url
            FROM $table_students s
            LEFT JOIN $table_classes c ON s.class_id = c.id
            LEFT JOIN $table_orgs o ON s.org_id = o.id
            WHERE s.student_token = %s
            LIMIT 1
        ", $token));
    }

    public static function get_active_league() {
        $l_code = $_GET['league_code'] ?? null;
        if (!$l_code) return null;

        global $wpdb;
        $table_leagues = $wpdb->prefix . 'gk_leagues';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_leagues WHERE league_code = %s", $l_code));
    }

    public static function inject_student_game_celebration_modal() {
        $student = self::get_active_student();
        if (!$student) return;

        $league = self::get_active_league();
        $ajax_url = admin_url('admin-ajax.php');
        $current_game_id = get_the_ID() ?: 0;

        if ($league) {
            $back_url = home_url('/league/?code=' . $league->league_code . '&st_token=' . $student->student_token);
            $back_btn_text = '🏆 بازگشت به سالن مسابقه و مشاهده رتبه';
            $subtitle = 'امتیاز شما در مسابقه «' . esc_html($league->title) . '» ثبت شد ✨';
        } else {
            $back_url = home_url('/teacher-class/?code=' . $student->invite_code);
            $back_btn_text = '🏆 مشاهده رتبه در تابلوی لیگ کلاس';
            $subtitle = 'امتیاز این بازی به نام شما در جدول کلاسی ثبت شد ✨';
        }
        ?>
        <style>
            #gk_score_modal { display: none !important; }
        </style>

        <script>
        if (window.GhorbaniKidsScore) {
            window.GhorbaniKidsScore.currentUserName = '<?php echo esc_js($student->name); ?>';
        }

        window.addEventListener('message', function(event) {
            if (event.data && (event.data.type === 'GK_SUBMIT_SCORE' || event.data.type === 'GAME_OVER' || event.data.type === 'GK_GAME_OVER')) {
                var score = parseInt(event.data.score || event.data.points || 0);
                if (score > 0) {
                    jQuery.post('<?php echo $ajax_url; ?>', {
                        action: 'gk_submit_student_game_score',
                        student_token: '<?php echo esc_js($student->student_token); ?>',
                        league_code: '<?php echo esc_js($league ? $league->league_code : ''); ?>',
                        game_id: '<?php echo (int) $current_game_id; ?>',
                        score: score
                    }, function(res) {
                        if (res.success && res.data) {
                            jQuery('#gk-st-modal-score').text(score.toLocaleString('fa-IR'));
                            jQuery('#gk-st-modal-total').text(res.data.new_total_formatted);
                            jQuery('#gk-student-celebrate-modal').fadeIn(250);
                        }
                    });
                }
            }
        });
        </script>

        <div id="gk-student-celebrate-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.7); z-index:99999999; backdrop-filter:blur(6px); direction:rtl; text-align:center;">
            <div style="max-width:440px; margin:100px auto; background:#ffffff; border-radius:28px; padding:32px 24px; box-shadow:0 25px 50px rgba(0,0,0,0.3); border:3px solid #e0e7ff; position:relative;">
                <div style="font-size:54px; margin-bottom:8px;">🎉</div>
                <h2 style="font-size:22px; font-weight:900; color:#1e1b4b; margin:0 0 6px 0;">آفرین قهرمان، <?php echo esc_html($student->name); ?>!</h2>
                <p style="font-size:13.5px; color:#64748b; margin:0 0 20px 0;"><?php echo $subtitle; ?></p>
                
                <div style="background:linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%); border:2px dashed #8b5cf6; border-radius:20px; padding:18px; margin-bottom:24px;">
                    <div style="font-size:13px; color:#6d28d9; font-weight:bold; margin-bottom:4px;">امتیاز کسب شده:</div>
                    <div style="font-size:32px; font-weight:900; color:#4f46e5; margin-bottom:8px;">
                        +<span id="gk-st-modal-score">0</span> امتیاز 🚀
                    </div>
                    <div style="font-size:12.5px; color:#475569;">
                        🏆 مجموع امتیاز: <strong id="gk-st-modal-total"><?php echo number_format($student->total_game_score); ?></strong>
                    </div>
                </div>

                <div style="display:flex; flex-direction:column; gap:10px;">
                    <a href="<?php echo esc_url($back_url); ?>" style="background:linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color:#ffffff; font-weight:900; font-size:14px; padding:13px 0; border-radius:14px; text-decoration:none; box-shadow:0 4px 15px rgba(99,102,241,0.35);">
                        <?php echo esc_html($back_btn_text); ?>
                    </a>
                    <button type="button" onclick="var m=document.getElementById('gk-student-celebrate-modal'); if(m) m.style.display='none'; var ifr=document.querySelector('iframe'); if(ifr) { var s=ifr.src.replace(/([?&])_t=\d+/, ''); ifr.src=s+(s.indexOf('?')>-1?'&':'?')+'_t='+new Date().getTime(); }" style="background:#f1f5f9; color:#334155; font-weight:bold; font-size:13px; padding:11px 0; border-radius:12px; border:none; cursor:pointer;">
                        🔄 بازی مجدد برای رکورد بالاتر 🎮
                    </button>
                </div>
            </div>
        </div>
        <?php
    }

    public static function inject_game_token_auto_appender() {
        $student = self::get_active_student();
        if (!$student) return;

        $league = self::get_active_league();
        $token = esc_js($student->student_token);
        $l_code = $league ? esc_js($league->league_code) : '';
        ?>
        <script>
        jQuery(document).ready(function($) {
            var stToken = '<?php echo $token; ?>';
            var leagueCode = '<?php echo $l_code; ?>';
            
            // فقط و فقط سورس آی‌فریم داخل صفحه بازی تغییر کند و منوی سایت دست‌نخورده بماند
            $('iframe').each(function() {
                var src = $(this).attr('src');
                if (src && src.indexOf('st_token=') === -1 && (src.indexOf('/games/') !== -1 || src.indexOf('.html') !== -1)) {
                    var sep = (src.indexOf('?') !== -1) ? '&' : '?';
                    var newSrc = src + sep + 'st_token=' + stToken;
                    if (leagueCode && src.indexOf('league_code=') === -1) {
                        newSrc += '&league_code=' + leagueCode;
                    }
                    $(this).attr('src', newSrc);
                }
            });
        });
        </script>
        <?php
    }

    public static function ajax_submit_score() {
        $token = sanitize_text_field($_POST['student_token'] ?? '');
        $league_code = sanitize_text_field($_POST['league_code'] ?? '');
        $game_id = intval($_POST['game_id'] ?? 0);
        $score = intval($_POST['score'] ?? 0);

        if (!$token || $score <= 0) {
            wp_send_json_error('Invalid parameters');
        }

        global $wpdb;
        $table_students = $wpdb->prefix . 'gk_students';
        $student = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_students WHERE student_token = %s", $token));

        if (!$student) {
            wp_send_json_error('Student not found');
        }

        // ۱. افزایش مجموع امتیاز کلاسی
        $new_total = intval($student->total_game_score) + $score;
        $wpdb->update($table_students, ['total_game_score' => $new_total], ['id' => $student->id], ['%d'], ['%d']);

        // ۲. ثبت در جدول اختصاصی مسابقه
        if (!empty($league_code)) {
            $table_leagues = $wpdb->prefix . 'gk_leagues';
            $table_league_scores = $wpdb->prefix . 'gk_league_scores';
            $league = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_leagues WHERE league_code = %s", $league_code));
            
            if ($league) {
                $wpdb->insert($table_league_scores, [
                    'league_id'  => $league->id,
                    'student_id' => $student->id,
                    'game_id'    => $game_id,
                    'score'      => $score,
                    'created_at' => current_time('mysql')
                ], ['%d', '%d', '%d', '%d', '%s']);
            }
        }

        // ۳. ثبت در جدول رکوردهای عمومی
        $table_game_scores = $wpdb->prefix . 'gk_game_scores';
        if ($game_id > 0) {
            $wpdb->insert($table_game_scores, [
                'game_id'     => $game_id,
                'user_id'     => 0,
                'player_name' => $student->name,
                'score'       => $score,
                'created_at'  => current_time('mysql')
            ], ['%d', '%d', '%s', '%d', '%s']);
        }

        wp_send_json_success([
            'added_score' => $score,
            'new_total'   => $new_total,
            'new_total_formatted' => number_format($new_total)
        ]);
    }
}