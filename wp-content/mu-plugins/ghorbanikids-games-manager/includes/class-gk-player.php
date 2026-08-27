<?php
/**
 * Single Game Player & Content Renderer for GhorbaniKids
 */

if (!defined('ABSPATH')) {
    exit;
}

class GK_Player {

    public static function init() {
        $instance = new self();
        add_filter('the_content', [$instance, 'render_game_content']);
        add_action('template_redirect', [$instance, 'disable_cache_for_game_players']);
        add_action('wp_enqueue_scripts', [$instance, 'enqueue_assets'], 10);
        add_action('wp_footer', [$instance, 'inject_public_score_celebration_modal']);
        add_action('wp_ajax_gk_submit_public_game_score', [$instance, 'ajax_submit_public_score']);
        add_action('wp_ajax_gk_submit_game_comment', [$instance, 'ajax_submit_comment']);
        add_action('wp_ajax_nopriv_gk_submit_game_comment', [$instance, 'ajax_submit_comment']);
        add_action('wp_ajax_nopriv_gk_submit_public_game_score', [$instance, 'ajax_submit_public_score']);
    }

    public function enqueue_assets() {
        $assets_url = plugins_url('assets', dirname(__FILE__));
        if (is_singular('gk_game')) {
            $css_ver = file_exists(dirname(dirname(__FILE__)) . '/assets/css/gk-player.css') ? filemtime(dirname(dirname(__FILE__)) . '/assets/css/gk-player.css') : time();
            wp_enqueue_style('gk-player', $assets_url . '/css/gk-player.css', [], $css_ver);
            wp_enqueue_script('gk-player', $assets_url . '/js/gk-player.js', [], GK_GAMES_MANAGER_VERSION . '.2', true);
        }
    }

    public function disable_cache_for_game_players() {
        if (is_singular('gk_game') || is_singular('game') || is_page('games') || is_page('curriculum') || (isset($_GET['st_token']) && !empty($_GET['st_token']))) {
            nocache_headers();
            if (defined('LSCWP_V')) {
                do_action('litespeed_control_set_nocache', 'Single game or player dynamic view');
            }
            if (!headers_sent()) {
                header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
                header("Pragma: no-cache");
                header("Expires: Wed, 11 Jan 1984 05:00:00 GMT");
                header("X-LiteSpeed-Cache-Control: no-cache");
            }
        }
    }

