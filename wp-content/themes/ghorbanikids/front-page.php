<?php
/**
 * Front Page Template - GhorbaniKids (Ultra-Luxury, Clean & 100% Self-Contained)
 */

get_header();
?>
<main class="gk-site-main">
    <!-- ۱. بخش هیرو با تصویر پس‌زمینه شفاف و کارت شیشه‌ای شناور -->
    <section class="gk-home-hero">
        <div class="gk-container">
            <div class="gk-hero-glass-card">
                <span class="gk-hero-badge">✨ پلتفرم تخصصی بازی و پرورش استعداد کودکان</span>
                <h1 class="gk-hero-title">دنیای شاد بازی‌های هوش، تمرکز و حافظه کودکان</h1>
                <p class="gk-hero-desc">
                    مجموعه بازی‌های تعاملی، سنجش علمی هوش و مهارت‌های زندگی در محیطی شاد، امن، استاندارد و بدون هیچ‌گونه تبلیغات مزاحم.
                </p>
                <div class="gk-hero-buttons">
                    <a href="<?php echo esc_url(home_url('/games/')); ?>" class="gk-btn-hero-primary">
                        <span>🎮 ورود به سالن بازی‌ها</span>
                    </a>
                    <a href="<?php echo esc_url(home_url('/pricing/')); ?>" class="gk-btn-hero-secondary">
                        <span>👑 خرید اشتراک ویژه (VIP)</span>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- ۲. بخش بازی‌های منتخب و پرطرفدار -->
    <section class="gk-home-top-games-section">
        <div class="gk-container">
            <div class="gk-section-header">
                <span class="gk-section-badge">🎯 سرگرمی هدفمند</span>
                <h2 class="gk-section-title">🎮 بازی‌های منتخب و پرطرفدار کودکان</h2>
                <p class="gk-section-desc">نمونه‌هایی از جذاب‌ترین بازی‌های تقویت حافظه دیداری، تمرکز شناختی و هوش فضایی</p>
            </div>

            <div class="gk-games-grid">
                <?php
                $featured_games = get_posts([
                    'post_type'      => ['gk_game', 'game'],
                    'posts_per_page' => 4,
                    'post_status'    => 'publish',
                    'orderby'        => 'date',
                    'order'          => 'DESC'
                ]);

                if (!empty($featured_games)) :
                    $i = 0;
                    foreach ($featured_games as $game) :
                        $permalink = get_permalink($game->ID);
                        $slug = $game->post_name;
                        $access_type = get_post_meta($game->ID, '_gk_game_access', true) ?: 'premium';
                        $cat_objs = get_the_terms($game->ID, 'game_category') ?: [];
                        $cat_name = !empty($cat_objs) ? $cat_objs[0]->name : 'حافظه و تمرکز';
                        $age_objs = get_the_terms($game->ID, 'game_age_group') ?: [];
                        $age_names = wp_list_pluck($age_objs, 'name');

                        $preset = gk_get_game_icon($game->ID);
                        $i++;
                ?>
                    <div class="gk-game-card">
                        <a href="<?php echo esc_url($permalink); ?>" class="gk-card-thumb-link">
                            <div class="gk-card-thumb" style="background: <?php echo esc_attr($preset['bg']); ?>;">
                                <?php if (has_post_thumbnail($game->ID)): ?>
                                    <?php echo get_the_post_thumbnail($game->ID, 'medium'); ?>
                                <?php else: ?>
                                    <div class="gk-default-thumb">
                                        <div class="gk-thumb-svg-circle">
                                            <?php echo $preset['svg']; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </a>
                        <div class="gk-card-body">
                            <div class="gk-card-badges-row">
                                <?php if ($access_type === 'free'): ?>
                                    <span class="gk-badge-pill gk-badge-free"><span class="gk-pill-dot free"></span> رایگان</span>
                                <?php else: ?>
                                    <span class="gk-badge-pill gk-badge-vip"><span class="gk-pill-dot vip"></span> ویژه VIP</span>
                                <?php endif; ?>
                                <span class="gk-badge-pill gk-badge-cat">🧠 <?php echo esc_html($cat_name); ?></span>
                            </div>

                            <h3 class="gk-card-title">
                                <a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($game->post_title); ?></a>
                            </h3>

                            <div class="gk-card-specs-box">
                                <div class="gk-spec-item">
                                    <span class="gk-spec-label">🎂 رده سنی:</span>
                                    <span class="gk-spec-val"><?php echo esc_html(implode('، ', $age_names) ?: 'همه سنین'); ?></span>
                                </div>
                            </div>

                            <div class="gk-card-action">
                                <a href="<?php echo esc_url($permalink); ?>" class="gk-play-btn">
                                    <span>▶️ بازی کنید</span>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php 
                    endforeach;
                endif; 
                ?>
            </div>

            <div class="gk-section-footer-cta">
                <a href="<?php echo esc_url(home_url('/games/')); ?>" class="gk-btn-view-all-games">
                    <span>✨ مشاهده تمام ۱۶ بازی و فیلترهای سنی 🚀</span>
                </a>
            </div>
        </div>
    </section>

    <!-- ۳. بخش سامانه مدارس و مسابقات مربیان (کامل با جزئیات و پیش‌نمایش جدول و رتبه‌بندی) -->
    <section class="gk-home-schools-section">
        <div class="gk-container">
            <div class="gk-schools-showcase-card">
                <div class="gk-schools-showcase-content">
                    <span class="gk-schools-badge">🏫 سامانه هوشمند مهدکودک‌ها و مدارس</span>
                    <h2 class="gk-schools-title">مدیریت کلاس‌ها، مسابقات و لیگ‌های آنلاین نوآموزان</h2>
                    <p class="gk-schools-desc">
                        مدیران و معلمان گرامی می‌توانند با ایجاد کلاس‌ها، ثبت نوآموزان و برگزاری مسابقات زمان‌دار هفتگی، یادگیری کودکان را به تجربه‌ای رقابتی، شاداب و ماندگار تبدیل نمایند.
                    </p>
                    
                    <div class="gk-schools-features-list">
                        <div class="gk-school-feat-item">
                            <span class="gk-feat-bullet-icon">🏆</span>
                            <span>ایجاد مسابقات کلاسی و لیگ با تعیین مهلت زمانی انقضا</span>
                        </div>
                        <div class="gk-school-feat-item">
                            <span class="gk-feat-bullet-icon">📱</span>
                            <span>ارسال لینک ورود اختصاصی به پیام‌رسان بله مادران با یک کلیک</span>
                        </div>
                        <div class="gk-school-feat-item">
                            <span class="gk-feat-bullet-icon">🥇</span>
                            <span>سکوی قهرمانان و جدول امتیازات زنده کلاسی</span>
                        </div>
                        <div class="gk-school-feat-item">
                            <span class="gk-feat-bullet-icon">🔓</span>
                            <span>بازگشایی خودکار ۱۰۰٪ بازی‌ها و تست‌ها برای نوآموزان بدون نیاز به اکانت</span>
                        </div>
                    </div>

                    <div class="gk-schools-actions">
                        <a href="<?php echo esc_url(home_url('/school-panel/')); ?>" class="gk-btn-school-panel">
                            <span>🏫 ورود به پنل مدیریت مدارس</span>
                        </a>
                        <a href="<?php echo esc_url(home_url('/pricing/')); ?>" class="gk-btn-school-plans">
                            <span>📋 مشاهده پلن‌های مهدکودک</span>
                        </a>
                    </div>
                </div>

                <div class="gk-schools-showcase-visual">
                    <div class="gk-visual-dashboard-preview">
                        <div class="gk-dash-mini-header">
                            <div class="gk-dash-dots">
                                <span class="gk-dash-dot red"></span>
                                <span class="gk-dash-dot yellow"></span>
                                <span class="gk-dash-dot green"></span>
                            </div>
                            <span class="gk-dash-title">🏆 لیگ مسابقات کلاسی - نوآموزان پیشتاز</span>
                        </div>
                        <div class="gk-dash-mini-body">
                            <div class="gk-dash-stat-row">
                                <div class="gk-dash-mini-stat">
                                    <small>👥 نوآموزان کلاس</small>
                                    <strong>۲۴ نفر</strong>
                                </div>
                                <div class="gk-dash-mini-stat">
                                    <small>⚡ لیگ‌های فعال</small>
                                    <strong>۳ مسابقه</strong>
                                </div>
                                <div class="gk-dash-mini-stat">
                                    <small>🎮 بازی‌های مسابقه</small>
                                    <strong>۵ بازی</strong>
                                </div>
                            </div>
                            
                            <div class="gk-dash-podium-box">
                                <div class="gk-mini-medal gold">
                                    <span class="gk-medal-icon">🥇</span>
                                    <span class="gk-student-name">علی محمدی</span>
                                    <strong class="gk-student-score">۲,۴۵۰ امتیاز</strong>
                                </div>
                                <div class="gk-mini-medal silver">
                                    <span class="gk-medal-icon">🥈</span>
                                    <span class="gk-student-name">سارا رضایی</span>
                                    <strong class="gk-student-score">۲,۱۰۰ امتیاز</strong>
                                </div>
                                <div class="gk-mini-medal bronze">
                                    <span class="gk-medal-icon">🥉</span>
                                    <span class="gk-student-name">پرهام کاظمی</span>
                                    <strong class="gk-student-score">۱,۹۵۰ امتیاز</strong>
                                </div>
                            </div>

                            <div class="gk-dash-live-badge">
                                <span class="gk-live-pulse"></span>
                                <span>جدول زنده ثبت رکوردها در سامانه</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ۴. بخش اسلایدر آخرین مقالات با قاب شکیل لوکس (Framed Showcase) -->
    <section class="gk-home-blog-section">
        <div class="gk-container">
            <div class="gk-blog-outer-frame">
                <div class="gk-section-header">
                    <span class="gk-section-badge">📚 مجله و دانشنامه تربیتی</span>
                    <h2 class="gk-section-title">📰 آخرین مقالات، آموزش‌ها و اخبار</h2>
                    <p class="gk-section-desc">جدیدترین نکات روانشناسی کودک، روش‌های تقویت هوش، بازی‌درمانی و یادگیری شاداب</p>
                </div>

                <!-- بدنه اسلایدر کروسل مقالات -->
                <div class="gk-blog-slider-container">
                    <div class="gk-blog-slider-track" id="gkBlogTrack">
                        <?php
                        $latest_posts = get_posts([
                            'post_type'      => 'post',
                            'posts_per_page' => 6,
                            'post_status'    => 'publish'
                        ]);

                        if (!empty($latest_posts)) :
                            foreach ($latest_posts as $p_item) :
                                $p_url = get_permalink($p_item->ID);
                                $p_img = get_the_post_thumbnail_url($p_item->ID, 'medium_large');
                                $p_date = get_the_date('j F Y', $p_item->ID);
                                $p_excerpt = wp_trim_words($p_item->post_content, 18, '...');
                        ?>
                            <article class="gk-blog-slide-card">
                                <a href="<?php echo esc_url($p_url); ?>" class="gk-blog-thumb-link">
                                <div class="gk-blog-thumb-wrapper">
                                    <?php if ($p_img) : ?>
                                        <img src="<?php echo esc_url($p_img); ?>" alt="<?php echo esc_attr($p_item->post_title); ?>" class="gk-blog-cover-img" loading="lazy" />
                                    <?php else : ?>
                                        <div class="gk-blog-placeholder-box">📚</div>
                                    <?php endif; ?>
                                    <span class="gk-blog-date-badge">📅 <?php echo esc_html($p_date); ?></span>
                                </div>
                            </a>
                            <div class="gk-blog-card-body">
                                <h3 class="gk-blog-card-title">
                                    <a href="<?php echo esc_url($p_url); ?>"><?php echo esc_html($p_item->post_title); ?></a>
                                </h3>
                                <p class="gk-blog-card-excerpt"><?php echo esc_html($p_excerpt); ?></p>
                                <div class="gk-blog-card-footer">
                                    <a href="<?php echo esc_url($p_url); ?>" class="gk-blog-read-btn">
                                        <span>مطالعه کامل مقاله ←</span>
                                    </a>
                                </div>
                            </div>
                        </article>
                        <?php 
                            endforeach;
                        endif; 
                        ?>
                    </div>
                </div>

                <!-- کنترل یکپارچه ناوبری اسلایدر در پایین (فلش راست + دات‌ها + فلش چپ) -->
                <div class="gk-slider-bottom-controls">
                    <button type="button" id="gkBlogPrevBtn" class="gk-slider-arrow-btn" aria-label="قبلی">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 18l6-6-6-6"/></svg>
                    </button>
                    <div class="gk-slider-dots-wrapper" id="gkBlogDots"></div>
                    <button type="button" id="gkBlogNextBtn" class="gk-slider-arrow-btn" aria-label="بعدی">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 18l-6-6 6-6"/></svg>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- ۵. بخش ۳ ستون ارزش‌ها و ویژگی‌های کلیدی -->
    <section class="gk-home-features-pillars">
        <div class="gk-container">
            <div class="gk-section-header">
                <span class="gk-section-badge">💎 چرا قربانی کیدز؟</span>
                <h2 class="gk-section-title">ویژگی‌های برجسته و استانداردهای آموزشی</h2>
                <p class="gk-section-desc">طراحی علمی و تخصصی برای تجربه یادگیری لذت‌بخش و شاداب کودکان</p>
            </div>
            <div class="gk-pillars-grid">
                <div class="gk-pillar-card pillar-brain">
                    <div class="gk-pillar-3d-badge">
                        <span class="gk-pillar-badge-icon">🧠</span>
                    </div>
                    <h3 class="gk-pillar-title">تقویت هوش و حافظه</h3>
                    <p class="gk-pillar-desc">بازی‌های هدفمند بر پایه علوم شناختی جهت ارتقای توجه انتخابی، حافظه فعال و مهارت‌های حل مسئله کودک.</p>
                </div>
                <div class="gk-pillar-card pillar-game">
                    <div class="gk-pillar-3d-badge">
                        <span class="gk-pillar-badge-icon">🎯</span>
                    </div>
                    <h3 class="gk-pillar-title">آموزش در قالب بازی</h3>
                    <p class="gk-pillar-desc">رویکرد گیمیفیکیشن استاندارد تا نوآموز بدون احساس خستگی و با شوق درونی مهارت‌های تازه بیاموزد.</p>
                </div>
                <div class="gk-pillar-card pillar-shield">
                    <div class="gk-pillar-3d-badge">
                        <span class="gk-pillar-badge-icon">🛡️</span>
                    </div>
                    <h3 class="gk-pillar-title">محیط ۱۰۰٪ امن و کودکانه</h3>
                    <p class="gk-pillar-desc">فضایی کاملاً پاک و بدون هیچ‌گونه تبلیغات مزاحم تا والدین با خیالی کاملاً آسوده گوشی یا تبلت را در اختیار کودک قرار دهند.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ۶. بنر CTA اقدام پایانی -->
    <section class="gk-home-cta-banner">
        <div class="gk-container">
            <div class="gk-cta-inner-card">
                <h2>همین امروز وارد دنیای بازی‌های قربانی کیدز شوید! 🎉</h2>
                <p>برای فرزندتان لحظاتی پر از هیجان، خنده و یادگیری موثر رقم بزنید.</p>
                <div class="gk-cta-btns">
                    <a href="<?php echo esc_url(home_url('/pricing/')); ?>" class="gk-btn-hero-secondary">
                        <span>👑 خرید اشتراک ویژه</span>
                    </a>
                    <a href="<?php echo esc_url(home_url('/games/')); ?>" class="gk-btn-hero-primary">
                        <span>🎮 ورود به سالن بازی‌ها</span>
                    </a>
                </div>
            </div>
        </div>
    </section>
