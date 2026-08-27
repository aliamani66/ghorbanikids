<?php
/**
 * CPT, Taxonomies & Meta Boxes for GhorbaniKids Games
 */

if (!defined('ABSPATH')) {
    exit;
}

class GK_CPT {

    public static function init() {
        $instance = new self();
        add_action('init', [$instance, 'register_cpt_and_taxonomies'], 0);
        add_action('add_meta_boxes', [$instance, 'add_meta_boxes']);
        add_action('save_post_gk_game', [$instance, 'save_meta_box_data']);
        add_filter('template_include', [$instance, 'override_archive_template']);
    }

    public function register_cpt_and_taxonomies() {
        $labels = [
            'name'                  => 'بازی‌ها',
            'singular_name'         => 'بازی',
            'menu_name'             => '🎮 بازی‌ها',
            'name_admin_bar'        => 'بازی',
            'add_new'               => 'افزودن بازی جدید',
            'add_new_item'          => 'افزودن بازی جدید',
            'new_item'              => 'بازی تازه',
            'edit_item'             => 'ویرایش بازی',
            'view_item'             => 'مشاهده بازی',
            'all_items'             => 'همه بازی‌ها',
            'search_items'          => 'جستجوی بازی‌ها',
            'not_found'             => 'هیچ بازی‌ای پیدا نشد.',
            'not_found_in_trash'    => 'هیچ بازی‌ای در سطل زباله پیدا نشد.'
        ];

        $args = [
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => ['slug' => 'game', 'with_front' => false],
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => 6,
            'menu_icon'          => 'dashicons-games',
            'supports'           => ['title', 'editor', 'thumbnail', 'excerpt'],
            'show_in_rest'       => true,
        ];

        register_post_type('gk_game', $args);

        register_taxonomy('game_age_group', ['gk_game'], [
            'hierarchical'      => true,
            'labels'            => ['name' => 'رده‌های سنی', 'singular_name' => 'رده سنی', 'menu_name' => '🎂 رده‌های سنی'],
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => ['slug' => 'game-age'],
            'show_in_rest'      => true,
        ]);

        register_taxonomy('game_category', ['gk_game'], [
            'hierarchical'      => true,
            'labels'            => ['name' => 'نوع و مهارت بازی', 'singular_name' => 'نوع بازی', 'menu_name' => '🧠 نوع و مهارت بازی'],
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => ['slug' => 'game-category'],
            'show_in_rest'      => true,
        ]);
    }

    public function add_meta_boxes() {
        add_meta_box(
            'gk_game_options',
            '🎮 تنظیمات، فایل و دسترسی بازی (GhorbaniKids)',
            [$this, 'render_meta_box'],
            'gk_game',
            'normal',
            'high'
        );
    }