    public function render_game_content($content) {
        if (is_singular('gk_game') && in_the_loop() && is_main_query()) {
            $post_id     = get_the_ID();
            $game_folder = get_post_meta($post_id, '_gk_game_folder', true);
            $aspect      = get_post_meta($post_id, '_gk_game_aspect', true) ?: '16/9';
            $access_type = get_post_meta($post_id, '_gk_game_access', true) ?: 'premium';

            $aspect_class = 'aspect-16-9';
            if ($aspect === '4/3') $aspect_class = 'aspect-4-3';
            if ($aspect === '1/1') $aspect_class = 'aspect-1-1';
            if ($aspect === 'fullscreen') $aspect_class = 'aspect-fullscreen';

            $age_terms   = get_the_terms($post_id, 'game_age_group');
            $cat_terms   = get_the_terms($post_id, 'game_category');
            $grade_terms = get_the_terms($post_id, 'school_grade');

            $is_curriculum = (!empty($grade_terms) && !is_wp_error($grade_terms));
            $back_url      = home_url('/games/');
            $back_label    = '⬅️ همه بازی‌ها';

            if ($is_curriculum) {
                $first_grade = reset($grade_terms);
                $grade_slug  = $first_grade->slug;
                $grade_name  = $first_grade->name;
                $back_url    = home_url('/curriculum/?grade=' . $grade_slug);
                $back_label  = '⬅️ بازی‌های درسی ' . esc_html($grade_name);
            }

            if (isset($_GET['st_token']) && !empty($_GET['st_token'])) {
                $back_url = add_query_arg('st_token', sanitize_text_field($_GET['st_token']), $back_url);
            }

            $sub_status = GK_Subscriptions::user_has_active_subscription();
            $has_access = ($access_type === 'free') || ($sub_status !== false);

            $game_url = '';
            if (!empty($game_folder)) {
                $game_url = content_url('games/' . trailingslashit($game_folder));
                
                // انتقال توکن دانش‌آموز فقط به آی‌فریم بازی مطابق قوانین سیستم مدارس
                if (isset($_GET['st_token']) && !empty($_GET['st_token'])) {
                    $game_url = add_query_arg('st_token', sanitize_text_field($_GET['st_token']), $game_url);
                }
            }

            ob_start();
            ?>
            <div class="gk-game-container" id="gkGameContainer" data-game-id="<?php echo esc_attr($post_id); ?>">
                <!-- ۱. نوار بالای بازی: عنوان و دکمه‌های ناوبری و تمام‌صفحه -->
                <div class="gk-game-header">
                    <h1 class="gk-game-page-title"><?php echo esc_html(get_the_title($post_id)); ?></h1>
                    <div class="gk-game-actions">
                        <a href="<?php echo esc_url($back_url); ?>" class="gk-btn gk-btn-back"><?php echo esc_html($back_label); ?></a>
                        <?php if ($has_access): ?>
                            <button class="gk-btn gk-btn-fullscreen" onclick="toggleGkFullscreen()">⛶ تمام‌صفحه</button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ۲. قاب اصلی اجرای بازی -->
                <?php if ($has_access): ?>
                    <div class="gk-player-wrapper <?php echo esc_attr($aspect_class); ?>" id="gkPlayerWrapper">
                        <?php if (!empty($game_url)): ?>
                            <iframe src="<?php echo esc_url($game_url); ?>" class="gk-game-iframe" id="gkGameIframe" allow="autoplay; fullscreen; gamepad; accelerometer; gyroscope; orientation-lock" allowfullscreen="true" scrolling="no"></iframe>
                        <?php else: ?>
                            <div style="color: #fff; padding: 40px; text-align: center;">
                                <p style="font-size: 1.2rem;">⚠️ هنوز پوشه بازی برای این پست انتخاب نشده است.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="gk-locked-box">
                        <div class="gk-lock-icon">🔒</div>
                        <h2 class="gk-lock-title">این بازی مخصوص اعضای ویژه قربانی کیدز است!</h2>
                        <p class="gk-lock-desc">
                            برای ورود به دنیای بازی‌های مهارتی، فکری و آموزشی و دسترسی به تمام مراحل این بازی، اشتراک ویژه خود را فعال کنید.
                        </p>
                        <div class="gk-lock-buttons">
                            <a href="<?php echo esc_url(home_url('/pricing/')); ?>" class="gk-btn gk-btn-subscribe">👑 مشاهده و خرید پلن‌های اشتراک 🚀</a>
                            <?php if (!is_user_logged_in()): ?>
                                <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="gk-btn gk-btn-login">🔑 قبلاً ثبت‌نام کرده‌اید؟ ورود</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- ۳. نشان‌های مشخصات بازی در زیر کادر بازی -->
                <div class="gk-game-meta-bar">
                    <div class="gk-game-badges">
                        <?php if ($access_type === 'free'): ?>
                            <span class="gk-badge gk-badge-free">🟢 بازی رایگان</span>
                        <?php elseif ($sub_status): ?>
                            <span class="gk-badge gk-badge-vip">👑 اشتراک فعال (<?php echo $sub_status['days_left']; ?> روز مانده)</span>
                        <?php else: ?>
                            <span class="gk-badge gk-badge-vip">🔒 نیازمند اشتراک ویژه</span>
                        <?php endif; ?>

                        <?php if (!empty($age_terms) && !is_wp_error($age_terms)): ?>
                            <?php foreach ($age_terms as $age): ?>
                                <span class="gk-badge gk-badge-age">🎂 <?php echo esc_html($age->name); ?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <?php if (!empty($cat_terms) && !is_wp_error($cat_terms)): ?>
                            <?php foreach ($cat_terms as $cat): ?>
                                <span class="gk-badge gk-badge-cat">🧠 <?php echo esc_html($cat->name); ?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty(trim($content))): ?>
                    <div class="gk-game-footer">
                        <h3>📖 راهنما و اهداف آموزشی بازی</h3>
                        <?php echo $content; ?>
                    </div>
                <?php endif; ?>

                <!-- ۴. تابلوی برترین رکوردهای بازی (Leaderboard) -->
                <?php
                global $wpdb;
                $table_scores = $wpdb->prefix . 'gk_game_scores';
                $top_scores = $wpdb->get_results($wpdb->prepare(
                    "SELECT player_name, MAX(score) as max_score, MAX(created_at) as record_date 
                     FROM {$table_scores} 
                     WHERE game_id = %d 
                     GROUP BY player_name 
                     ORDER BY max_score DESC, record_date ASC 
                     LIMIT 10",
                    $post_id
                ));
                ?>
                <div class="gk-game-leaderboard-card" id="gkGameLeaderboardSection">
                    <div class="gk-leaderboard-header">
                        <div class="gk-lead-title-box">
                            <span class="gk-lead-icon">🏆</span>
                            <div>
                                <h3 class="gk-lead-title">تابلوی قهرمانان و برترین رکوردهای بازی</h3>
                                <span class="gk-lead-subtitle">برترین رکوردهای ثبت‌شده توسط کودکان و نوآموزان</span>
                            </div>
                        </div>
                        <span class="gk-lead-badge" id="gkLeadCountBadge"><?php echo count($top_scores); ?> رکورد برتر</span>
                    </div>

                    <div id="gkLeadDynamicContainer">
                        <?php if (!empty($top_scores)): ?>
                            <div class="gk-lead-table-wrapper" id="gkLeadTableWrapper">
                                <table class="gk-lead-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 15%; text-align: center;">رتبه</th>
                                            <th style="width: 45%;">نام قهرمان</th>
                                            <th style="width: 25%; text-align: center;">امتیاز کسب‌شده</th>
                                            <th style="width: 15%; text-align: center;">زمان</th>
                                        </tr>
                                    </thead>
                                    <tbody id="gkLeadTableBody">
                                        <?php 
                                        $rank = 1;
                                        foreach ($top_scores as $row): 
                                            $medal = '';
                                            $rank_class = '';
                                            if ($rank === 1) { $medal = '🥇'; $rank_class = 'rank-gold'; }
                                            elseif ($rank === 2) { $medal = '🥈'; $rank_class = 'rank-silver'; }
                                            elseif ($rank === 3) { $medal = '🥉'; $rank_class = 'rank-bronze'; }
                                            else { $medal = (string) $rank; }

                                            $time_ago = human_time_diff(strtotime($row->record_date), current_time('timestamp')) . ' پیش';
                                        ?>
                                            <tr class="<?php echo $rank_class; ?>">
                                                <td style="text-align: center;">
                                                    <span class="gk-rank-badge <?php echo $rank_class; ?>">
                                                        <?php echo $medal; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="gk-lead-player-cell">
                                                        <span class="gk-player-avatar">🧑‍🦱</span>
                                                        <span class="gk-player-name"><?php echo esc_html($row->player_name ?: 'نوآموز قهرمان'); ?></span>
                                                    </div>
                                                </td>
                                                <td style="text-align: center;">
                                                    <span class="gk-lead-score-pill">
                                                        ⭐ <?php echo number_format($row->max_score); ?>
                                                    </span>
                                                </td>
                                                <td style="text-align: center; font-size: 11.5px; color: #94a3b8;">
                                                    <?php echo esc_html($time_ago); ?>
                                                </td>
                                            </tr>
                                        <?php 
                                            $rank++;
                                        endforeach; 
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="gk-lead-empty-box" id="gkLeadEmptyBox">
                                <span style="font-size: 40px; margin-bottom: 8px;">🚀</span>
                                <h4 style="margin: 0 0 6px 0; font-size: 15px; font-weight: 900; color: #0f172a;">اولین قهرمان این بازی باشید!</h4>
                                <p style="margin: 0; font-size: 12.5px; color: #64748b;">هنوز امتیازی برای این بازی ثبت نشده است. بازی را شروع کنید و بالاترین رکورد را به نام خود ثبت کنید.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ۵. بخش نظرات و امتیازات بازی (Comments & Ratings) -->
                <?php
                $comments = get_comments([
                    'post_id' => $post_id,
                    'status'  => 'approve',
                    'order'   => 'DESC'
                ]);
                $comment_count = count($comments);
                $is_logged = is_user_logged_in();
                $cur_user = $is_logged ? wp_get_current_user() : null;
                $user_name = $cur_user ? ($cur_user->display_name ?: $cur_user->user_login) : '';
                $ajax_url  = admin_url('admin-ajax.php');
                ?>
                <div class="gk-game-comments-card" id="gkGameCommentsSection">
                    <div class="gk-comments-header">
                        <div class="gk-comments-title-box">
                            <span class="gk-comments-icon">💬</span>
                            <div>
                                <h3 class="gk-comments-title">نظرات و تجربیات بازیکنان و والدین</h3>
                                <span class="gk-comments-subtitle">شما هم نظرتان را درباره این بازی با ما در میان بگذارید</span>
                            </div>
                        </div>
                        <span class="gk-comments-count-badge"><?php echo $comment_count; ?> دیدگاه</span>
                    </div>

                    <!-- فرم ارسال دیدگاه جدید -->
                    <div class="gk-comment-form-wrap">
                        <h4 class="gk-form-heading">✍️ نظر یا تجربه خود را بنویسید</h4>
                        <form id="gkGameCommentForm" class="gk-comment-form">
                            <input type="hidden" name="game_id" value="<?php echo esc_attr($post_id); ?>" />
                            
                            <div class="gk-form-row">
                                <div class="gk-form-group gk-form-name">
                                    <label for="gkCommentAuthor">نام و نام خانوادگی:</label>
                                    <input type="text" id="gkCommentAuthor" name="author" value="<?php echo esc_attr($user_name); ?>" placeholder="مثلاً: علی یا مادر پارسا" required />
                                </div>
                                
                                <div class="gk-form-group gk-form-rating">
                                    <label>امتیاز به بازی:</label>
                                    <div class="gk-star-rating-select" id="gkStarRatingSelect">
                                        <input type="radio" id="star5" name="rating" value="5" checked /><label for="star5" title="عالی (۵ ستاره)">★</label>
                                        <input type="radio" id="star4" name="rating" value="4" /><label for="star4" title="خیلی خوب (۴ ستاره)">★</label>
                                        <input type="radio" id="star3" name="rating" value="3" /><label for="star3" title="خوب (۳ ستاره)">★</label>
                                        <input type="radio" id="star2" name="rating" value="2" /><label for="star2" title="متوسط (۲ ستاره)">★</label>
                                        <input type="radio" id="star1" name="rating" value="1" /><label for="star1" title="ضعیف (۱ ستاره)">★</label>
                                    </div>
                                </div>
                            </div>

                            <div class="gk-form-group">
                                <label for="gkCommentText">متن نظر شما:</label>
                                <textarea id="gkCommentText" name="comment" rows="3" placeholder="نظرت در مورد این بازی چیه؟ کدوم قسمتش رو بیشتر دوست داشتی؟" required></textarea>
                            </div>

                            <div class="gk-form-actions">
                                <button type="submit" id="gkBtnSubmitComment" class="gk-btn-submit-comment">
                                    🚀 ارسال نظر قشنگم
                                </button>
                                <span id="gkCommentFormStatus" class="gk-form-status-msg" style="display:none;"></span>
                            </div>
                        </form>
                    </div>

                    <!-- لیست نظرات ثبت‌شده -->
                    <div class="gk-comments-list" id="gkCommentsList">
                        <?php if (!empty($comments)): ?>
                            <?php foreach ($comments as $c): 
                                $rating = intval(get_comment_meta($c->comment_ID, '_gk_rating', true)) ?: 5;
                                $c_time = human_time_diff(strtotime($c->comment_date), current_time('timestamp')) . ' پیش';
                            ?>
                                <div class="gk-comment-item">
                                    <div class="gk-comment-top">
                                        <div class="gk-c-author-info">
                                            <span class="gk-c-avatar">🧑‍🦱</span>
                                            <div>
                                                <h5 class="gk-c-author-name"><?php echo esc_html($c->comment_author); ?></h5>
                                                <span class="gk-c-date"><?php echo esc_html($c_time); ?></span>
                                            </div>
                                        </div>
                                        <div class="gk-c-stars">
                                            <?php 
                                            for ($i = 1; $i <= 5; $i++) {
                                                echo $i <= $rating ? '<span class="star-on">★</span>' : '<span class="star-off">☆</span>';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <div class="gk-comment-body">
                                        <?php echo nl2br(esc_html($c->comment_content)); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="gk-comments-empty" id="gkCommentsEmptyMsg">
                                <span style="font-size: 34px; margin-bottom: 6px;">✨</span>
                                <h4 style="margin:0 0 4px 0; font-size:14.5px; font-weight:900; color:#0f172a;">اولین نفری باشید که نظر می‌دهد!</h4>
                                <span style="font-size:12px; color:#64748b;">تجربه خود را از انجام این بازی بنویسید تا دیگران هم بخوانند.</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <style>
                .gk-game-comments-card {
                    background: #ffffff !important;
                    border: 2px solid #e0f2fe !important;
                    border-radius: 24px !important;
                    padding: 24px 22px !important;
                    margin-top: 24px !important;
                    box-shadow: 0 8px 30px rgba(2, 132, 199, 0.06) !important;
                    direction: rtl !important;
                    font-family: 'IRANSansXFaNum', 'IRANSansX', sans-serif !important;
                }
                .gk-comments-header {
                    display: flex !important;
                    align-items: center !important;
                    justify-content: space-between !important;
                    padding-bottom: 16px !important;
                    border-bottom: 1.5px solid #f1f5f9 !important;
                    margin-bottom: 20px !important;
                    flex-wrap: wrap !important;
                    gap: 10px !important;
                }
                .gk-comments-title-box {
                    display: flex !important;
                    align-items: center !important;
                    gap: 12px !important;
                }
                .gk-comments-icon {
                    font-size: 30px !important;
                }
                .gk-comments-title {
                    font-size: 1.15rem !important;
                    font-weight: 900 !important;
                    color: #0f172a !important;
                    margin: 0 0 4px 0 !important;
                }
                .gk-comments-subtitle {
                    font-size: 12px !important;
                    color: #64748b !important;
                }
                .gk-comments-count-badge {
                    background: #f0f9ff !important;
                    color: #0284c7 !important;
                    border: 1px solid #bae6fd !important;
                    font-size: 12px !important;
                    font-weight: 900 !important;
                    padding: 4px 14px !important;
                    border-radius: 99px !important;
                }
                .gk-comment-form-wrap {
                    background: #f8fafc !important;
                    border: 1.5px solid #e2e8f0 !important;
                    border-radius: 20px !important;
                    padding: 18px 20px !important;
                    margin-bottom: 24px !important;
                }
                .gk-form-heading {
                    margin: 0 0 14px 0 !important;
                    font-size: 14px !important;
                    font-weight: 900 !important;
                    color: #1e293b !important;
                }
                .gk-form-row {
                    display: grid !important;
                    grid-template-columns: 1fr 1fr !important;
                    gap: 14px !important;
                    margin-bottom: 12px !important;
                }
                @media (max-width: 640px) {
                    .gk-form-row {
                        grid-template-columns: 1fr !important;
                    }
                }
                .gk-form-group {
                    display: flex !important;
                    flex-direction: column !important;
                    gap: 6px !important;
                }
                .gk-form-group label {
                    font-size: 12.5px !important;
                    font-weight: 800 !important;
                    color: #334155 !important;
                }
                .gk-form-group input, .gk-form-group textarea {
                    width: 100% !important;
                    padding: 10px 14px !important;
                    border-radius: 14px !important;
                    border: 1.5px solid #cbd5e1 !important;
                    font-family: inherit !important;
                    font-size: 13.5px !important;
                    font-weight: 700 !important;
                    outline: none !important;
                    box-sizing: border-box !important;
                    background: #ffffff !important;
                    transition: all 0.2s ease !important;
                }
                .gk-form-group input:focus, .gk-form-group textarea:focus {
                    border-color: #0284c7 !important;
                    box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15) !important;
                }
                .gk-star-rating-select {
                    display: inline-flex !important;
                    flex-direction: row-reverse !important;
                    justify-content: flex-end !important;
                    gap: 4px !important;
                    padding-top: 4px !important;
                }
                .gk-star-rating-select input {
                    display: none !important;
                }
                .gk-star-rating-select label {
                    font-size: 24px !important;
                    color: #cbd5e1 !important;
                    cursor: pointer !important;
                    transition: color 0.15s ease !important;
                    line-height: 1 !important;
                }
                .gk-star-rating-select input:checked ~ label,
                .gk-star-rating-select label:hover,
                .gk-star-rating-select label:hover ~ label {
                    color: #f59e0b !important;
                }
                .gk-form-actions {
                    display: flex !important;
                    align-items: center !important;
                    gap: 12px !important;
                    margin-top: 14px !important;
                    flex-wrap: wrap !important;
                }
                .gk-btn-submit-comment {
                    background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%) !important;
                    color: #ffffff !important;
                    border: none !important;
                    padding: 10px 22px !important;
                    border-radius: 14px !important;
                    font-size: 13.5px !important;
                    font-weight: 900 !important;
                    cursor: pointer !important;
                    box-shadow: 0 4px 14px rgba(2, 132, 199, 0.3) !important;
                    transition: all 0.2s ease !important;
                    font-family: inherit !important;
                }
                .gk-btn-submit-comment:hover {
                    transform: translateY(-2px) !important;
                    box-shadow: 0 6px 18px rgba(2, 132, 199, 0.4) !important;
                }
                .gk-form-status-msg {
                    font-size: 12.5px !important;
                    font-weight: 800 !important;
                    padding: 6px 14px !important;
                    border-radius: 12px !important;
                }
                .gk-comments-list {
                    display: flex !important;
                    flex-direction: column !important;
                    gap: 12px !important;
                }
                .gk-comment-item {
                    background: #f8fafc !important;
                    border: 1.5px solid #f1f5f9 !important;
                    border-radius: 18px !important;
                    padding: 16px 18px !important;
                    transition: all 0.2s ease !important;
                }
                .gk-comment-item.gk-new-comment-pulse {
                    animation: gkCommentPulse 2.5s ease-out !important;
                    border-color: #86efac !important;
                    background: #f0fdf4 !important;
                }
                @keyframes gkCommentPulse {
                    0% { transform: scale(0.96); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.4); }
                    50% { transform: scale(1.01); box-shadow: 0 0 0 10px rgba(34, 197, 94, 0); }
                    100% { transform: scale(1); border-color: #f1f5f9; background: #f8fafc; }
                }
                .gk-comment-item:hover {
                    border-color: #e0f2fe !important;
                    background: #ffffff !important;
                    box-shadow: 0 4px 14px rgba(2, 132, 199, 0.05) !important;
                }
                .gk-comment-top {
                    display: flex !important;
                    align-items: center !important;
                    justify-content: space-between !important;
                    margin-bottom: 8px !important;
                }
                .gk-c-author-info {
                    display: flex !important;
                    align-items: center !important;
                    gap: 10px !important;
                }
                .gk-c-avatar {
                    font-size: 22px !important;
                    width: 38px !important;
                    height: 38px !important;
                    border-radius: 50% !important;
                    background: #e0f2fe !important;
                    display: flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                }
                .gk-c-author-name {
                    margin: 0 0 2px 0 !important;
                    font-size: 13.5px !important;
                    font-weight: 900 !important;
                    color: #0f172a !important;
                }
                .gk-c-date {
                    font-size: 11.5px !important;
                    color: #94a3b8 !important;
                }
                .gk-c-stars {
                    color: #f59e0b !important;
                    font-size: 16px !important;
                    letter-spacing: 2px !important;
                }
                .gk-c-stars .star-off {
                    color: #cbd5e1 !important;
                }
                .gk-comment-body {
                    font-size: 13px !important;
                    color: #334155 !important;
                    line-height: 1.6 !important;
                    padding-right: 48px !important;
                }
                .gk-comments-empty {
                    text-align: center !important;
                    padding: 24px 16px !important;
                    display: flex !important;
                    flex-direction: column !important;
                    align-items: center !important;
                    background: #f8fafc !important;
                    border-radius: 18px !important;
                    border: 1.5px dashed #cbd5e1 !important;
                }
                </style>

