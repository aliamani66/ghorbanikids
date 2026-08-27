<?php
/**
 * Games Catalog & Live Filtering for GhorbaniKids (General Brain & Cognitive Games Only)
 */

if (!defined('ABSPATH')) {
    exit;
}

class GK_Catalog {

    public static function init() {
        $instance = new self();
        add_shortcode('ghorbanikids_games', [$instance, 'render_catalog']);
        add_shortcode('ghorbanikids_catalog', [$instance, 'render_catalog']);
        add_action('wp_enqueue_scripts', [$instance, 'enqueue_assets'], 10);
    }

    public function enqueue_assets() {
        $assets_url = plugins_url('assets', dirname(__FILE__));
        if (is_page(['games', 'games-catalog']) || is_tax(['game_age_group', 'game_category'])) {
            $css_ver = file_exists(dirname(dirname(__FILE__)) . '/assets/css/gk-catalog.css') ? filemtime(dirname(dirname(__FILE__)) . '/assets/css/gk-catalog.css') : time();
            wp_enqueue_style('gk-catalog', $assets_url . '/css/gk-catalog.css', [], $css_ver);
        }
    }

    public function render_catalog($atts = []) {
        $atts = shortcode_atts([
            'category' => '',
            'age'      => '',
        ], $atts);

        $assets_url = plugins_url('assets', dirname(__FILE__));
        $css_ver = file_exists(dirname(dirname(__FILE__)) . '/assets/css/gk-catalog.css') ? filemtime(dirname(dirname(__FILE__)) . '/assets/css/gk-catalog.css') : time();
        wp_enqueue_style('gk-catalog', $assets_url . '/css/gk-catalog.css', [], $css_ver);

        $selected_cat = isset($_GET['cat']) ? sanitize_text_field(urldecode($_GET['cat'])) : (!empty($atts['category']) ? $atts['category'] : '');
        $selected_age = isset($_GET['age']) ? sanitize_text_field(urldecode($_GET['age'])) : (!empty($atts['age']) ? $atts['age'] : '');

        $cat_terms = get_terms(['taxonomy' => 'game_category', 'hide_empty' => false]);
        $age_terms = get_terms(['taxonomy' => 'game_age_group', 'hide_empty' => false]);

        $cat_icons = [
            'دیداری و بصری'   => '👁️',
            'حافظه و تمرکز'   => '🧠',
            'هوش و ریاضی'     => '🔢',
            'سرگرمی و تفریحی' => '🎯',
            'شنیداری'          => '🎧',
            'سرعت عمل'        => '⚡'
        ];

        $age_icons = [
            'زیر ۳ سال'   => '👶',
            '۳ تا ۵ سال'  => '🧸',
            '۶ تا ۸ سال'  => '🎒',
            '۹ تا ۱۲ سال' => '🚀'
        ];

        // دریافت تمام بازی‌ها
        $all_posts = get_posts([
            'post_type'      => ['gk_game', 'game'],
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC'
        ]);

        // فیلتر کردن و جداسازی: بازی‌های درسی دبستان در کاتالوگ عمومی نمایش داده نمی‌شوند
        $games_list = [];
        foreach ($all_posts as $post_obj) {
            $pid = $post_obj->ID;
            $pslug = $post_obj->post_name;

            $has_grade = has_term('', 'school_grade', $pid);
            $has_subj = has_term('', 'school_subject', $pid);
            $has_less = has_term('', 'school_lesson', $pid);
            $is_curriculum = $has_grade || $has_subj || $has_less || 
                             (strpos($pslug, '1-') !== false && (strpos($pslug, 'riazi') !== false || strpos($pslug, 'farsi') !== false || strpos($pslug, 'oloom') !== false || strpos($pslug, 'hedyeh') !== false));

            if (!$is_curriculum) {
                $games_list[] = $post_obj;
            }
        }

        $total_games = count($games_list);

        ob_start();
        ?>
        <style data-no-optimize="1">
        /* Absolute Zero-Lag Free & VIP Badge Enforcements */
        .gk-card-thumb {
            position: relative !important;
            overflow: hidden !important;
        }
        .gk-free-tag,
        .gk-vip-tag {
            position: absolute !important;
            bottom: 10px !important;
            right: 10px !important;
            display: inline-flex !important;
            flex-direction: row !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 5px !important;
            white-space: nowrap !important;
            line-height: 1 !important;
            font-size: 11px !important;
            font-weight: 800 !important;
            padding: 4px 9px !important;
            border-radius: 8px !important;
            z-index: 5 !important;
            pointer-events: none !important;
            direction: rtl !important;
            box-sizing: border-box !important;
            width: auto !important;
            max-width: max-content !important;
            height: auto !important;
        }
        .gk-free-tag {
            background: #16a34a !important;
            color: #ffffff !important;
            border: 1px solid rgba(255, 255, 255, 0.4) !important;
            box-shadow: 0 3px 8px rgba(22, 163, 74, 0.35) !important;
        }
        .gk-vip-tag {
            background: linear-gradient(135deg, #9333ea, #7e22ce) !important;
            color: #ffffff !important;
            border: 1px solid rgba(255, 255, 255, 0.4) !important;
            box-shadow: 0 3px 8px rgba(147, 51, 234, 0.35) !important;
        }
        .gk-vip-tag .gk-crown-svg {
            width: 12px !important;
            height: 12px !important;
            display: inline-block !important;
            flex-shrink: 0 !important;
            fill: #fef08a !important;
        }
        .gk-tag-dot {
            width: 6px !important;
            height: 6px !important;
            border-radius: 50% !important;
            background: #ffffff !important;
            display: inline-block !important;
            box-shadow: 0 0 5px rgba(255, 255, 255, 0.9) !important;
            flex-shrink: 0 !important;
        }

        @media (max-width: 768px) {
            .gk-card-thumb {
                height: 110px !important;
            }
            .gk-thumb-svg-circle {
                width: 46px !important;
                height: 46px !important;
                border-radius: 14px !important;
            }
            .gk-thumb-svg-circle svg {
                width: 26px !important;
                height: 26px !important;
            }
            .gk-free-tag,
            .gk-vip-tag {
                bottom: 6px !important;
                right: 6px !important;
                font-size: 9.5px !important;
                font-weight: 800 !important;
                padding: 2px 7px !important;
                border-radius: 6px !important;
                gap: 3px !important;
                box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15) !important;
            }
            .gk-vip-tag .gk-crown-svg {
                width: 10px !important;
                height: 10px !important;
            }
            .gk-tag-dot {
                width: 5px !important;
                height: 5px !important;
            }
            .gk-ai-tag {
                top: 6px !important;
                left: 6px !important;
                font-size: 8.5px !important;
                font-weight: 800 !important;
                padding: 2px 6px !important;
                border-radius: 6px !important;
                box-shadow: 0 2px 6px rgba(99, 102, 241, 0.3) !important;
            }
        }
        </style>
        <div class="gk-archive-wrapper" id="gkGamesArchiveApp"
             data-initial-cat="<?php echo esc_attr($selected_cat); ?>"
             data-initial-age="<?php echo esc_attr($selected_age); ?>">

            <!-- 1. Hero Header -->
            <div class="gk-archive-hero">
                <span class="gk-hero-badge">✨ باشگاه بازی‌های فکری، تمرکزی و مهارتی</span>
                <h1 class="gk-archive-title">🎮 دنیای بازی‌های هوش و مهارت</h1>
                <p class="gk-archive-desc">مجموعه تخصصی بازی‌های آنلاین تقویت حافظه، دقت، هوش دیداری، پردازش ذهنی و مهارت‌های شناختی کودکان</p>
                
                <!-- 2. Search & Filter Bar -->
                <div class="gk-search-bar-row">
                    <div class="gk-search-input-box">
                        <span class="gk-search-icon">🔍</span>
                        <input type="text" id="gkGameSearchInput" class="gk-search-input" placeholder="جستجوی عنوان بازی یا مهارت..." autocomplete="off">
                        <button type="button" id="gkSearchClearBtn" class="gk-search-clear-btn" style="display:none;" title="پاک کردن جستجو">✕</button>
                    </div>
                    <button type="button" id="gkOpenMobileFilterBtn" class="gk-mobile-filter-trigger-btn">
                        <span>🎛️ فیلتر پیشرفته</span>
                        <span id="gkActiveFiltersBadge" class="gk-active-badge" style="display:none;">0</span>
                    </button>
                </div>
            </div>

            <!-- 3. Modal / Filter Drawer (Desktop & Mobile Unified) -->
            <div id="gkFilterModalOverlay" class="gk-filter-modal-overlay">
                <div class="gk-filter-drawer-container">
                    <div class="gk-drawer-header">
                        <div class="gk-drawer-title-box">
                            <h3>🎛️ فیلتر هوشمند بازی‌ها</h3>
                            <p>دسته مهارتی و رده سنی مورد نظر خود را انتخاب کنید</p>
                        </div>
                        <button type="button" id="gkCloseDrawerBtn" class="gk-drawer-close-btn" aria-label="بستن پنجره">✕</button>
                    </div>

                    <div class="gk-drawer-body">
                        <!-- Category Section -->
                        <div class="gk-drawer-section">
                            <div class="gk-section-label">
                                <span>🧠 دسته‌بندی مهارتی:</span>
                                <span class="gk-sec-badge">مهارت هدف</span>
                            </div>
                            <div class="gk-chips-wrap-grid">
                                <button type="button" class="gk-chip-btn gk-cat-chip <?php echo empty($selected_cat) ? 'active' : ''; ?>" data-cat="">
                                    <span>🌟 همه دسته‌ها</span>
                                </button>
                                <?php if (!empty($cat_terms) && !is_wp_error($cat_terms)): ?>
                                    <?php foreach ($cat_terms as $cat): 
                                        $icon = $cat_icons[$cat->name] ?? '🎮';
                                    ?>
                                        <button type="button" class="gk-chip-btn gk-cat-chip <?php echo ($selected_cat === $cat->slug || $selected_cat === $cat->name) ? 'active' : ''; ?>" data-cat="<?php echo esc_attr($cat->slug); ?>">
                                            <span><?php echo $icon; ?> <?php echo esc_html($cat->name); ?></span>
                                        </button>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Age Section -->
                        <div class="gk-drawer-section">
                            <div class="gk-section-label">
                                <span>🎂 رده سنی کودک:</span>
                                <span class="gk-sec-badge">رده سنی</span>
                            </div>
                            <div class="gk-chips-wrap-grid">
                                <button type="button" class="gk-chip-btn gk-age-chip <?php echo empty($selected_age) ? 'active' : ''; ?>" data-age="">
                                    <span>👶 همه سنین</span>
                                </button>
                                <?php if (!empty($age_terms) && !is_wp_error($age_terms)): ?>
                                    <?php foreach ($age_terms as $age): 
                                        $icon = $age_icons[$age->name] ?? '🎯';
                                    ?>
                                        <button type="button" class="gk-chip-btn gk-age-chip <?php echo ($selected_age === $age->slug || $selected_age === $age->name) ? 'active' : ''; ?>" data-age="<?php echo esc_attr($age->slug); ?>">
                                            <span><?php echo $icon; ?> <?php echo esc_html($age->name); ?></span>
                                        </button>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="gk-drawer-footer">
                        <button type="button" id="gkResetDrawerBtn" class="gk-btn-drawer-reset">🔄 پاکسازی فیلترها</button>
                        <button type="button" id="gkApplyDrawerBtn" class="gk-btn-drawer-apply">✨ مشاهده <span id="gkDrawerCount"><?php echo $total_games; ?></span> بازی</button>
                    </div>
                </div>
            </div>

            <!-- 4. Results Bar -->
            <div class="gk-results-bar">
                <div class="gk-results-count">
                    نمایش <strong id="gkGamesCount"><?php echo $total_games; ?></strong> بازی فکری و مهارتی
                </div>
                <button type="button" id="gkResetFiltersBtn" class="gk-reset-filters-btn" style="display:none;" onclick="window.gkResetAllFilters()">
                    🔄 حذف فیلترها و نمایش همه
                </button>
            </div>

            <!-- 5. Games Grid -->
            <div class="gk-games-grid" id="gkGamesGrid">
                <?php foreach ($games_list as $post_obj): 
                    $post_id = $post_obj->ID;
                    $title = $post_obj->post_title;
                    $permalink = get_permalink($post_id);

                    $age_objs = get_the_terms($post_id, 'game_age_group') ?: [];
                    $cat_objs = get_the_terms($post_id, 'game_category') ?: [];

                    $age_names = !is_wp_error($age_objs) ? wp_list_pluck($age_objs, 'name') : [];
                    $cat_names = !is_wp_error($cat_objs) ? wp_list_pluck($cat_objs, 'name') : [];
                    $age_slugs = !is_wp_error($age_objs) ? wp_list_pluck($age_objs, 'slug') : [];
                    $cat_slugs = !is_wp_error($cat_objs) ? wp_list_pluck($cat_objs, 'slug') : [];

                    $access_type = get_post_meta($post_id, '_gk_game_access', true) ?: 'premium';
                    $is_ai = (get_post_meta($post_id, '_gk_game_created_by', true) === 'ai_agent');

                    $preset = gk_get_game_icon($post_id);
                    $all_tags_text = implode(' ', array_merge($age_names, $cat_names, [$title]));
                ?>
                    <div class="gk-game-card-item"
                         data-title="<?php echo esc_attr(mb_strtolower($title)); ?>"
                         data-cats="<?php echo esc_attr(implode(',', array_merge($cat_names, $cat_slugs))); ?>"
                         data-ages="<?php echo esc_attr(implode(',', array_merge($age_names, $age_slugs, [$all_tags_text]))); ?>">
                        <div class="gk-game-card">
                            <a href="<?php echo esc_url($permalink); ?>" class="gk-card-thumb-link">
                                <div class="gk-card-thumb" style="background: <?php echo esc_attr($preset['bg']); ?>;">
                                    <?php if (!empty($is_ai)): ?>
                                        <span class="gk-ai-tag">🤖 طراحی AI</span>
                                    <?php endif; ?>

                                    <?php if (has_post_thumbnail($post_id)): ?>
                                        <?php echo get_the_post_thumbnail($post_id, 'medium'); ?>
                                    <?php else: ?>
                                        <div class="gk-default-thumb">
                                            <div class="gk-thumb-svg-circle">
                                                <?php echo $preset['svg']; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($access_type === 'free'): ?>
                                        <span class="gk-free-tag"><span class="gk-tag-dot"></span>رایگان</span>
                                    <?php else: ?>
                                        <span class="gk-vip-tag"><svg class="gk-crown-svg" width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M5 16L3 5l5.5 5L12 4l3.5 6L21 5l-2 11H5zm14 3c0 .6-.4 1-1 1H6c-.6 0-1-.4-1-1v-1h14v1z"/></svg><span>ویژه VIP</span></span>
                                    <?php endif; ?>
                                </div>
                            </a>

                            <div class="gk-card-body">
                                <div class="gk-card-badges-row">
                                    <?php if (!empty($cat_names)): ?>
                                        <span class="gk-badge-pill gk-badge-cat">
                                            🧠 <?php echo esc_html($cat_names[0]); ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if (!empty($age_names)): ?>
                                        <span class="gk-badge-pill" style="background: #f1f5f9; color: #475569;">
                                            🎂 <?php echo esc_html($age_names[0]); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <h3 class="gk-card-title">
                                    <a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($title); ?></a>
                                </h3>

                                <div class="gk-card-desc">
                                    <?php echo wp_trim_words($post_obj->post_content, 11, '...'); ?>
                                </div>

                                <div class="gk-card-action">
                                    <a href="<?php echo esc_url($permalink); ?>" class="gk-play-btn">
                                        <span>▶️ بازی کنید</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- No Results Message -->
            <div id="gkNoResults" class="gk-no-results-box" style="display:none; text-align: center; padding: 50px 20px; background: #fff; border-radius: 24px; border: 2px dashed #cbd5e1; margin-top: 30px;">
                <div style="font-size: 3.5rem; margin-bottom: 12px;">🔍</div>
                <h3 style="color: #0f172a; font-weight: 900; margin-bottom: 8px;">هیچ بازی‌ای با این مشخصات پیدا نشد!</h3>
                <p style="color: #64748b; font-weight: 600; margin-bottom: 20px;">می‌توانید کلمه جستجو را تغییر دهید یا فیلترهای دیگر را انتخاب نمایید.</p>
                <button type="button" class="gk-btn-drawer-apply" style="max-width: 250px; margin: 0 auto; display: inline-flex;" onclick="window.gkResetAllFilters()">🎮 مشاهده همه بازی‌ها</button>
            </div>
        </div>

        <script data-no-optimize="1">
        (function() {
            function initGameFilter() {
                var searchInput = document.getElementById('gkGameSearchInput');
                var clearBtn = document.getElementById('gkSearchClearBtn');
                var catChips = document.querySelectorAll('.gk-cat-chip');
                var ageChips = document.querySelectorAll('.gk-age-chip');
                var cards = document.querySelectorAll('.gk-game-card-item');
                var countElem = document.getElementById('gkGamesCount');
                var drawerCount = document.getElementById('gkDrawerCount');
                var activeBadge = document.getElementById('gkActiveFiltersBadge');
                var noResults = document.getElementById('gkNoResults');
                var resetBtn = document.getElementById('gkResetFiltersBtn');
                var resetDrawerBtn = document.getElementById('gkResetDrawerBtn');
                var applyDrawerBtn = document.getElementById('gkApplyDrawerBtn');
                var openDrawerBtn = document.getElementById('gkOpenMobileFilterBtn');
                var closeDrawerBtn = document.getElementById('gkCloseDrawerBtn');
                var modalOverlay = document.getElementById('gkFilterModalOverlay');
                var app = document.getElementById('gkGamesArchiveApp');

                function norm(str) {
                    if (!str) return '';
                    try { str = decodeURIComponent(str); } catch(e) {}
                    return str.replace(/[-_+]/g, ' ').replace(/\s+/g, ' ').trim().toLowerCase();
                }

                var currentCat = norm(app ? app.getAttribute('data-initial-cat') : '');
                var currentAge = norm(app ? app.getAttribute('data-initial-age') : '');

                function updateActiveBadge() {
                    var activeCount = (currentCat ? 1 : 0) + (currentAge ? 1 : 0);
                    if (activeBadge) {
                        activeBadge.textContent = activeCount;
                        activeBadge.style.display = activeCount > 0 ? 'inline-flex' : 'none';
                    }
                }

                function filterGames() {
                    var query = (searchInput ? searchInput.value : '').trim().toLowerCase();
                    if (clearBtn) clearBtn.style.display = query ? 'flex' : 'none';

                    var visibleCount = 0;
                    cards.forEach(function(card) {
                        var title = norm(card.getAttribute('data-title'));
                        var cats = norm(card.getAttribute('data-cats'));
                        var ages = norm(card.getAttribute('data-ages'));

                        var matchesSearch = !query || title.indexOf(query) !== -1 || cats.indexOf(query) !== -1;
                        var matchesCat = !currentCat || cats.indexOf(currentCat) !== -1;
                        var matchesAge = !currentAge || ages.indexOf(currentAge) !== -1;

                        if (matchesSearch && matchesCat && matchesAge) {
                            card.style.display = 'block';
                            visibleCount++;
                        } else {
                            card.style.display = 'none';
                        }
                    });

                    if (countElem) countElem.textContent = visibleCount;
                    if (drawerCount) drawerCount.textContent = visibleCount;
                    if (noResults) noResults.style.display = (visibleCount === 0) ? 'block' : 'none';
                    if (resetBtn) resetBtn.style.display = (currentCat || currentAge || query) ? 'inline-flex' : 'none';
                    updateActiveBadge();
                }

                function openDrawer() {
                    if (modalOverlay) {
                        modalOverlay.classList.add('is-open');
                        document.body.style.overflow = 'hidden';
                    }
                }

                function closeDrawer() {
                    if (modalOverlay) {
                        modalOverlay.classList.remove('is-open');
                        document.body.style.overflow = '';
                    }
                }

                if (openDrawerBtn) openDrawerBtn.addEventListener('click', openDrawer);
                if (closeDrawerBtn) closeDrawerBtn.addEventListener('click', closeDrawer);
                if (applyDrawerBtn) applyDrawerBtn.addEventListener('click', closeDrawer);
                if (modalOverlay) {
                    modalOverlay.addEventListener('click', function(e) {
                        if (e.target === modalOverlay) closeDrawer();
                    });
                }

                if (searchInput) searchInput.addEventListener('input', filterGames);
                if (clearBtn) {
                    clearBtn.addEventListener('click', function() {
                        searchInput.value = '';
                        filterGames();
                    });
                }

                catChips.forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        catChips.forEach(function(c) { c.classList.remove('active'); });
                        btn.classList.add('active');
                        currentCat = norm(btn.getAttribute('data-cat'));
                        filterGames();
                    });
                });

                ageChips.forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        ageChips.forEach(function(c) { c.classList.remove('active'); });
                        btn.classList.add('active');
                        currentAge = norm(btn.getAttribute('data-age'));
                        filterGames();
                    });
                });

                window.gkResetAllFilters = function() {
                    if (searchInput) searchInput.value = '';
                    currentCat = '';
                    currentAge = '';
                    catChips.forEach(function(c) { c.classList.remove('active'); });
                    if (catChips[0]) catChips[0].classList.add('active');
                    ageChips.forEach(function(c) { c.classList.remove('active'); });
                    if (ageChips[0]) ageChips[0].classList.add('active');
                    filterGames();
                };

                if (resetBtn) resetBtn.addEventListener('click', window.gkResetAllFilters);
                if (resetDrawerBtn) resetDrawerBtn.addEventListener('click', function() {
                    window.gkResetAllFilters();
                    closeDrawer();
                });

                filterGames();
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initGameFilter);
            } else {
                initGameFilter();
            }
        })();
        </script>
        <?php
        return ob_get_clean();
    }
}