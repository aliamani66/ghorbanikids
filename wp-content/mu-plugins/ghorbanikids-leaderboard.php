<?php
/**
 * Plugin Name: GhorbaniKids Game Leaderboard & Score System
 * Description: سیستم ثبت امتیازات و جدول برترین بازیکنان قربانی کیدز
 * Version: 1.0.0
 * Author: GhorbaniKids Team
 */

defined('ABSPATH') || exit;

class GhorbaniKids_Leaderboard {

    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        // leaderboard handled in single-gk_game.php
        // modal handled in single-gk_game.php
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        register_rest_route('ghorbanikids/v1', '/submit-score', [
            'methods'  => 'POST',
            'callback' => [$this, 'handle_submit_score'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('ghorbanikids/v1', '/leaderboard', [
            'methods'  => 'GET',
            'callback' => [$this, 'handle_get_leaderboard'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * Handle score submission
     */
    public function handle_submit_score($request) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'gk_game_scores';

        $game_id = (int) $request->get_param('game_id');
        $score   = (int) $request->get_param('score');
        $player_name = sanitize_text_field($request->get_param('player_name'));

        if (!$game_id || $score < 0) {
            return new WP_REST_Response(['success' => false, 'message' => 'اطلاعات ارسالی نامعتبر است.'], 400);
        }

        // Get user if logged in
        $user_id = get_current_user_id();
        if ($user_id > 0) {
            $user = get_userdata($user_id);
            $user_display = trim($user->first_name . ' ' . $user->last_name);
            if (empty($user_display)) {
                $user_display = $user->display_name ?: $user->user_login;
            }
            $player_name = $user_display;
        } else {
            if (empty($player_name)) {
                $player_name = 'قهرمان مهمان';
            }
        }

        // Check if identical score was submitted for this game by this user/player in the last 60 seconds (anti-duplicate)
        $recent_duplicate = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name 
             WHERE game_id = %d AND player_name = %s AND score = %d 
             AND created_at >= DATE_SUB(NOW(), INTERVAL 60 SECOND)
             ORDER BY id DESC LIMIT 1",
            $game_id, $player_name, $score
        ));

        if (!$recent_duplicate) {
            $inserted = $wpdb->insert($table_name, [
                'game_id'     => $game_id,
                'user_id'     => $user_id,
                'player_name' => $player_name,
                'score'       => $score,
                'created_at'  => current_time('mysql'),
            ], ['%d', '%d', '%s', '%d', '%s']);

            if (!$inserted) {
                return new WP_REST_Response(['success' => false, 'message' => 'خطا در ثبت امتیاز.'], 500);
            }
        }

        if (!$inserted) {
            return new WP_REST_Response(['success' => false, 'message' => 'خطا در ثبت امتیاز.'], 500);
        }

        // Calculate player rank for this game
        $rank = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) + 1 FROM $table_name WHERE game_id = %d AND score > %d",
            $game_id, $score
        ));

        // Get updated leaderboard
        $leaderboard = $this->get_game_leaderboard_data($game_id);

        return new WP_REST_Response([
            'success'      => true,
            'message'      => 'امتیاز شما با موفقیت ثبت شد!',
            'player_name'  => $player_name,
            'score'        => $score,
            'rank'         => (int) $rank,
            'leaderboard'  => $leaderboard,
        ], 200);
    }

    /**
     * Handle get leaderboard request
     */
    public function handle_get_leaderboard($request) {
        $game_id = (int) $request->get_param('game_id');
        if (!$game_id) {
            return new WP_REST_Response(['success' => false, 'message' => 'شناسه بازی الزامی است.'], 400);
        }

        $leaderboard = $this->get_game_leaderboard_data($game_id);

        return new WP_REST_Response([
            'success'     => true,
            'leaderboard' => $leaderboard,
        ], 200);
    }