                <script>
                (function() {
                    var form = document.getElementById('gkGameCommentForm');
                    if (!form) return;

                    form.addEventListener('submit', function(e) {
                        e.preventDefault();
                        var btn = document.getElementById('gkBtnSubmitComment');
                        var status = document.getElementById('gkCommentFormStatus');
                        var author = document.getElementById('gkCommentAuthor').value.trim();
                        var comment = document.getElementById('gkCommentText').value.trim();
                        var ratingEl = form.querySelector('input[name="rating"]:checked');
                        var rating = ratingEl ? ratingEl.value : 5;

                        if (!author || !comment) {
                            alert('لطفاً نام و متن نظر خود را وارد کنید.');
                            return;
                        }

                        btn.disabled = true;
                        btn.textContent = 'در حال ارسال... ⏳';

                        var formData = new FormData();
                        formData.append('action', 'gk_submit_game_comment');
                        formData.append('game_id', <?php echo intval($post_id); ?>);
                        formData.append('author', author);
                        formData.append('comment', comment);
                        formData.append('rating', rating);

                        fetch('<?php echo esc_url($ajax_url); ?>', {
                            method: 'POST',
                            body: formData
                        })
                        .then(function(res){ return res.json(); })
                        .then(function(data) {
                            if (data.success) {
                                status.style.display = 'inline-block';
                                status.style.background = '#dcfce7';
                                status.style.color = '#166534';
                                status.style.border = '1.5px solid #86efac';
                                status.textContent = '✅ ' + data.data.message;
                                
                                document.getElementById('gkCommentText').value = '';
                                btn.disabled = false;
                                btn.textContent = '🚀 ارسال نظر قشنگم';

                                // ساخت و درج درجا کارت دیدگاه بدون رفرش صفحه (Zero-Reload)
                                var cData = data.data;
                                var starsHtml = '';
                                for (var i = 1; i <= 5; i++) {
                                    starsHtml += (i <= cData.rating) ? '<span class="star-on">★</span>' : '<span class="star-off">☆</span>';
                                }

                                var newComment = document.createElement('div');
                                newComment.className = 'gk-comment-item gk-new-comment-pulse';
                                newComment.innerHTML = '<div class="gk-comment-top">' +
                                    '<div class="gk-c-author-info">' +
                                        '<span class="gk-c-avatar">🧑‍🦱</span>' +
                                        '<div>' +
                                            '<h5 class="gk-c-author-name">' + cData.author + '</h5>' +
                                            '<span class="gk-c-date">' + cData.time + '</span>' +
                                        '</div>' +
                                    '</div>' +
                                    '<div class="gk-c-stars">' + starsHtml + '</div>' +
                                '</div>' +
                                '<div class="gk-comment-body">' + cData.comment + '</div>';

                                var list = document.getElementById('gkCommentsList');
                                var emptyMsg = document.getElementById('gkCommentsEmptyMsg');
                                if (emptyMsg) emptyMsg.style.display = 'none';
                                if (list) {
                                    list.insertBefore(newComment, list.firstChild);
                                    newComment.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                }

                                var countBadge = document.querySelector('.gk-comments-count-badge');
                                if (countBadge) {
                                    var count = parseInt(countBadge.textContent, 10) || 0;
                                    countBadge.textContent = (count + 1) + ' دیدگاه';
                                }

                                setTimeout(function() {
                                    status.style.display = 'none';
                                }, 4000);
                            } else {
                                status.style.display = 'inline-block';
                                status.style.background = '#fee2e2';
                                status.style.color = '#991b1b';
                                status.style.border = '1px solid #fecaca';
                                status.textContent = '❌ ' + (data.data || 'خطا در ثبت نظر');
                                btn.disabled = false;
                                btn.textContent = '🚀 ارسال نظر قشنگم';
                            }
                        })
                        .catch(function(err) {
                            btn.disabled = false;
                            btn.textContent = '🚀 ارسال نظر قشنگم';
                            status.style.display = 'inline-block';
                            status.style.background = '#fee2e2';
                            status.style.color = '#991b1b';
                            status.textContent = '❌ خطا در برقراری ارتباط با سرور';
                        });
                    });
                })();
                </script>