    public function render_meta_box($post) {
        wp_nonce_field('gk_game_meta_nonce', 'gk_game_meta_nonce_field');

        $current_folder = get_post_meta($post->ID, '_gk_game_folder', true);
        $aspect_ratio   = get_post_meta($post->ID, '_gk_game_aspect', true) ?: '16/9';
        $access_type    = get_post_meta($post->ID, '_gk_game_access', true) ?: 'premium';

        // Load Icon & Gradient data
        $icon_data   = GK_Game_Assets::get_game_icon($post->ID);
        $saved_svg   = get_post_meta($post->ID, '_gk_game_icon_svg', true) ?: $icon_data['svg'];
        $saved_bg    = get_post_meta($post->ID, '_gk_game_bg_gradient', true) ?: $icon_data['bg'];
        $saved_preset = get_post_meta($post->ID, '_gk_game_icon_preset', true) ?: ($icon_data['preset'] ?? '');

        $all_presets = GK_Game_Assets::get_presets();

        $games_base_dir = WP_CONTENT_DIR . '/games';
        $available_folders = [];

        if (is_dir($games_base_dir)) {
            $items = scandir($games_base_dir);
            foreach ($items as $item) {
                if ($item !== '.' && $item !== '..' && is_dir($games_base_dir . '/' . $item)) {
                    $has_index = file_exists($games_base_dir . '/' . $item . '/index.html') || file_exists($games_base_dir . '/' . $item . '/index.php');
                    $available_folders[] = [
                        'name' => $item,
                        'has_index' => $has_index
                    ];
                }
            }
        }
        ?>
        <div style="padding: 12px; font-family: Tahoma, Arial, sans-serif; line-height: 1.8;">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="gk_game_access"><strong>🔐 سطح دسترسی به بازی:</strong></label></th>
                    <td>
                        <select name="gk_game_access" id="gk_game_access" style="min-width: 280px; font-weight: bold;">
                            <option value="premium" <?php selected($access_type, 'premium'); ?>>🔒 نیازمند اشتراک ویژه VIP (قفل‌دار)</option>
                            <option value="free" <?php selected($access_type, 'free'); ?>>🟢 کاملاً رایگان برای همه (بدون قفل)</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="gk_game_folder"><strong>انتخاب پوشه بازی:</strong></label></th>
                    <td>
                        <select name="gk_game_folder_select" id="gk_game_folder_select" style="min-width: 280px;" onchange="document.getElementById('gk_game_folder').value = this.value;">
                            <option value="">-- انتخاب از پوشه‌های اسکن‌شده در هاست --</option>
                            <?php foreach ($available_folders as $folder): ?>
                                <option value="<?php echo esc_attr($folder['name']); ?>" <?php selected($current_folder, $folder['name']); ?>>
                                    <?php echo esc_html($folder['name']); ?> <?php echo $folder['has_index'] ? ' (✅ دارای index.html)' : ' (⚠️ بدون index.html)'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <br><br>
                        <input type="text" name="gk_game_folder" id="gk_game_folder" value="<?php echo esc_attr($current_folder); ?>" placeholder="مثلاً: bubble-detective" style="width: 280px;" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="gk_game_aspect"><strong>نسبت تصویر کادر بازی:</strong></label></th>
                    <td>
                        <select name="gk_game_aspect" id="gk_game_aspect" style="min-width: 280px;">
                            <option value="16/9" <?php selected($aspect_ratio, '16/9'); ?>>۱۶:۹ (عریض استاندارد - پیشنهادی)</option>
                            <option value="4/3" <?php selected($aspect_ratio, '4/3'); ?>>۴:۳ (مستطیلی کلاسیک)</option>
                            <option value="1/1" <?php selected($aspect_ratio, '1/1'); ?>>۱:۱ (مربعی)</option>
                            <option value="fullscreen" <?php selected($aspect_ratio, 'fullscreen'); ?>>ارتفاع بلند (۷۵۰ پیکسل)</option>
                        </select>
                    </td>
                </tr>
            </table>

            <hr style="margin: 24px 0; border: 0; border-top: 2px dashed #e2e8f0;">

            <!-- بخش ذخیره و مدیریت آیکون و گرادینت کارت بازی -->
            <div style="background: #f8fafc; border: 1.5px solid #cbd5e1; border-radius: 16px; padding: 20px;">
                <h3 style="margin: 0 0 14px 0; font-size: 16px; font-weight: 900; color: #1e293b; display:flex; align-items:center; gap:8px;">
                    <span>🎨</span>
                    <span>آیکون و رنگ گرادینت تایل بازی (SVG & Background):</span>
                </h3>
                <p style="font-size: 13px; color: #64748b; margin-bottom: 16px;">
                    این آیکون و رنگ در تمام تایل‌های سایت (صفحه بازی‌ها، صفحه اصلی، سالن مسابقه و پنل مربی) ذخیره و لود می‌شود. می‌توانید با ۱ کلیک یکی از آیکون‌های آماده زیر را انتخاب کنید یا کد SVG دلخواه بگذارید:
                </p>

                <!-- پیش‌نمایش زنده -->
                <div style="display:flex; align-items:center; gap:20px; background:#ffffff; border:1px solid #e2e8f0; border-radius:14px; padding:16px; margin-bottom:20px;">
                    <div id="gk_live_preview_box" style="width:72px; height:72px; border-radius:16px; background: <?php echo esc_attr($saved_bg); ?>; display:flex; align-items:center; justify-content:center; box-shadow:0 6px 16px rgba(0,0,0,0.12);">
                        <div id="gk_live_preview_svg" style="width:48px; height:48px; display:flex; align-items:center; justify-content:center;">
                            <?php echo $saved_svg; ?>
                        </div>
                    </div>
                    <div>
                        <strong style="font-size:14px; color:#0f172a; display:block;">پیش‌نمایش زنده تایل این بازی</strong>
                        <span id="gk_current_preset_label" style="font-size:12px; color:#64748b;">قالب فعال: <?php echo esc_html($saved_preset ?: 'سفارشی'); ?></span>
                    </div>
                </div>

                <!-- انتخابگر سریع آیکون‌های آماده (Presets) -->
                <label style="font-size:13px; font-weight:bold; color:#334155; display:block; margin-bottom:8px;">انتخاب سریع از آیکون‌های وکتور آماده:</label>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 10px; margin-bottom: 20px;">
                    <?php foreach ($all_presets as $key => $preset): 
                        $is_sel = ($saved_preset === $key);
                    ?>
                        <button type="button" class="gk-preset-select-btn" data-key="<?php echo esc_attr($key); ?>" data-bg="<?php echo esc_attr($preset['bg']); ?>" data-title="<?php echo esc_attr($preset['title']); ?>" style="display:flex; flex-direction:column; align-items:center; gap:6px; padding:10px 8px; border:2px solid <?php echo $is_sel ? '#4f46e5' : '#e2e8f0'; ?>; border-radius:12px; background:#ffffff; cursor:pointer; text-align:center;">
                            <div style="width:40px; height:40px; border-radius:10px; background:<?php echo esc_attr($preset['bg']); ?>; display:flex; align-items:center; justify-content:center;">
                                <div style="transform:scale(0.7); display:flex; align-items:center; justify-content:center;">
                                    <?php echo $preset['svg']; ?>
                                </div>
                            </div>
                            <span style="font-size:11px; font-weight:bold; color:#334155; line-height:1.3;"><?php echo esc_html($preset['title']); ?></span>
                            <!-- Hidden raw svg for js copy -->
                            <textarea style="display:none;" class="gk-preset-raw-svg"><?php echo esc_textarea($preset['svg']); ?></textarea>
                        </button>
                    <?php endforeach; ?>
                </div>

                <!-- فیلدهای متای ذخیره‌سازی -->
                <table class="form-table" style="margin-top:0;">
                    <tr>
                        <th scope="row" style="width:160px;"><label for="gk_game_bg_gradient"><strong>رنگ گرادینت CSS:</strong></label></th>
                        <td>
                            <input type="text" name="gk_game_bg_gradient" id="gk_game_bg_gradient" value="<?php echo esc_attr($saved_bg); ?>" style="width:100%; max-width:500px; font-family:monospace; direction:ltr;" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="gk_game_icon_svg"><strong>کد SVG آیکون:</strong></label></th>
                        <td>
                            <textarea name="gk_game_icon_svg" id="gk_game_icon_svg" rows="4" style="width:100%; max-width:500px; font-family:monospace; direction:ltr;"><?php echo esc_textarea($saved_svg); ?></textarea>
                            <input type="hidden" name="gk_game_icon_preset" id="gk_game_icon_preset" value="<?php echo esc_attr($saved_preset); ?>" />
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <script>
        (function() {
            var buttons = document.querySelectorAll('.gk-preset-select-btn');
            var svgInput = document.getElementById('gk_game_icon_svg');
            var bgInput = document.getElementById('gk_game_bg_gradient');
            var presetInput = document.getElementById('gk_game_icon_preset');
            var previewBox = document.getElementById('gk_live_preview_box');
            var previewSvg = document.getElementById('gk_live_preview_svg');
            var presetLabel = document.getElementById('gk_current_preset_label');

            function updatePreview(svg, bg, title) {
                if (previewBox && bg) previewBox.style.background = bg;
                if (previewSvg && svg) previewSvg.innerHTML = svg;
                if (presetLabel && title) presetLabel.innerText = 'قالب فعال: ' + title;
            }

            buttons.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    buttons.forEach(function(b) { b.style.borderColor = '#e2e8f0'; });
                    btn.style.borderColor = '#4f46e5';

                    var key = btn.getAttribute('data-key');
                    var bg = btn.getAttribute('data-bg');
                    var title = btn.getAttribute('data-title');
                    var svg = btn.querySelector('.gk-preset-raw-svg').value;

                    if (svgInput) svgInput.value = svg;
                    if (bgInput) bgInput.value = bg;
                    if (presetInput) presetInput.value = key;

                    updatePreview(svg, bg, title);
                });
            });

            if (svgInput) {
                svgInput.addEventListener('input', function() {
                    updatePreview(svgInput.value, bgInput.value, 'سفارشی');
                    if (presetInput) presetInput.value = 'custom';
                });
            }
            if (bgInput) {
                bgInput.addEventListener('input', function() {
                    updatePreview(svgInput.value, bgInput.value, 'سفارشی');
                });
            }
        })();
        </script>
        <?php
    }

    public function save_meta_box_data($post_id) {
        if (!isset($_POST['gk_game_meta_nonce_field']) || !wp_verify_nonce($_POST['gk_game_meta_nonce_field'], 'gk_game_meta_nonce')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        if (isset($_POST['gk_game_access'])) update_post_meta($post_id, '_gk_game_access', sanitize_text_field($_POST['gk_game_access']));
        if (isset($_POST['gk_game_folder'])) update_post_meta($post_id, '_gk_game_folder', sanitize_text_field($_POST['gk_game_folder']));
        if (isset($_POST['gk_game_aspect'])) update_post_meta($post_id, '_gk_game_aspect', sanitize_text_field($_POST['gk_game_aspect']));

        // Save Game Icon & Gradient
        if (isset($_POST['gk_game_icon_svg'])) {
            $svg = trim($_POST['gk_game_icon_svg']);
            // Allow svg tags and attributes
            update_post_meta($post_id, '_gk_game_icon_svg', $svg);
        }
        if (isset($_POST['gk_game_bg_gradient'])) {
            update_post_meta($post_id, '_gk_game_bg_gradient', sanitize_text_field($_POST['gk_game_bg_gradient']));
        }
        if (isset($_POST['gk_game_icon_preset'])) {
            update_post_meta($post_id, '_gk_game_icon_preset', sanitize_text_field($_POST['gk_game_icon_preset']));
        }
    }

    public function override_archive_template($template) {
        if (is_tax('game_age_group') || is_tax('game_category')) {
            $term = get_queried_object();
            if ($term && !empty($term->slug)) {
                $param = is_tax('game_age_group') ? 'age' : 'cat';
                wp_safe_redirect(add_query_arg($param, urlencode($term->name), home_url('/games/')), 301);
                exit;
            }
        }
        return $template;
    }
}