    /**
     * Helper to get top scores for a game
     */
    public function get_game_leaderboard_data($game_id, $limit = 10) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'gk_game_scores';

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT id, player_name, score, created_at, user_id
             FROM $table_name
             WHERE game_id = %d
             ORDER BY score DESC, created_at ASC
             LIMIT %d",
            $game_id, $limit
        ));

        $list = [];
        $rank = 1;
        foreach ($results as $r) {
            $medal = '';
            if ($rank === 1) $medal = '🥇';
            elseif ($rank === 2) $medal = '🥈';
            elseif ($rank === 3) $medal = '🥉';

            $list[] = [
                'rank'        => $rank,
                'medal'       => $medal,
                'player_name' => esc_html($r->player_name),
                'score'       => (int) $r->score,
                'score_fmt'   => number_format((int) $r->score, 0, '', '،'),
                'time_ago'    => human_time_diff(strtotime($r->created_at), current_time('timestamp')) . ' پیش',
            ];
            $rank++;
        }

        return $list;
    }

    /**
     * Append Leaderboard Widget to single game page content
     */
    public function append_leaderboard_to_game_content($content) {
        if (!is_singular('gk_game') || !in_the_loop() || !is_main_query()) {
            return $content;
        }

        $game_id = get_the_ID();
        $leaderboard = $this->get_game_leaderboard_data($game_id, 10);
        $user_id = get_current_user_id();

        ob_start();
        ?>
        <div class="gk-leaderboard-section" id="gk_game_leaderboard_wrap" data-game-id="<?php echo esc_attr($game_id); ?>">
            <style id="gk-leaderboard-scoped-css">
                .gk-leaderboard-section {
                    max-width: 900px !important;
                    margin: 35px auto 40px auto !important;
                    background: #ffffff !important;
                    border: 2px solid #e2e8f0 !important;
                    border-radius: 24px !important;
                    padding: 26px 22px !important;
                    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04) !important;
                    direction: rtl !important;
                    text-align: right !important;
                    font-family: 'IRANSansXFaNum', 'aviny', Tahoma, sans-serif !important;
                    box-sizing: border-box !important;
                }

                .gk-lb-header {
                    display: flex !important;
                    align-items: center !important;
                    justify-content: space-between !important;
                    flex-wrap: wrap !important;
                    gap: 10px !important;
                    margin-bottom: 18px !important;
                    border-bottom: 2px solid #f1f5f9 !important;
                    padding-bottom: 14px !important;
                }

                .gk-lb-header-title {
                    display: flex !important;
                    align-items: center !important;
                    gap: 10px !important;
                }

                .gk-lb-icon-badge {
                    width: 46px !important;
                    height: 46px !important;
                    border-radius: 14px !important;
                    background: #fef3c7 !important;
                    border: 1.5px solid #fde68a !important;
                    display: flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    font-size: 1.6rem !important;
                }

                .gk-lb-header h3 {
                    font-family: 'aviny', cursive, Tahoma, sans-serif !important;
                    font-size: 1.9rem !important;
                    font-weight: 900 !important;
                    color: #0f172a !important;
                    margin: 0 !important;
                    line-height: 1.2 !important;
                }

                .gk-lb-header p {
                    font-size: 0.92rem !important;
                    color: #64748b !important;
                    font-weight: 700 !important;
                    margin: 0 !important;
                }

                .gk-lb-refresh-btn {
                    display: inline-flex !important;
                    align-items: center !important;
                    gap: 6px !important;
                    background: #f8fafc !important;
                    border: 1.5px solid #cbd5e1 !important;
                    border-radius: 10px !important;
                    padding: 6px 14px !important;
                    font-size: 0.85rem !important;
                    font-weight: 800 !important;
                    color: #475569 !important;
                    cursor: pointer !important;
                    transition: all 0.2s ease !important;
                }

                .gk-lb-refresh-btn:hover {
                    background: #f1f5f9 !important;
                    color: #0f172a !important;
                }

                .gk-lb-list-table {
                    display: flex !important;
                    flex-direction: column !important;
                    gap: 8px !important;
                    width: 100% !important;
                    box-sizing: border-box !important;
                }

                .gk-lb-row-item {
                    display: flex !important;
                    align-items: center !important;
                    justify-content: space-between !important;
                    background: #f8fafc !important;
                    border: 1.5px solid #e2e8f0 !important;
                    border-radius: 14px !important;
                    padding: 10px 16px !important;
                    transition: all 0.2s ease !important;
                    box-sizing: border-box !important;
                }

                .gk-lb-row-item:hover {
                    transform: translateY(-1px) !important;
                    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04) !important;
                }

                .gk-lb-row-item.rank-1 {
                    background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%) !important;
                    border-color: #fcd34d !important;
                }

                .gk-lb-row-item.rank-2 {
                    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%) !important;
                    border-color: #cbd5e1 !important;
                }

                .gk-lb-row-item.rank-3 {
                    background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%) !important;
                    border-color: #fdba74 !important;
                }

                .gk-lb-left-col {
                    display: flex !important;
                    align-items: center !important;
                    gap: 12px !important;
                }

                .gk-lb-rank-badge {
                    width: 32px !important;
                    height: 32px !important;
                    border-radius: 10px !important;
                    display: flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    font-size: 1.1rem !important;
                    font-weight: 900 !important;
                    background: #ffffff !important;
                    border: 1.5px solid #cbd5e1 !important;
                    color: #475569 !important;
                    flex-shrink: 0 !important;
                }

                .gk-lb-row-item.rank-1 .gk-lb-rank-badge {
                    background: #f59e0b !important;
                    border-color: #f59e0b !important;
                    color: #ffffff !important;
                }

                .gk-lb-row-item.rank-2 .gk-lb-rank-badge {
                    background: #94a3b8 !important;
                    border-color: #94a3b8 !important;
                    color: #ffffff !important;
                }

                .gk-lb-row-item.rank-3 .gk-lb-rank-badge {
                    background: #d97706 !important;
                    border-color: #d97706 !important;
                    color: #ffffff !important;
                }

                .gk-lb-player-name {
                    font-weight: 900 !important;
                    font-size: 1.05rem !important;
                    color: #0f172a !important;
                }

                .gk-lb-score-col {
                    display: flex !important;
                    align-items: center !important;
                    gap: 10px !important;
                }

                .gk-lb-score-val {
                    font-size: 1.25rem !important;
                    font-weight: 900 !important;
                    color: #4f46e5 !important;
                    background: #ffffff !important;
                    border: 1.5px solid #e2e8f0 !important;
                    border-radius: 10px !important;
                    padding: 4px 12px !important;
                }

                .gk-lb-row-item.rank-1 .gk-lb-score-val { color: #b45309 !important; border-color: #fcd34d !important; }
                .gk-lb-row-item.rank-2 .gk-lb-score-val { color: #475569 !important; border-color: #cbd5e1 !important; }
                .gk-lb-row-item.rank-3 .gk-lb-score-val { color: #c2410c !important; border-color: #fdba74 !important; }

                .gk-lb-time-ago {
                    font-size: 0.8rem !important;
                    color: #94a3b8 !important;
                    font-weight: 700 !important;
                }

                .gk-lb-empty-notice {
                    text-align: center !important;
                    padding: 24px 16px !important;
                    background: #f8fafc !important;
                    border-radius: 16px !important;
                    border: 1.5px dashed #cbd5e1 !important;
                }

                .gk-lb-empty-notice p {
                    font-size: 1rem !important;
                    font-weight: 800 !important;
                    color: #64748b !important;
                    margin: 0 !important;
                }
            </style>

            <div class="gk-lb-header">
                <div class="gk-lb-header-title">
                    <div class="gk-lb-icon-badge">🏆</div>
                    <div>
                        <h3>جدول قهرمانان و برترین امتیازات</h3>
                        <p>رکورد بازیکنان برتر این بازی در قربانی کیدز</p>
                    </div>
                </div>
                <button type="button" class="gk-lb-refresh-btn" onclick="GhorbaniKidsScore.refreshLeaderboard(<?php echo $game_id; ?>)">
                    <span>🔄 به‌روزرسانی</span>
                </button>
            </div>

            <div class="gk-lb-list-table" id="gk_lb_items_container">
                <?php if (!empty($leaderboard)): ?>
                    <?php foreach ($leaderboard as $row): 
                        $rank_class = ($row['rank'] <= 3) ? 'rank-' . $row['rank'] : '';
                    ?>
                        <div class="gk-lb-row-item <?php echo esc_attr($rank_class); ?>">
                            <div class="gk-lb-left-col">
                                <div class="gk-lb-rank-badge">
                                    <?php echo !empty($row['medal']) ? $row['medal'] : $row['rank']; ?>
                                </div>
                                <span class="gk-lb-player-name"><?php echo $row['player_name']; ?></span>
                            </div>
                            <div class="gk-lb-score-col">
                                <span class="gk-lb-time-ago"><?php echo $row['time_ago']; ?></span>
                                <span class="gk-lb-score-val"><?php echo $row['score_fmt']; ?> امتیاز</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="gk-lb-empty-notice">
                        <p>🎮 هنوز رکوردی برای این بازی ثبت نشده است. همین حالا بازی کنید و اولین قهرمان باشید!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        $leaderboard_html = ob_get_clean();

        return $content . $leaderboard_html;
    }

    /**
     * Render Guest Name Prompt Modal and Bridge Scripts
     */
    public function render_score_prompt_modal() {
        if (!is_singular('gk_game')) {
            return;
        }

        $game_id = get_the_ID();
        $is_logged_in = is_user_logged_in();
        $user_name = '';
        if ($is_logged_in) {
            $user = wp_get_current_user();
            $user_name = trim($user->first_name . ' ' . $user->last_name) ?: $user->display_name;
        }
        ?>
        <div id="gk_score_modal" style="display:none; position:fixed; z-index:999999; inset:0; background:rgba(15,23,42,0.65); backdrop-filter:blur(6px); align-items:center; justify-content:center; direction:rtl; font-family:'IRANSansXFaNum', 'aviny', Tahoma, sans-serif;">
            <div style="background:#ffffff; border-radius:24px; max-width:440px; width:90%; padding:28px 22px; text-align:center; box-shadow:0 20px 40px rgba(0,0,0,0.25); border:2px solid #a7f3d0;">
                <div style="width:68px; height:68px; border-radius:50%; background:#ecfdf5; border:2px solid #10b981; color:#10b981; font-size:2.2rem; display:flex; align-items:center; justify-content:center; margin:0 auto 14px auto;">
                    🎉
                </div>
                <h3 style="font-family:'aviny', cursive, Tahoma, sans-serif; font-size:2rem; font-weight:900; color:#065f46; margin:0 0 6px 0;">آفرین قهرمان!</h3>
                <p style="font-size:1rem; color:#475569; font-weight:700; margin:0 0 16px 0;">
                    امتیاز بازی شما: <span id="gk_modal_score_val" style="color:#059669; font-size:1.35rem; font-weight:900;">۰</span>
                </p>

                <?php if (!$is_logged_in): ?>
                    <div style="margin-bottom:18px; text-align:right;">
                        <label for="gk_guest_player_name" style="display:block; font-size:0.92rem; font-weight:800; color:#334155; margin-bottom:6px;">نام قشنگت رو برای ثبت در جدول قهرمانان بنویس:</label>
                        <input type="text" id="gk_guest_player_name" placeholder="مثلاً: علی کوچولو" style="width:100%; height:46px; border:1.5px solid #cbd5e1; border-radius:12px; padding:0 14px; font-size:1rem; font-family:inherit; font-weight:700; box-sizing:border-box; outline:none;">
                    </div>
                <?php else: ?>
                    <p style="font-size:0.95rem; color:#0f172a; font-weight:800; margin-bottom:18px;">
                        امتیاز شما با نام <strong><?php echo esc_html($user_name); ?></strong> ثبت می‌شود.
                    </p>
                <?php endif; ?>

                <div style="display:flex; gap:10px; justify-content:center;">
                    <button type="button" onclick="GhorbaniKidsScore.confirmSubmitScore()" style="flex:1; height:48px; background:linear-gradient(135deg, #10b981 0%, #059669 100%); color:#ffffff; font-family:inherit; font-size:1.1rem; font-weight:900; border:none; border-radius:12px; cursor:pointer;">
                        ✨ ثبت امتیاز من
                    </button>
                    <button type="button" onclick="GhorbaniKidsScore.closeModal()" style="height:48px; padding:0 18px; background:#f1f5f9; border:1.5px solid #cbd5e1; color:#475569; font-family:inherit; font-size:0.95rem; font-weight:800; border-radius:12px; cursor:pointer;">
                        بستن
                    </button>
                </div>
            </div>
        </div>

        <script>
        var GhorbaniKidsScore = {
            currentGameId: <?php echo (int) $game_id; ?>,
            isLoggedIn: <?php echo $is_logged_in ? 'true' : 'false'; ?>,
            currentUserName: '<?php echo esc_js($user_name); ?>',
            pendingScore: 0,

            // Listen for postMessage from game iframe
            initListener: function() {
                var self = this;
                window.addEventListener('message', function(e) {
                    if (e.data && (e.data.type === 'GK_GAME_OVER' || e.data.type === 'GK_SUBMIT_SCORE' || e.data.type === 'GAME_OVER')) {
                        var score = parseInt(e.data.score || e.data.points || 0, 10);
                        if (score > 0) {
                            self.promptScore(score);
                        }
                    }
                });
            },

            promptScore: function(score) {
                this.pendingScore = score;
                document.getElementById('gk_modal_score_val').innerText = score.toLocaleString('fa-IR');
                var modal = document.getElementById('gk_score_modal');
                if (modal) {
                    modal.style.display = 'flex';
                }
            },

            closeModal: function() {
                var modal = document.getElementById('gk_score_modal');
                if (modal) {
                    modal.style.display = 'none';
                }
            },

            confirmSubmitScore: function() {
                var self = this;
                var playerName = self.isLoggedIn ? self.currentUserName : '';
                var input = document.getElementById('gk_guest_player_name');
                if (input && input.value.trim()) {
                    playerName = input.value.trim();
                }

                if (!self.isLoggedIn && !playerName) {
                    playerName = 'قهرمان مهمان';
                }

                fetch('<?php echo esc_url_raw(rest_url('ghorbanikids/v1/submit-score')); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        game_id: self.currentGameId,
                        score: self.pendingScore,
                        player_name: playerName
                    })
                })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    self.closeModal();
                    if (data.success) {
                        // alert('🎉 آفرین! امتیاز ' + data.score + ' با موفقیت ثبت شد و رتبه شما: ' + data.rank + ' شد!');
                        self.renderLeaderboardRows(data.leaderboard);
                    } else {
                        // alert(data.message || 'خطا در ثبت امتیاز.');
                    }
                })
                .catch(function(err) {
                    self.closeModal();
                    console.error(err);
                });
            },

            refreshLeaderboard: function(gameId) {
                var self = this;
                fetch('<?php echo esc_url_raw(rest_url('ghorbanikids/v1/leaderboard?game_id=')); ?>' + gameId)
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data.success) {
                        self.renderLeaderboardRows(data.leaderboard);
                    }
                });
            },

            renderLeaderboardRows: function(rows) {
                var container = document.getElementById('gk_lb_items_container');
                if (!container) return;

                if (!rows || rows.length === 0) {
                    container.innerHTML = '<div class="gk-lb-empty-notice"><p>🎮 هنوز رکوردی برای این بازی ثبت نشده است. همین حالا بازی کنید و اولین قهرمان باشید!</p></div>';
                    return;
                }

                var html = '';
                rows.forEach(function(r) {
                    var rankClass = (r.rank <= 3) ? 'rank-' + r.rank : '';
                    var badge = r.medal ? r.medal : r.rank;
                    html += '<div class="gk-lb-row-item ' + rankClass + '">' +
                        '<div class="gk-lb-left-col">' +
                            '<div class="gk-lb-rank-badge">' + badge + '</div>' +
                            '<span class="gk-lb-player-name">' + r.player_name + '</span>' +
                        '</div>' +
                        '<div class="gk-lb-score-col">' +
                            '<span class="gk-lb-time-ago">' + r.time_ago + '</span>' +
                            '<span class="gk-lb-score-val">' + r.score_fmt + ' امتیاز</span>' +
                        '</div>' +
                    '</div>';
                });
                container.innerHTML = html;
            }
        };

        document.addEventListener('DOMContentLoaded', function() {
            GhorbaniKidsScore.initListener();
        });
        </script>
        <?php
    }
}

GhorbaniKids_Leaderboard::get_instance();