                <style>
                .gk-game-leaderboard-card {
                    background: #ffffff !important;
                    border: 2px solid #e0f2fe !important;
                    border-radius: 24px !important;
                    padding: 22px !important;
                    margin-top: 24px !important;
                    box-shadow: 0 8px 30px rgba(2, 132, 199, 0.06) !important;
                    direction: rtl !important;
                    font-family: 'IRANSansXFaNum', 'IRANSansX', sans-serif !important;
                    box-sizing: border-box !important;
                    width: 100% !important;
                    overflow: hidden !important;
                }
                .gk-leaderboard-header {
                    display: flex !important;
                    align-items: center !important;
                    justify-content: space-between !important;
                    padding-bottom: 16px !important;
                    border-bottom: 1.5px solid #f1f5f9 !important;
                    margin-bottom: 16px !important;
                    flex-wrap: wrap !important;
                    gap: 10px !important;
                }
                .gk-lead-title-box {
                    display: flex !important;
                    align-items: center !important;
                    gap: 12px !important;
                }
                .gk-lead-icon {
                    font-size: 32px !important;
                }
                .gk-lead-title {
                    font-size: 1.15rem !important;
                    font-weight: 900 !important;
                    color: #0f172a !important;
                    margin: 0 0 4px 0 !important;
                }
                .gk-lead-subtitle {
                    font-size: 12px !important;
                    color: #64748b !important;
                }
                .gk-lead-badge {
                    background: #f0fdf4 !important;
                    color: #16a34a !important;
                    border: 1px solid #86efac !important;
                    font-size: 12px !important;
                    font-weight: 900 !important;
                    padding: 4px 14px !important;
                    border-radius: 99px !important;
                    white-space: nowrap !important;
                }
                .gk-lead-table-wrapper {
                    overflow-x: hidden !important;
                    border-radius: 16px !important;
                    border: 1.5px solid #f1f5f9 !important;
                    width: 100% !important;
                    box-sizing: border-box !important;
                }
                .gk-lead-table {
                    width: 100% !important;
                    border-collapse: collapse !important;
                    font-size: 13.5px !important;
                    table-layout: fixed !important;
                }
                .gk-lead-table th {
                    background: #f8fafc !important;
                    color: #475569 !important;
                    font-weight: 900 !important;
                    padding: 12px 10px !important;
                    border-bottom: 1.5px solid #e2e8f0 !important;
                    white-space: nowrap !important;
                }
                .gk-lead-table td {
                    padding: 12px 10px !important;
                    border-bottom: 1px solid #f1f5f9 !important;
                    color: #1e293b !important;
                }
                .gk-lead-table tr:last-child td {
                    border-bottom: none !important;
                }
                .gk-lead-table tr:hover {
                    background: #f0f9ff !important;
                }
                .gk-rank-badge {
                    display: inline-flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    width: 32px !important;
                    height: 32px !important;
                    border-radius: 50% !important;
                    font-size: 14px !important;
                    font-weight: 900 !important;
                    background: #f1f5f9 !important;
                    color: #475569 !important;
                }
                .gk-rank-badge.rank-gold {
                    background: linear-gradient(135deg, #fef08a 0%, #fde047 100%) !important;
                    box-shadow: 0 2px 8px rgba(234, 179, 8, 0.3) !important;
                    font-size: 18px !important;
                }
                .gk-rank-badge.rank-silver {
                    background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%) !important;
                    font-size: 18px !important;
                }
                .gk-rank-badge.rank-bronze {
                    background: linear-gradient(135deg, #fed7aa 0%, #fdba74 100%) !important;
                    font-size: 18px !important;
                }
                .gk-lead-player-cell {
                    display: flex !important;
                    align-items: center !important;
                    gap: 8px !important;
                    min-width: 0 !important;
                    overflow: hidden !important;
                }
                .gk-player-avatar {
                    font-size: 18px !important;
                    flex-shrink: 0 !important;
                }
                .gk-player-name {
                    font-weight: 800 !important;
                    color: #0f172a !important;
                    white-space: nowrap !important;
                    overflow: hidden !important;
                    text-overflow: ellipsis !important;
                    display: block !important;
                }
                .gk-lead-score-pill {
                    display: inline-flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    gap: 4px !important;
                    background: #eff6ff !important;
                    color: #0284c7 !important;
                    border: 1.5px solid #bae6fd !important;
                    padding: 4px 10px !important;
                    border-radius: 99px !important;
                    font-weight: 900 !important;
                    font-size: 12.5px !important;
                    white-space: nowrap !important;
                }
                .gk-lead-empty-box {
                    text-align: center !important;
                    padding: 30px 16px !important;
                    display: flex !important;
                    flex-direction: column !important;
                    align-items: center !important;
                    background: #f8fafc !important;
                    border-radius: 18px !important;
                    border: 1.5px dashed #cbd5e1 !important;
                }

                @media (max-width: 768px) {
                    .gk-game-leaderboard-card {
                        padding: 16px 12px !important;
                        border-radius: 20px !important;
                        margin-top: 16px !important;
                    }
                    .gk-lead-title {
                        font-size: 15px !important;
                    }
                    .gk-lead-subtitle {
                        font-size: 11px !important;
                    }
                    .gk-lead-table th, 
                    .gk-lead-table td {
                        padding: 10px 6px !important;
                        font-size: 12px !important;
                    }
                    /* در موبایل ستون زمان مخفی می‌شود تا ۳ ستون اصلی با فضای عالی و بدون لغزش نمایش داده شوند */
                    .gk-lead-table th:nth-child(4),
                    .gk-lead-table td:nth-child(4) {
                        display: none !important;
                    }
                    .gk-lead-table th:nth-child(1),
                    .gk-lead-table td:nth-child(1) {
                        width: 42px !important;
                    }
                    .gk-lead-table th:nth-child(2),
                    .gk-lead-table td:nth-child(2) {
                        width: auto !important;
                    }
                    .gk-lead-table th:nth-child(3),
                    .gk-lead-table td:nth-child(3) {
                        width: 85px !important;
                    }
                    .gk-rank-badge {
                        width: 26px !important;
                        height: 26px !important;
                        font-size: 12px !important;
                    }
                    .gk-lead-score-pill {
                        padding: 3px 8px !important;
                        font-size: 11.5px !important;
                    }
                }
                </style>
            </div>
            <?php
            return ob_get_clean();
        }

