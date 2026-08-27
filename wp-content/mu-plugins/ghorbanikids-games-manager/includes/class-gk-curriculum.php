<?php
/**
 * School Curriculum Module (Grades 1-5, Subjects, Lessons, Games & Quizzes Hub)
 * Designed for GhorbaniKids - Clean, Fast, In-Page Grid Update
 */

if (!defined('ABSPATH')) {
    exit;
}

class GK_Curriculum {

    public static function init() {
        $instance = new self();
        add_action('init', [$instance, 'register_taxonomies'], 1);
        add_action('init', [$instance, 'seed_default_terms'], 5);
        add_shortcode('ghorbanikids_curriculum', [$instance, 'render_curriculum_hub']);
        add_action('wp_enqueue_scripts', [$instance, 'enqueue_assets'], 10);
    }

    public function register_taxonomies() {
        register_taxonomy('school_grade', ['gk_game'], [
            'hierarchical'      => true,
            'labels'            => [
                'name'              => 'پایه‌های تحصیلی دبستان',
                'singular_name'     => 'پایه تحصیلی',
                'menu_name'         => '🎒 پایه‌های دبستان',
                'all_items'         => 'همه پایه‌ها',
                'edit_item'         => 'ویرایش پایه',
                'view_item'         => 'مشاهده پایه',
                'update_item'       => 'بروزرسانی پایه',
                'add_new_item'      => 'افزودن پایه جدید',
                'new_item_name'     => 'نام پایه تحصیلی',
                'search_items'      => 'جستجوی پایه',
            ],
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => ['slug' => 'school-grade'],
            'show_in_rest'      => true,
        ]);

        register_taxonomy('school_subject', ['gk_game'], [
            'hierarchical'      => true,
            'labels'            => [
                'name'              => 'کتاب‌های درسی',
                'singular_name'     => 'کتاب درسی',
                'menu_name'         => '📚 کتاب‌های درسی',
                'all_items'         => 'همه کتاب‌ها',
                'edit_item'         => 'ویرایش کتاب',
                'view_item'         => 'مشاهده کتاب',
                'update_item'       => 'بروزرسانی کتاب',
                'add_new_item'      => 'افزودن کتاب جدید',
                'new_item_name'     => 'نام کتاب درسی',
                'search_items'      => 'جستجوی کتاب',
            ],
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => ['slug' => 'school-subject'],
            'show_in_rest'      => true,
        ]);

        register_taxonomy('school_lesson', ['gk_game'], [
            'hierarchical'      => true,
            'labels'            => [
                'name'              => 'فصل‌ها و درس‌ها',
                'singular_name'     => 'فصل / درس',
                'menu_name'         => '📑 فصل‌ها و درس‌ها',
                'all_items'         => 'همه فصل‌ها و درس‌ها',
                'edit_item'         => 'ویرایش درس',
                'view_item'         => 'مشاهده درس',
                'update_item'       => 'بروزرسانی درس',
                'add_new_item'      => 'افزودن درس جدید',
                'new_item_name'     => 'نام فصل یا درس',
                'search_items'      => 'جستجوی درس',
            ],
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => ['slug' => 'school-lesson'],
            'show_in_rest'      => true,
        ]);
    }

    public function seed_default_terms() {
        if (get_option('gk_curriculum_terms_seeded')) {
            return;
        }

        $grades = [
            'grade-1' => 'کلاس اول دبستان',
            'grade-2' => 'کلاس دوم دبستان',
            'grade-3' => 'کلاس سوم دبستان',
            'grade-4' => 'کلاس چهارم دبستان',
            'grade-5' => 'کلاس پنجم دبستان',
        ];

        foreach ($grades as $slug => $name) {
            if (!term_exists($slug, 'school_grade')) {
                wp_insert_term($name, 'school_grade', ['slug' => $slug]);
            }
        }

        $subjects = [
            'math'    => '🧮 ریاضی',
            'farsi'   => '📖 فارسی و نگارش',
            'science' => '🔬 علوم تجربی',
            'hedyeh'  => '🌸 هدیه‌های آسمان',
            'quran'   => '📖 آموزش قرآن',
            'social'  => '🌍 مطالعات اجتماعی',
        ];

        foreach ($subjects as $slug => $name) {
            if (!term_exists($slug, 'school_subject')) {
                wp_insert_term($name, 'school_subject', ['slug' => $slug]);
            }
        }

        update_option('gk_curriculum_terms_seeded', '1');
    }

