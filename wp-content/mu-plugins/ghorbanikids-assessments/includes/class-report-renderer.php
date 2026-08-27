<?php
/**
 * Class GK_Report_Renderer
 * Generates the HTML report card and visual dashboards
 */
if (!defined('ABSPATH')) exit;

class GK_Report_Renderer {

    public static function render_report_card($data) {
        $child_name     = esc_html($data['child_name']);
        $child_age      = esc_html($data['child_age']);
        $test_title     = esc_html($data['test_title']);
        $summary        = $data['summary'];
        $sorted_summary = $data['sorted_summary'];
        $top_strengths  = array_slice($sorted_summary, 0, 3, true);
        $recommended    = $data['recommended_games'];

        ob_start();
        ?>
        <div class="gk-report-card-wrapper" id="gk-printable-report">
            
            <!-- هدر کارنامه -->
            <div class="gk-report-header">
                <div class="gk-brand-logo">قربانی کیدز 🎮 | سامانه ارزیابی هوش و رشد شناختی</div>
                <h1 class="gk-report-title">کارنامه تحلیلی استعدادیابی و رشد شناختی</h1>
                <div class="gk-report-meta-badges">
                    <span class="gk-meta-badge">👤 نام کودک: <strong><?php echo $child_name; ?></strong></span>
                    <span class="gk-meta-badge">🎂 سن: <strong><?php echo $child_age; ?> سال</strong></span>
                    <span class="gk-meta-badge">📅 تاریخ آزمون: <strong><?php echo date_i18n('j F Y'); ?></strong></span>
                    <span class="gk-meta-badge gk-meta-badge-success">📋 <?php echo $test_title; ?></span>
                </div>
            </div>

            <!-- بخش نمودار عنکبوتی رادار و خلاصه استعدادها -->
            <div class="gk-report-charts-grid">
                
                <!-- کادر نمودار راداری -->
                <div class="gk-chart-box">
                    <h3 class="gk-box-title">📊 نمودار راداری پراکندگی مهارت‌ها و استعدادها</h3>
                    <div class="gk-canvas-holder">
                        <canvas id="gk-radar-chart"></canvas>
                    </div>
                </div>

                <!-- کادر ۳ استعداد و ویژگی برتر -->
                <div class="gk-top-strengths-box">
                    <h3 class="gk-box-title">🌟 ۳ استعداد و ویژگی برجسته <?php echo $child_name; ?></h3>
                    <div class="gk-medals-list">
                        <?php 
                        $medals = ['🥇 رتبه اول (استعداد درخشان)', '🥈 رتبه دوم (توانمندی عالی)', '🥉 رتبه سوم (پتانسیل بالا)'];
                        $idx = 0;
                        foreach ($top_strengths as $key => $item): 
                            $badge_class = 'gk-rank-' . ($idx + 1);
                        ?>
                            <div class="gk-medal-item <?php echo $badge_class; ?>">
                                <div class="gk-medal-header">
                                    <span class="gk-medal-icon"><?php echo $item['icon']; ?></span>
                                    <div class="gk-medal-info">
                                        <h4><?php echo esc_html($item['name']); ?></h4>
                                        <span class="gk-medal-rank"><?php echo $medals[$idx]; ?></span>
                                    </div>
                                    <div class="gk-medal-score"><?php echo $item['percentage']; ?>٪</div>
                                </div>
                                <p class="gk-medal-desc"><?php echo esc_html($item['desc']); ?></p>
                            </div>
                        <?php 
                            $idx++;
                        endforeach; 
                        ?>
                    </div>
                </div>
            </div>

            <!-- جدول جزئیات تمام شاخص‌ها -->
            <div class="gk-breakdown-section">
                <h3 class="gk-box-title">📈 تحلیل تفکیکی تمام ابعاد و شاخص‌ها</h3>
                <div class="gk-bars-grid">
                    <?php foreach ($summary as $key => $item): ?>
                        <div class="gk-bar-card">
                            <div class="gk-bar-header">
                                <span><?php echo $item['icon'] . ' ' . esc_html($item['name']); ?></span>
                                <strong><?php echo $item['percentage']; ?>٪</strong>
                            </div>
                            <div class="gk-bar-track">
                                <div class="gk-bar-fill" style="width: <?php echo $item['percentage']; ?>%; background-color: <?php echo esc_attr($item['color']); ?>;"></div>
                            </div>
                            <p class="gk-bar-desc"><?php echo esc_html($item['desc']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- بسته بازی‌های تجویزی و متناسب در قربانی کیدز -->
            <div class="gk-recommended-games-section">
                <div class="gk-rec-header">
                    <span class="gk-rec-icon">🎮</span>
                    <div>
                        <h3>بازی‌های پیشنهادی سامانه برای تقویت و پرورش استعدادهای <?php echo $child_name; ?></h3>
                        <p>بر اساس تحلیل نتایج آزمون، انجام مستمر این بازی‌ها در سایت به رشد شناختی فرزندتان کمک شایانی می‌کند:</p>
                    </div>
                </div>

                <div class="gk-rec-games-grid">
                    <?php 
                    foreach ($recommended as $g_slug): 
                        $game_post = get_posts([
                            'post_type'  => 'gk_game',
                            'meta_key'   => '_gk_game_folder',
                            'meta_value' => $g_slug,
                            'numberposts' => 1
                        ]);
                        if (!empty($game_post)) {
                            $p = $game_post[0];
                            $game_url = get_permalink($p->ID);
                            $game_title = $p->post_title;
                        } else {
                            $game_url = home_url('/game/' . $g_slug . '/');
                            $game_title = $g_slug;
                        }

                        // اگر توکن نوآموز فعال است، به لینک بازی متصل کن تا هویت کودک منتقل شود
                        $st_token = $_GET['st_token'] ?? ($_COOKIE['gk_active_student_token'] ?? ($data['st_token'] ?? ''));
                        if (!empty($st_token)) {
                            $game_url = add_query_arg('st_token', $st_token, $game_url);
                        }
                    ?>
                        <div class="gk-rec-game-card">
                            <div class="gk-rec-game-icon">🕹️</div>
                            <h4><?php echo esc_html($game_title); ?></h4>
                            <a href="<?php echo esc_url($game_url); ?>" target="_blank" class="gk-btn-play-rec">
                                اجرای بازی 🚀
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- دکمه‌های عملیات کارنامه -->
            <div class="gk-report-actions">
                <button type="button" class="gk-btn-action gk-btn-print" onclick="window.print()">
                    🖨️ چاپ / ذخیره PDF کارنامه
                </button>
                <a href="<?php echo esc_url(wc_get_account_endpoint_url('assessments')); ?>" class="gk-btn-action gk-btn-dashboard">
                    📁 ورود به پرونده هوش من در سایت
                </a>
                <a href="<?php echo esc_url(remove_query_arg('test')); ?>" class="gk-btn-action gk-btn-retry">
                    🧠 انجام سایر تست‌ها
                </a>
            </div>

        </div>
        <?php
        return ob_get_clean();
    }
}