        return $content;
    }

    /**
     * ثبت عمومی امتیاز در تابلوی رکوردهای بازی
     */
    public function ajax_submit_public_score() {
        $game_id = intval($_POST['game_id'] ?? 0);
        $score = intval($_POST['score'] ?? 0);
        $player_name = sanitize_text_field($_POST['player_name'] ?? '');

        if ($game_id <= 0 || $score <= 0) {
            wp_send_json_error('اطلاعات نامعتبر است.');
        }

        if (empty($player_name)) {
            if (is_user_logged_in()) {
                $u = wp_get_current_user();
                $player_name = $u->display_name ?: $u->user_login;
            } else {
                $player_name = 'نوآموز زرنگ';
            }
        }

        global $wpdb;
        $table_scores = $wpdb->prefix . 'gk_game_scores';
        $user_id = get_current_user_id();

        $wpdb->insert($table_scores, [
            'game_id'     => $game_id,
            'user_id'     => $user_id,
            'player_name' => $player_name,
            'score'       => $score,
            'created_at'  => current_time('mysql')
        ], ['%d', '%d', '%s', '%d', '%s']);

        if (has_action('litespeed_purge_post')) {
            do_action('litespeed_purge_post', $game_id);
        }
        if (function_exists('clean_post_cache')) {
            clean_post_cache($game_id);
        }

        // دریافت ۱۰ رکورد برتر جدید جهت به‌روزرسانی زنده جدول
        $top_scores = $wpdb->get_results($wpdb->prepare(
            "SELECT player_name, MAX(score) as max_score, MAX(created_at) as record_date 
             FROM {$table_scores} 
             WHERE game_id = %d 
             GROUP BY player_name 
             ORDER BY max_score DESC, record_date ASC 
             LIMIT 10",
            $game_id
        ));

        $formatted = [];
        $rank = 1;
        foreach ($top_scores as $row) {
            $medal = '';
            $rank_class = '';
            if ($rank === 1) { $medal = '🥇'; $rank_class = 'rank-gold'; }
            elseif ($rank === 2) { $medal = '🥈'; $rank_class = 'rank-silver'; }
            elseif ($rank === 3) { $medal = '🥉'; $rank_class = 'rank-bronze'; }
            else { $medal = (string) $rank; }

            $time_ago = human_time_diff(strtotime($row->record_date), current_time('timestamp')) . ' پیش';

            $formatted[] = [
                'rank'        => $rank,
                'medal'       => $medal,
                'rank_class'  => $rank_class,
                'player_name' => esc_html($row->player_name ?: 'نوآموز قهرمان'),
                'score'       => number_format(intval($row->max_score)),
                'time_ago'    => $time_ago,
                'is_current'  => ($row->player_name === $player_name && intval($row->max_score) === $score)
            ];
            $rank++;
        }

        wp_send_json_success([
            'message'    => 'رکورد شما با موفقیت ثبت شد! 🎉',
            'top_scores' => $formatted
        ]);
    }

    /**
     * تزریق پنجره پاپ‌آپ پایان بازی و ثبت رکورد برای همه کاربران (مهمان، والدین، عمومی)
     */
    public function inject_public_score_celebration_modal() {
        if (!is_singular('gk_game') && !is_singular('game')) {
            return;
        }

        $is_student = isset($_GET['st_token']) && !empty($_GET['st_token']);
        $game_id = get_queried_object_id() ?: get_the_ID();
        $default_player_name = '';
        if (is_user_logged_in()) {
            $u = wp_get_current_user();
            $default_player_name = $u->display_name ?: $u->user_login;
        }
        $ajax_url = admin_url('admin-ajax.php');
        ?>
        <!-- پنجره پاپ‌آپ شاداب ثبت رکورد پایان بازی قربانی کیدز -->
        <div id="gkPublicCelebrationModal" class="gk-celebration-modal-overlay" style="display:none;">
            <div class="gk-celebration-card" style="position:relative;">
                <button type="button" onclick="GkPublicModal.close()" style="position:absolute; top:14px; left:14px; background:#f1f5f9; border:none; border-radius:50%; width:32px; height:32px; font-size:16px; font-weight:bold; color:#64748b; cursor:pointer; display:flex; align-items:center; justify-content:center;">✕</button>
                <div class="gk-cel-confetti">🎉 ⭐ 🏆 ✨</div>
                <h3 class="gk-cel-title">آفرین قهرمان! بازی تموم شد!</h3>
                <p class="gk-cel-desc">رکورد عالی و تلاش فوق‌العاده‌ای داشتی</p>

                <div class="gk-cel-score-box">
                    <span class="gk-cel-score-label">امتیاز کسب‌شده:</span>
                    <span class="gk-cel-score-num" id="gkPublicFinalScore">۰</span>
                </div>

                <div class="gk-cel-input-wrap" id="gkPublicNameWrap">
                    <label for="gkPublicPlayerName" class="gk-cel-input-label">نام قشنگت رو بنویس تا در تابلوی قهرمانان ثبت بشه:</label>
                    <input type="text" id="gkPublicPlayerName" class="gk-cel-input" value="<?php echo esc_attr($default_player_name); ?>" placeholder="مثلاً: علی کوچولو یا نام شما" />
                </div>

                <div class="gk-cel-actions">
                    <button type="button" id="gkBtnSubmitPublicScore" class="gk-btn-cel-submit">
                        🏆 ثبت رکورد در تابلوی بازی
                    </button>
                    <div style="display:flex; gap:8px;">
                        <button type="button" id="gkBtnRestartGame" class="gk-btn-cel-restart" style="flex:1;">
                            🔄 بازی دوباره
                        </button>
                        <button type="button" onclick="GkPublicModal.closeAndScroll()" class="gk-btn-cel-restart" style="flex:1; background:#e0f2fe; color:#0369a1; border-color:#bae6fd;">
                            📊 مشاهده جدول
                        </button>
                    </div>
                </div>

                <div id="gkPublicScoreMsg" class="gk-cel-msg" style="display:none;"></div>
            </div>
        </div>

        <style>
        .gk-celebration-modal-overlay {
            display: none;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            background: rgba(15, 23, 42, 0.75) !important;
            backdrop-filter: blur(8px) !important;
            -webkit-backdrop-filter: blur(8px) !important;
            z-index: 9999999 !important;
        }
        .gk-celebration-modal-overlay.is-active {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 16px !important;
            direction: rtl !important;
            font-family: 'IRANSansXFaNum', 'IRANSansX', sans-serif !important;
        }
        .gk-celebration-card {
            background: #ffffff !important;
            border: 2.5px solid #86efac !important;
            border-radius: 28px !important;
            padding: 26px 22px !important;
            max-width: 440px !important;
            width: 100% !important;
            text-align: center !important;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3) !important;
            animation: gkPopIn 0.35s cubic-bezier(0.16, 1, 0.3, 1) !important;
            box-sizing: border-box !important;
        }
        @keyframes gkPopIn {
            from { transform: scale(0.8) translateY(20px); opacity: 0; }
            to { transform: scale(1) translateY(0); opacity: 1; }
        }
        .gk-cel-confetti {
            font-size: 32px !important;
            margin-bottom: 8px !important;
            animation: gkBounce 1s infinite alternate !important;
        }
        @keyframes gkBounce {
            from { transform: translateY(0); }
            to { transform: translateY(-6px); }
        }
        .gk-cel-title {
            font-family: 'aviny', 'Aviny', 'IRANSansXFaNum', sans-serif !important;
            font-size: 1.6rem !important;
            font-weight: 900 !important;
            color: #0f172a !important;
            margin: 0 0 4px 0 !important;
        }
        .gk-cel-desc {
            font-size: 13px !important;
            color: #64748b !important;
            margin: 0 0 16px 0 !important;
        }
        .gk-cel-score-box {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%) !important;
            border: 2px solid #86efac !important;
            border-radius: 20px !important;
            padding: 14px !important;
            margin-bottom: 16px !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            gap: 4px !important;
        }
        .gk-cel-score-label {
            font-size: 12.5px !important;
            font-weight: 800 !important;
            color: #166534 !important;
        }
        .gk-cel-score-num {
            font-size: 34px !important;
            font-weight: 900 !important;
            color: #15803d !important;
            line-height: 1 !important;
        }
        .gk-cel-input-wrap {
            margin-bottom: 16px !important;
            text-align: right !important;
        }
        .gk-cel-input-label {
            display: block !important;
            font-size: 12px !important;
            font-weight: 800 !important;
            color: #334155 !important;
            margin-bottom: 6px !important;
        }
        .gk-cel-input {
            width: 100% !important;
            padding: 10px 14px !important;
            border-radius: 14px !important;
            border: 1.5px solid #cbd5e1 !important;
            font-family: inherit !important;
            font-size: 14px !important;
            font-weight: 800 !important;
            outline: none !important;
            box-sizing: border-box !important;
            transition: all 0.2s ease !important;
        }
        .gk-cel-input:focus {
            border-color: #0284c7 !important;
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15) !important;
        }
        .gk-cel-actions {
            display: flex !important;
            flex-direction: column !important;
            gap: 8px !important;
        }
        .gk-btn-cel-submit {
            background: linear-gradient(135deg, #16a34a 0%, #15803d 100%) !important;
            color: #ffffff !important;
            border: none !important;
            padding: 12px 20px !important;
            border-radius: 16px !important;
            font-size: 14px !important;
            font-weight: 900 !important;
            cursor: pointer !important;
            box-shadow: 0 4px 14px rgba(22, 163, 74, 0.3) !important;
            transition: all 0.2s ease !important;
        }
        .gk-btn-cel-submit:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 18px rgba(22, 163, 74, 0.4) !important;
        }
        .gk-btn-cel-restart {
            background: #f1f5f9 !important;
            color: #475569 !important;
            border: 1.5px solid #cbd5e1 !important;
            padding: 10px 20px !important;
            border-radius: 16px !important;
            font-size: 13px !important;
            font-weight: 800 !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
        }
        .gk-btn-cel-restart:hover {
            background: #e2e8f0 !important;
            color: #0f172a !important;
        }
        .gk-cel-msg {
            margin-top: 12px !important;
            padding: 8px 12px !important;
            border-radius: 12px !important;
            font-size: 12.5px !important;
            font-weight: 800 !important;
            background: #f0fdf4 !important;
            color: #166534 !important;
            border: 1px solid #86efac !important;
        }
        @keyframes gkHighlightPulse {
            0% { transform: scale(0.98); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.5); }
            50% { transform: scale(1.01); box-shadow: 0 0 0 8px rgba(34, 197, 94, 0); }
            100% { transform: scale(1); }
        }
        .gk-new-record-highlight {
            animation: gkHighlightPulse 1.5s ease-out !important;
        }
        </style>

        <script>
        var GkPublicModal = {
            currentScore: 0,
            currentGameId: (function() {
                var c = document.getElementById('gkGameContainer');
                return c && c.getAttribute('data-game-id') ? parseInt(c.getAttribute('data-game-id'), 10) : <?php echo intval($game_id); ?>;
            })(),
            isStudentMode: <?php echo $is_student ? 'true' : 'false'; ?>,
            ajaxUrl: '<?php echo esc_url($ajax_url); ?>',

            open: function(score) {
                this.currentScore = score;
                var modal = document.getElementById('gkPublicCelebrationModal');
                var scoreEl = document.getElementById('gkPublicFinalScore');
                if (scoreEl) scoreEl.textContent = Number(score).toLocaleString('fa-IR');
                if (modal) {
                    modal.style.display = 'flex';
                    modal.classList.add('is-active');
                }
            },

            close: function() {
                var modal = document.getElementById('gkPublicCelebrationModal');
                if (modal) {
                    modal.classList.remove('is-active');
                    modal.style.display = 'none';
                }
            },

            closeAndScroll: function() {
                this.close();
                var lb = document.getElementById('gkGameLeaderboardSection');
                if (lb) lb.scrollIntoView({ behavior: 'smooth', block: 'center' });
            },

            submit: function() {
                var self = this;
                var btn = document.getElementById('gkBtnSubmitPublicScore');
                var nameInput = document.getElementById('gkPublicPlayerName');
                var playerName = nameInput ? nameInput.value.trim() : '';
                var msgEl = document.getElementById('gkPublicScoreMsg');

                if (btn) {
                    btn.disabled = true;
                    btn.textContent = 'در حال ثبت... ⏳';
                }

                var formData = new FormData();
                formData.append('action', 'gk_submit_public_game_score');
                formData.append('game_id', self.currentGameId);
                formData.append('score', self.currentScore);
                formData.append('player_name', playerName);

                fetch(self.ajaxUrl, {
                    method: 'POST',
                    body: formData
                })
                .then(function(res) { return res.json(); })
                .then(function(res) {
                    if (res && res.success && res.data) {
                        if (msgEl) {
                            msgEl.style.display = 'block';
                            msgEl.textContent = '✅ ' + (res.data.message || 'رکورد شما در تابلوی بازی ثبت شد!');
                        }
                        if (btn) btn.textContent = '✨ ثبت شد!';

                        // به‌روزرسانی فوری جدول رکوردهای پایین صفحه (Zero-Reload)
                        if (res.data.top_scores && res.data.top_scores.length) {
                            self.renderLeaderboard(res.data.top_scores);
                        }

                        setTimeout(function() {
                            self.closeAndScroll();
                            if (btn) {
                                btn.disabled = false;
                                btn.textContent = '🏆 ثبت رکورد در تابلوی بازی';
                            }
                            if (msgEl) msgEl.style.display = 'none';
                        }, 700);
                    } else {
                        if (btn) {
                            btn.disabled = false;
                            btn.textContent = '🏆 ثبت رکورد در تابلوی بازی';
                        }
                        alert(res.data || 'خطا در ثبت رکورد');
                    }
                })
                .catch(function(err) {
                    if (btn) {
                        btn.disabled = false;
                        btn.textContent = '🏆 ثبت رکورد در تابلوی بازی';
                    }
                    console.error(err);
                });
            },

            renderLeaderboard: function(topScores) {
                var countBadge = document.getElementById('gkLeadCountBadge');
                if (countBadge) {
                    countBadge.textContent = topScores.length + ' رکورد برتر';
                }

                var dynContainer = document.getElementById('gkLeadDynamicContainer');
                if (dynContainer) {
                    var rowsHtml = '';
                    topScores.forEach(function(r) {
                        var highlightStyle = r.is_current ? 'background: #dcfce7 !important; border: 2px solid #22c55e;' : '';
                        var highlightClass = r.is_current ? ' gk-new-record-highlight' : '';
                        rowsHtml += '<tr class="' + r.rank_class + highlightClass + '" style="' + highlightStyle + '">' +
                            '<td style="text-align: center;"><span class="gk-rank-badge ' + r.rank_class + '">' + r.medal + '</span></td>' +
                            '<td><div class="gk-lead-player-cell"><span class="gk-player-avatar">🧑‍🦱</span><span class="gk-player-name">' + r.player_name + '</span></div></td>' +
                            '<td style="text-align: center;"><span class="gk-lead-score-pill">⭐ ' + r.score + '</span></td>' +
                            '<td style="text-align: center; font-size: 11.5px; color: #94a3b8;">' + r.time_ago + '</td>' +
                        '</tr>';
                    });

                    dynContainer.innerHTML = '<div class="gk-lead-table-wrapper" id="gkLeadTableWrapper">' +
                        '<table class="gk-lead-table">' +
                            '<thead><tr>' +
                                '<th style="width: 15%; text-align: center;">رتبه</th>' +
                                '<th style="width: 45%;">نام قهرمان</th>' +
                                '<th style="width: 25%; text-align: center;">امتیاز کسب‌شده</th>' +
                                '<th style="width: 15%; text-align: center;">زمان</th>' +
                            '</tr></thead>' +
                            '<tbody id="gkLeadTableBody">' + rowsHtml + '</tbody>' +
                        '</table>' +
                    '</div>';
                }
            },

            restartGame: function() {
                this.close();
                var iframe = document.getElementById('gkGameIframe') || document.querySelector('iframe.gk-game-iframe');
                if (iframe) {
                    var currentSrc = iframe.getAttribute('src') || iframe.src;
                    var cleanSrc = currentSrc.replace(/([?&])_t=\d+/, '');
                    var reloadSrc = cleanSrc + (cleanSrc.indexOf('?') > -1 ? '&' : '?') + '_t=' + new Date().getTime();
                    iframe.src = reloadSrc;
                }
            }
        };

        (function() {
            window.addEventListener('message', function(e) {
                if (!e || !e.data) return;
                
                var iframe = document.getElementById('gkGameIframe') || document.querySelector('iframe.gk-game-iframe');
                if (iframe && e.source && e.source !== iframe.contentWindow) {
                    return;
                }

                if (e.data.type === 'GK_SUBMIT_SCORE' || e.data.type === 'SUBMIT_SCORE') {
                    var parsed = parseInt(e.data.score, 10);
                    if (isNaN(parsed) || parsed <= 0) return;
                    
                    if (!GkPublicModal.isStudentMode) {
                        GkPublicModal.open(parsed);
                    }
                }
            });

            var btnSubmit = document.getElementById('gkBtnSubmitPublicScore');
            if (btnSubmit) {
                btnSubmit.onclick = function() { GkPublicModal.submit(); };
            }

            var btnRestart = document.getElementById('gkBtnRestartGame');
            if (btnRestart) {
                btnRestart.onclick = function(e) { e.preventDefault(); GkPublicModal.restartGame(); };
            }
        })();
        </script>
        <?php
    }

    /**
     * ثبت دیدگاه و امتیاز ستاره‌ای برای بازی
     */
    public function ajax_submit_comment() {
        $game_id = intval($_POST['game_id'] ?? 0);
        $author = sanitize_text_field($_POST['author'] ?? '');
        $comment_text = sanitize_textarea_field($_POST['comment'] ?? '');
        $rating = intval($_POST['rating'] ?? 5);

        if ($game_id <= 0 || empty($author) || empty($comment_text)) {
            wp_send_json_error('لطفاً تمامی فیلدها را پر کنید.');
        }

        $user_id = get_current_user_id();
        $user_email = $user_id ? wp_get_current_user()->user_email : 'guest_' . time() . '@ghorbanikids.ir';

        $comment_id = wp_insert_comment([
            'comment_post_ID'      => $game_id,
            'comment_author'       => $author,
            'comment_author_email' => $user_email,
            'comment_content'      => $comment_text,
            'comment_type'         => 'comment',
            'comment_approved'     => 1, // تایید خودکار نظرات شاداب کودکان
            'user_id'              => $user_id,
            'comment_date'         => current_time('mysql')
        ]);

        if ($comment_id) {
            update_comment_meta($comment_id, '_gk_rating', $rating);
            wp_send_json_success([
                'message'    => 'نظر قشنگ شما با موفقیت ثبت و اضافه شد! 🎉',
                'comment_id' => $comment_id,
                'author'     => esc_html($author),
                'comment'    => nl2br(esc_html($comment_text)),
                'rating'     => $rating,
                'time'       => 'همین الان'
            ]);
        } else {
            wp_send_json_error('خطا در ذخیره‌سازی دیدگاه.');
        }
    }
}