    public function enqueue_assets() {
        if (is_page(['curriculum', 'school-learning', 'elementary']) || is_tax(['school_grade', 'school_subject', 'school_lesson'])) {
            $assets_url = plugins_url('assets', dirname(__FILE__));
            $css_path   = dirname(dirname(__FILE__)) . '/assets/css/gk-curriculum.css';
            $css_ver    = file_exists($css_path) ? filemtime($css_path) : time();
            wp_enqueue_style('gk-curriculum', $assets_url . '/css/gk-curriculum.css', [], $css_ver);
        }
    }

    /**
     * Render the streamlined Curriculum Hub (Search modal controls filtering directly onto main grid)
     */
    public function render_curriculum_hub($atts) {
        $initial_grade   = isset($_GET['grade']) ? sanitize_text_field($_GET['grade']) : 'all';
        $initial_subject = isset($_GET['subject']) ? sanitize_text_field($_GET['subject']) : 'all';

        // Index all curriculum games
        $all_search_games = [];
        $raw_games = get_posts([
            'post_type'      => 'gk_game',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC'
        ]);

        foreach ($raw_games as $rg) {
            $g_grades   = wp_get_post_terms($rg->ID, 'school_grade', ['fields' => 'slugs']);
            $g_subjects = wp_get_post_terms($rg->ID, 'school_subject', ['fields' => 'all']);
            $g_lessons  = wp_get_post_terms($rg->ID, 'school_lesson', ['fields' => 'names']);

            $icon_info = GK_Game_Assets::get_game_icon($rg->ID);
            $is_free   = (get_post_meta($rg->ID, '_gk_game_access', true) === 'free');
            $is_ai     = (get_post_meta($rg->ID, '_gk_game_created_by', true) === 'ai_agent');

            $subj_slugs = !empty($g_subjects) && !is_wp_error($g_subjects) ? wp_list_pluck($g_subjects, 'slug') : [];
            $subj_names = !empty($g_subjects) && !is_wp_error($g_subjects) ? wp_list_pluck($g_subjects, 'name') : [];

            if (!empty($g_grades) || !empty($subj_slugs)) {
                $all_search_games[] = [
                    'id'           => $rg->ID,
                    'title'        => $rg->post_title,
                    'desc'         => wp_strip_all_tags($rg->post_content),
                    'url'          => get_permalink($rg->ID),
                    'grades'       => !empty($g_grades) && !is_wp_error($g_grades) ? $g_grades : ['grade-1'],
                    'subjects'     => $subj_slugs,
                    'subject_name' => !empty($subj_names) ? $subj_names[0] : 'بازی درسی',
                    'lessons'      => !empty($g_lessons) && !is_wp_error($g_lessons) ? $g_lessons : [],
                    'is_free'      => $is_free,
                    'is_ai'        => $is_ai,
                    'icon_bg'      => $icon_info['bg'],
                    'icon_svg'     => $icon_info['svg']
                ];
            }
        }

        ob_start();
        ?>
        <div class="gk-curriculum-wrap" id="gkCurriculumApp">
            <!-- Hero Header -->
            <div class="gk-curr-hero">
                <div class="gk-curr-badge">🎒 مدرسه هوشمند و بازی‌های درسی دبستان</div>
                <h1 class="gk-curr-title">دنیای بازی‌های آموزشی و یادگیری دروس دبستان</h1>
                <p class="gk-curr-subtitle">یادگیری مفهومی دروس ریاضی، فارسی، علوم و... همراه با بازی‌های تعاملی هدفمند و هوشمند</p>

                <!-- Prominent Search / Filter Trigger -->
                <div class="gk-curr-search-trigger-wrap" style="max-width: 680px; margin: 24px auto 0 auto;">
                    <button type="button" class="gk-btn-search-trigger" onclick="GkCurriculumSearch.open()">
                        <div class="gk-search-trigger-content">
                            <span class="gk-search-ico">🔍</span>
                            <span class="gk-search-text" id="gkSearchTriggerText">فیلتر و جستجوی پیشرفته بر اساس پایه، کتاب و مبحث درسی...</span>
                        </div>
                        <span class="gk-search-key-badge">🔍 منوی جستجو و فیلتر</span>
                    </button>
                </div>
            </div>

            <!-- Main Content Area: Games Catalog -->
            <div class="gk-curr-content-container" id="gkCurriculumMainGridSection">
                <div class="gk-curr-tabs-nav">
                    <div class="gk-curr-tab-btn is-active" style="cursor: default;">
                        <span>🎮</span>
                        <span id="gkCatalogTabTitle">تمام بازی‌های آموزشی دبستان</span>
                        <span class="gk-tab-count" id="gkCatalogCountBadge"><?php echo count($all_search_games); ?></span>
                    </div>
                    <button type="button" id="gkBtnResetNav" class="gk-curr-tab-btn" onclick="GkCurriculumSearch.resetAll()" style="display:none; margin-right: auto; background: #fee2e2; border-color: #fca5a5; color: #dc2626;">
                        <span>🔄</span>
                        <span>نمایش همه بازی‌ها</span>
                    </button>
                </div>

                <!-- Games Grid -->
                <div class="gk-curr-tab-panel is-active" id="tab-games">
                    <div class="gk-curr-games-grid" id="gkMainCurriculumGrid">
                        <?php foreach ($all_search_games as $game): ?>
                            <a href="<?php echo esc_url($game['url']); ?>" class="gk-curr-game-card">
                                <div class="gk-curr-card-thumb" style="background: <?php echo esc_attr($game['icon_bg']); ?>;">
                                    <?php if (!empty($game['is_ai'])): ?>
                                        <span class="gk-ai-tag">🤖 طراحی AI</span>
                                    <?php endif; ?>
                                    
                                    <div class="gk-default-thumb">
                                        <div class="gk-thumb-svg-circle">
                                            <?php echo $game['icon_svg']; ?>
                                        </div>
                                    </div>

                                    <?php if ($game['is_free']): ?>
                                        <span class="gk-free-tag">🟢 رایگان</span>
                                    <?php else: ?>
                                        <span class="gk-vip-tag">👑 ویژه VIP</span>
                                    <?php endif; ?>
                                </div>
                                <div class="gk-curr-card-body">
                                    <div class="gk-card-badges-row">
                                        <span class="gk-badge-pill gk-badge-cat">
                                            <?php echo esc_html($game['subject_name']); ?>
                                        </span>
                                        <span class="gk-badge-pill gk-badge-grade">
                                            🎒 پایه اول
                                        </span>
                                    </div>

                                    <h3 class="gk-curr-card-title"><?php echo esc_html($game['title']); ?></h3>
                                    <div class="gk-curr-card-desc"><?php echo wp_trim_words($game['desc'], 11, '...'); ?></div>
                                    
                                    <div class="gk-card-action">
                                        <div class="gk-play-btn">
                                            <span>▶️ بازی کنید</span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- پنجره مودال فرم فیلتر و جستجوی پیشرفته -->
        <div id="gkCurriculumSearchModal" class="gk-search-modal-overlay" style="display:none;" onclick="if(event.target===this)GkCurriculumSearch.close()">
            <div class="gk-search-modal-card">
                <div class="gk-search-modal-header">
                    <div class="gk-search-modal-title">
                        <span>🔍</span>
                        <h3>فیلتر و جستجوی بازی‌های درسی دبستان</h3>
                    </div>
                    <button type="button" class="gk-search-close-btn" onclick="GkCurriculumSearch.close()">✕</button>
                </div>

                <div class="gk-search-modal-body">
                    <!-- Search Input Bar -->
                    <div class="gk-search-bar-wrap">
                        <span class="gk-search-bar-icon">🔎</span>
                        <input type="text" id="gkSearchQueryInput" class="gk-search-input" placeholder="نام بازی، درس یا موضوع مورد نظر (مثلاً: جدول دوستی، چوب‌خط، تقارن...)" oninput="GkCurriculumSearch.onSearchChange()" onkeydown="if(event.key==='Enter')GkCurriculumSearch.applyAndClose()" />
                        <button type="button" id="gkSearchClearBtn" class="gk-search-clear-btn" onclick="GkCurriculumSearch.clearSearch()" style="display:none;">✕</button>
                    </div>

                    <!-- Filter 1: Grade Selection -->
                    <div class="gk-search-filter-section">
                        <span class="gk-filter-sec-label">🎓 انتخاب پایه تحصیلی:</span>
                        <div class="gk-search-chips" id="gkSearchGradeChips">
                            <button type="button" class="gk-chip is-active" data-grade="all" onclick="GkCurriculumSearch.setGrade('all')">همه پایه‌ها</button>
                            <button type="button" class="gk-chip" data-grade="grade-1" onclick="GkCurriculumSearch.setGrade('grade-1')">کلاس اول</button>
                            <button type="button" class="gk-chip" data-grade="grade-2" onclick="GkCurriculumSearch.setGrade('grade-2')">کلاس دوم</button>
                            <button type="button" class="gk-chip" data-grade="grade-3" onclick="GkCurriculumSearch.setGrade('grade-3')">کلاس سوم</button>
                            <button type="button" class="gk-chip" data-grade="grade-4" onclick="GkCurriculumSearch.setGrade('grade-4')">کلاس چهارم</button>
                            <button type="button" class="gk-chip" data-grade="grade-5" onclick="GkCurriculumSearch.setGrade('grade-5')">کلاس پنجم</button>
                        </div>
                    </div>

                    <!-- Filter 2: Subject Selection -->
                    <div class="gk-search-filter-section">
                        <span class="gk-filter-sec-label">📚 انتخاب کتاب درسی:</span>
                        <div class="gk-search-chips" id="gkSearchSubjectChips">
                            <button type="button" class="gk-chip is-active" data-subject="all" onclick="GkCurriculumSearch.setSubject('all')">🌟 همه کتاب‌ها</button>
                            <button type="button" class="gk-chip" data-subject="math" onclick="GkCurriculumSearch.setSubject('math')">🧮 ریاضی</button>
                            <button type="button" class="gk-chip" data-subject="farsi" onclick="GkCurriculumSearch.setSubject('farsi')">📖 فارسی و نگارش</button>
                            <button type="button" class="gk-chip" data-subject="science" onclick="GkCurriculumSearch.setSubject('science')">🔬 علوم تجربی</button>
                            <button type="button" class="gk-chip" data-subject="hedyeh" onclick="GkCurriculumSearch.setSubject('hedyeh')">🌸 هدیه‌های آسمان</button>
                            <button type="button" class="gk-chip" data-subject="quran" onclick="GkCurriculumSearch.setSubject('quran')">📖 قرآن</button>
                            <button type="button" class="gk-chip" data-subject="social" onclick="GkCurriculumSearch.setSubject('social')">🌍 مطالعات اجتماعی</button>
                        </div>
                    </div>

                    <!-- Filter 3: Quick Lesson / Topic Tags for active book -->
                    <div class="gk-search-filter-section" id="gkSearchTopicSection">
                        <span class="gk-filter-sec-label">🏷️ مباحث و درس‌های پرکاربرد:</span>
                        <div class="gk-topic-tags" id="gkSearchTopicTags">
                            <!-- Populated via JavaScript -->
                        </div>
                    </div>
                </div>

                <!-- Action Footer -->
                <div class="gk-search-modal-footer">
                    <button type="button" class="gk-btn-apply-filters" onclick="GkCurriculumSearch.applyAndClose()">
                        <span id="gkBtnApplyText">✨ اعمال فیلتر و مشاهده بازی‌ها در صفحه</span>
                    </button>
                    <button type="button" class="gk-btn-reset-filters" onclick="GkCurriculumSearch.resetAll()">
                        🔄 پاک کردن همه
                    </button>
                </div>
            </div>
        </div>

        <script>
        var GkCurriculumSearch = {
            games: <?php echo json_encode($all_search_games, JSON_UNESCAPED_UNICODE); ?>,
            activeGrade: '<?php echo esc_js($initial_grade); ?>' || 'all',
            activeSubject: '<?php echo esc_js($initial_subject); ?>' || 'all',
            activeTopic: '',

            gradeNames: {
                'all': 'همه پایه‌ها',
                'grade-1': 'کلاس اول',
                'grade-2': 'کلاس دوم',
                'grade-3': 'کلاس سوم',
                'grade-4': 'کلاس چهارم',
                'grade-5': 'کلاس پنجم'
            },

            subjectNames: {
                'all': 'همه کتاب‌ها',
                'math': 'ریاضی',
                'farsi': 'فارسی و نگارش',
                'science': 'علوم تجربی',
                'hedyeh': 'هدیه‌های آسمان',
                'quran': 'قرآن',
                'social': 'مطالعات اجتماعی'
            },

            topicsBySubject: {
                'all': [
                    'چوب‌خط و شمارش', 'جدول دوستی', 'حواس پنج‌گانه', 'الگوهای شطرنجی', 'صدای اول و آخر', 
                    'جانوران و پوشش', 'محور اعداد', 'جمله‌سازی', 'رشد گیاهان', 'ارزش مکانی', 
                    'نشانه‌های ۲', 'آهنربای جادویی', 'تقارن و آینه', 'روان‌خوانی', 'سنگ‌ها و خاک'
                ],
                'math': [
                    'چوب‌خط و شمارش', 'الگوهای رنگی و شطرنجی', 'پرش روی محور اعداد', 
                    'قلعه ده‌تایی‌ها و یکی‌ها (ارزش مکانی)', 'آینه جادویی تقارن', 'جدول شگفت‌انگیز', 'جمع و تفریق'
                ],
                'farsi': [
                    'جدول دوستی و ترکیب‌ها', 'قطار صداهای اول و آخر', 'پل کلمات و جمله‌سازی', 
                    'باغچه نشانه‌های چندشکلی', 'روان‌خوانی و درک تصویر', 'چکش تشدید', 'سفر نشانه‌ها', 'سوپ الفبا'
                ],
                'science': [
                    'کارآگاه حواس پنج‌گانه', 'پوشش دنیای جانوران', 'مراحل رشد گیاه', 
                    'آهنربای جادویی و اجسام', 'دنیای شگفت‌انگیز سنگ‌ها', 'سایه و نور', 'محیط زیست و بازیافت'
                ],
                'hedyeh': ['خداشناسی', 'مهربانی با دوستان', 'نعمت‌های زیبای خدا', 'پیامبران و امامان'],
                'quran': ['انس با قرآن', 'قصه‌های قرآنی', 'پیام‌های آسمانی', 'روخوانی سوره‌های کوچک'],
                'social': ['خانواده من', 'قوانین و نظم', 'محیط مدرسه', 'همسایگان']
            },

            open: function() {
                var modal = document.getElementById('gkCurriculumSearchModal');
                if (modal) {
                    modal.style.display = 'flex';
                    modal.classList.add('is-active');
                    this.renderTopics();
                    this.updateCountBadge();
                    setTimeout(function() {
                        var inp = document.getElementById('gkSearchQueryInput');
                        if (inp) inp.focus();
                    }, 100);
                }
            },

            close: function() {
                var modal = document.getElementById('gkCurriculumSearchModal');
                if (modal) {
                    modal.classList.remove('is-active');
                    modal.style.display = 'none';
                }
            },

            applyAndClose: function() {
                this.close();
                this.renderMainGrid();
                var sec = document.getElementById('gkCurriculumMainGridSection');
                if (sec) {
                    sec.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            },

            setGrade: function(gradeSlug) {
                this.activeGrade = gradeSlug;
                var chips = document.querySelectorAll('#gkSearchGradeChips .gk-chip');
                chips.forEach(function(c) {
                    c.classList.toggle('is-active', c.getAttribute('data-grade') === gradeSlug);
                });
                this.updateCountBadge();
            },

            setSubject: function(subjectSlug) {
                this.activeSubject = subjectSlug;
                this.activeTopic = '';
                var chips = document.querySelectorAll('#gkSearchSubjectChips .gk-chip');
                chips.forEach(function(c) {
                    c.classList.toggle('is-active', c.getAttribute('data-subject') === subjectSlug);
                });
                this.renderTopics();
                this.updateCountBadge();
            },

            setTopic: function(topicName) {
                if (this.activeTopic === topicName) {
                    this.activeTopic = '';
                } else {
                    this.activeTopic = topicName;
                }
                this.renderTopics();
                this.updateCountBadge();
            },

            renderTopics: function() {
                var cont = document.getElementById('gkSearchTopicTags');
                if (!cont) return;
                var list = this.topicsBySubject[this.activeSubject] || this.topicsBySubject['all'];
                var self = this;
                var html = '';
                list.forEach(function(t) {
                    var isAct = (self.activeTopic === t);
                    html += '<button type="button" class="gk-tag-btn ' + (isAct ? 'is-active' : '') + '" onclick="GkCurriculumSearch.setTopic(\'' + t.replace(/'/g, "\\'") + '\')">' + (isAct ? '✓ ' : '') + t + '</button>';
                });
                cont.innerHTML = html;
            },

            onSearchChange: function() {
                var inp = document.getElementById('gkSearchQueryInput');
                var clearBtn = document.getElementById('gkSearchClearBtn');
                if (inp && clearBtn) {
                    clearBtn.style.display = inp.value.trim() ? 'flex' : 'none';
                }
                this.updateCountBadge();
            },

            clearSearch: function() {
                var inp = document.getElementById('gkSearchQueryInput');
                var clearBtn = document.getElementById('gkSearchClearBtn');
                if (inp) {
                    inp.value = '';
                    inp.focus();
                }
                if (clearBtn) clearBtn.style.display = 'none';
                this.updateCountBadge();
            },

            getFilteredGames: function() {
                var qInput = document.getElementById('gkSearchQueryInput');
                var query = qInput ? qInput.value.trim().toLowerCase() : '';
                var self = this;

                return this.games.filter(function(g) {
                    if (self.activeGrade !== 'all') {
                        if (!g.grades || g.grades.indexOf(self.activeGrade) === -1) return false;
                    }
                    if (self.activeSubject !== 'all') {
                        if (!g.subjects || g.subjects.indexOf(self.activeSubject) === -1) return false;
                    }
                    if (self.activeTopic) {
                        var topicLower = self.activeTopic.toLowerCase();
                        var matchTopic = (g.title.toLowerCase().indexOf(topicLower) > -1) ||
                                         (g.desc.toLowerCase().indexOf(topicLower) > -1) ||
                                         (g.lessons && g.lessons.some(function(l){ return l.toLowerCase().indexOf(topicLower) > -1; }));
                        
                        var keyWords = topicLower.split(' ');
                        for (var i = 0; i < keyWords.length; i++) {
                            var kw = keyWords[i];
                            if (kw.length >= 3 && (g.title.indexOf(kw) > -1 || g.desc.indexOf(kw) > -1)) {
                                matchTopic = true;
                                break;
                            }
                        }
                        if (!matchTopic) return false;
                    }
                    if (query) {
                        var titleMatch = g.title.toLowerCase().indexOf(query) > -1;
                        var descMatch = g.desc.toLowerCase().indexOf(query) > -1;
                        var subjMatch = (g.subject_name || '').toLowerCase().indexOf(query) > -1;
                        if (!titleMatch && !descMatch && !subjMatch) return false;
                    }
                    return true;
                });
            },

            updateCountBadge: function() {
                var filtered = this.getFilteredGames();
                var btnApplyText = document.getElementById('gkBtnApplyText');
                if (btnApplyText) {
                    btnApplyText.textContent = '✨ اعمال فیلتر و مشاهده ' + Number(filtered.length).toLocaleString('fa-IR') + ' بازی در صفحه';
                }
            },

            renderMainGrid: function() {
                var filtered = this.getFilteredGames();
                var grid = document.getElementById('gkMainCurriculumGrid');
                var countBadge = document.getElementById('gkCatalogCountBadge');
                var tabTitle = document.getElementById('gkCatalogTabTitle');
                var btnResetNav = document.getElementById('gkBtnResetNav');
                var qInput = document.getElementById('gkSearchQueryInput');
                var query = qInput ? qInput.value.trim() : '';

                if (countBadge) {
                    countBadge.textContent = Number(filtered.length).toLocaleString('fa-IR');
                }

                var isFiltered = (this.activeGrade !== 'all') || (this.activeSubject !== 'all') || (this.activeTopic !== '') || (query !== '');

                if (isFiltered) {
                    var titleParts = [];
                    if (this.activeGrade !== 'all') titleParts.push(this.gradeNames[this.activeGrade]);
                    if (this.activeSubject !== 'all') titleParts.push(this.subjectNames[this.activeSubject]);
                    if (this.activeTopic) titleParts.push(this.activeTopic);
                    if (query) titleParts.push('«' + query + '»');

                    if (tabTitle) {
                        tabTitle.textContent = 'بازی‌های: ' + titleParts.join(' • ');
                    }
                    if (btnResetNav) {
                        btnResetNav.style.display = 'inline-flex';
                    }
                } else {
                    if (tabTitle) tabTitle.textContent = 'تمام بازی‌های آموزشی دبستان';
                    if (btnResetNav) btnResetNav.style.display = 'none';
                }

                if (!grid) return;

                if (filtered.length === 0) {
                    grid.innerHTML = '<div class="gk-curr-empty-state" style="grid-column: 1 / -1; width: 100%;">' +
                        '<div class="gk-empty-icon">🔍🍃</div>' +
                        '<h3>بازی با این مشخصات یافت نشد!</h3>' +
                        '<p>می‌توانید فیلترها را تغییر داده یا دکمه زیر را برای مشاهده همه بازی‌ها بزنید.</p>' +
                        '<button type="button" class="gk-btn-reset-filter" onclick="GkCurriculumSearch.resetAll()">' +
                            '🔄 نمایش همه بازی‌های درسی' +
                        '</button>' +
                    '</div>';
                    return;
                }

                var html = '';
                filtered.forEach(function(g) {
                    var aiTag = g.is_ai ? '<span class="gk-ai-tag">🤖 طراحی AI</span>' : '';
                    var accessTag = g.is_free ? '<span class="gk-free-tag">🟢 رایگان</span>' : '<span class="gk-vip-tag">👑 ویژه VIP</span>';

                    html += '<a href="' + g.url + '" class="gk-curr-game-card">' +
                        '<div class="gk-curr-card-thumb" style="background: ' + g.icon_bg + ';">' +
                            aiTag +
                            '<div class="gk-default-thumb">' +
                                '<div class="gk-thumb-svg-circle">' + g.icon_svg + '</div>' +
                            '</div>' +
                            accessTag +
                        '</div>' +
                        '<div class="gk-curr-card-body">' +
                            '<div class="gk-card-badges-row">' +
                                '<span class="gk-badge-pill gk-badge-cat">' + g.subject_name + '</span>' +
                                '<span class="gk-badge-pill gk-badge-grade">🎒 پایه اول</span>' +
                            '</div>' +
                            '<h3 class="gk-curr-card-title">' + g.title + '</h3>' +
                            '<div class="gk-curr-card-desc">' + (g.desc.length > 75 ? g.desc.substring(0, 75) + '...' : g.desc) + '</div>' +
                            '<div class="gk-card-action">' +
                                '<div class="gk-play-btn">' +
                                    '<span>▶️ بازی کنید</span>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                    '</a>';
                });

                grid.innerHTML = html;
            },

            resetAll: function() {
                this.activeGrade = 'all';
                this.activeSubject = 'all';
                this.activeTopic = '';
                var inp = document.getElementById('gkSearchQueryInput');
                if (inp) inp.value = '';
                var clearBtn = document.getElementById('gkSearchClearBtn');
                if (clearBtn) clearBtn.style.display = 'none';

                var gradeChips = document.querySelectorAll('#gkSearchGradeChips .gk-chip');
                gradeChips.forEach(function(c) {
                    c.classList.toggle('is-active', c.getAttribute('data-grade') === 'all');
                });

                var subjectChips = document.querySelectorAll('#gkSearchSubjectChips .gk-chip');
                subjectChips.forEach(function(c) {
                    c.classList.toggle('is-active', c.getAttribute('data-subject') === 'all');
                });

                this.renderTopics();
                this.updateCountBadge();
                this.renderMainGrid();
                this.close();
            }
        };

        // Initial check if query params passed
        (function() {
            if (GkCurriculumSearch.activeGrade !== 'all' || GkCurriculumSearch.activeSubject !== 'all') {
                GkCurriculumSearch.renderMainGrid();
            }
        })();
        </script>
        <?php
        return ob_get_clean();
    }
}