</main>

<script data-no-optimize="1">
(function() {
    function initBlogSlider() {
        var track = document.getElementById('gkBlogTrack');
        var prevBtn = document.getElementById('gkBlogPrevBtn');
        var nextBtn = document.getElementById('gkBlogNextBtn');
        var dotsContainer = document.getElementById('gkBlogDots');
        if (!track) return;

        var slides = track.querySelectorAll('.gk-blog-slide-card');
        if (!slides.length) return;

        function getCardWidth() {
            var firstCard = slides[0];
            if (!firstCard) return 340;
            var gap = 24;
            try {
                var computedGap = parseFloat(window.getComputedStyle(track).gap);
                if (!isNaN(computedGap)) gap = computedGap;
            } catch(e){}
            return firstCard.offsetWidth + gap;
        }

        function getVisibleCount() {
            var trackW = track.clientWidth;
            var cardW = getCardWidth();
            return Math.max(1, Math.round(trackW / cardW));
        }

        function getDotsCount() {
            var visible = getVisibleCount();
            var total = slides.length;
            var count = total - visible + 1;
            return count > 0 ? count : 1;
        }

        function renderDots() {
            if (!dotsContainer) return;
            dotsContainer.innerHTML = '';
            var totalDots = getDotsCount();
            for (var idx = 0; idx < totalDots; idx++) {
                (function(i) {
                    var dot = document.createElement('button');
                    dot.type = 'button';
                    dot.className = 'gk-slider-dot' + (i === 0 ? ' active' : '');
                    dot.setAttribute('aria-label', 'اسلاید ' + (i + 1));
                    dot.addEventListener('click', function() {
                        scrollToSlide(i);
                    });
                    dotsContainer.appendChild(dot);
                })(idx);
            }
            updateActiveDot();
        }

        function updateActiveDot() {
            if (!dotsContainer) return;
            var dots = dotsContainer.querySelectorAll('.gk-slider-dot');
            if (!dots.length) return;
            var scrollPos = Math.abs(track.scrollLeft);
            var cardW = getCardWidth();
            var activeIdx = Math.round(scrollPos / cardW);
            activeIdx = Math.min(activeIdx, dots.length - 1);
            dots.forEach(function(d, i) {
                d.classList.toggle('active', i === activeIdx);
            });
        }

        function scrollToSlide(idx) {
            var cardW = getCardWidth();
            var totalDots = getDotsCount();
            idx = Math.min(idx, totalDots - 1);
            track.scrollTo({
                left: -(idx * cardW),
                behavior: 'smooth'
            });
        }

        if (prevBtn) {
            prevBtn.onclick = function() {
                var cardW = getCardWidth();
                track.scrollBy({ left: cardW, behavior: 'smooth' });
            };
        }

        if (nextBtn) {
            nextBtn.onclick = function() {
                var cardW = getCardWidth();
                track.scrollBy({ left: -cardW, behavior: 'smooth' });
            };
        }

        track.addEventListener('scroll', updateActiveDot, { passive: true });
        window.addEventListener('resize', function() {
            renderDots();
        }, { passive: true });

        renderDots();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initBlogSlider);
    } else {
        initBlogSlider();
    }
})();
</script>
<?php
get_footer();