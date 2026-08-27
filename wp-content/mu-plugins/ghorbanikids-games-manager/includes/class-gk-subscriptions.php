<?php
/**
 * Subscription management and VIP access checks for GhorbaniKids
 */

if (!defined('ABSPATH')) {
    exit;
}

class GK_Subscriptions {

    public static function init() {
        $instance = new self();
        
        // استایل کارت اشتراک در پیشخوان
        add_action('wp_enqueue_scripts', [$instance, 'enqueue_assets'], 10);
        
        // داشبورد حساب کاربری
        // add_action('woocommerce_account_dashboard', [$instance, 'render_dashboard_subscription_box'], 5);
        add_filter('woocommerce_endpoint_dashboard_title', [$instance, 'custom_dashboard_title']);
        add_filter('gettext', [$instance, 'translate_dashboard_text'], 20, 3);
        
        // ویرایش اشتراک در پروفایل کاربر توسط مدیر
        add_action('show_user_profile', [$instance, 'render_user_subscription_fields']);
        add_action('edit_user_profile', [$instance, 'render_user_subscription_fields']);
        add_action('personal_options_update', [$instance, 'save_user_subscription_fields']);
        add_action('edit_user_profile_update', [$instance, 'save_user_subscription_fields']);
    }

    public function enqueue_assets() {
        $assets_url = plugins_url('assets', dirname(__FILE__));
        if (is_account_page()) {
            wp_enqueue_style('gk-account', $assets_url . '/css/gk-account.css', [], GK_GAMES_MANAGER_VERSION);
        }
    }

    /**
     * بررسی فعال بودن اشتراک VIP کاربر
     */
    public static function user_has_active_subscription($user_id = null) {
        // ۱. اولویت قطعی با نوآموزان مدارس (School Student Token Bypass)
        if (isset($_GET['st_token']) && !empty($_GET['st_token'])) {
            global $wpdb;
            $table_students = $wpdb->prefix . 'gk_students';
            $st = $wpdb->get_row($wpdb->prepare("SELECT id, name FROM $table_students WHERE student_token = %s", sanitize_text_field($_GET['st_token'])));
            if ($st) {
                return [
                    'active' => true,
                    'days_left' => 365,
                    'is_student' => true,
                    'student_name' => $st->name,
                    'expires_at' => null
                ];
            }
        }

        if ($user_id === null) {
            $user_id = get_current_user_id();
        }

        if (!$user_id) {
            return false;
        }

        // ۲. دسترسی نامحدود مدیران و نویسندگان
        if (user_can($user_id, 'manage_options') || user_can($user_id, 'edit_posts')) {
            return [
                'active' => true,
                'days_left' => 999,
                'is_admin' => true,
                'expires_at' => null
            ];
        }

        // ۳. بررسی تاریخ انقضای متای کاربر
        $expires_at = get_user_meta($user_id, '_gk_subscription_expires_at', true);
        if (!$expires_at) {
            return false;
        }

        $now = time();
        if ($now < $expires_at) {
            $days_left = ceil(($expires_at - $now) / 86400);
            return [
                'active' => true,
                'days_left' => $days_left,
                'is_admin' => false,
                'expires_at' => $expires_at
            ];
        }

        return false;
    }

    public function custom_dashboard_title($title) {
        return 'پیشخوان کاربری قربانی کیدز';
    }

    public function translate_dashboard_text($translated, $text, $domain) {
        if ($text === 'From your account dashboard you can view your %1$srecent orders%2$s, manage your %3$sshipping and billing addresses%2$s, and %4$cedit your password and account details%2$s.') {
            return 'از طریق پیشخوان کاربری می‌توانید سفارشات، اشتراک‌های فعال و اطلاعات کاربری خود را مدیریت کنید.';
        }
        return $translated;
    }

    public function render_dashboard_subscription_box() {
        $user_id = get_current_user_id();
        $sub = self::user_has_active_subscription($user_id);
        ?>
        <div class="gk-myaccount-sub-box">
            <?php if ($sub && !empty($sub['active'])): ?>
                <div class="gk-sub-card active">
                    <div class="gk-sub-icon">👑</div>
                    <div>
                        <h3 style="margin:0 0 6px; color:#15803d; font-weight:800;">اشتراک ویژه شما فعال است!</h3>
                        <p style="margin:0; color:#166534; font-size:0.95rem;">
                            شما به تمامی بازی‌های فکری و مهارتی قربانی کیدز دسترسی نامحدود دارید.
                            (<strong><?php echo $sub['days_left']; ?> روز</strong> باقی‌مانده)
                        </p>
                    </div>
                    <a href="<?php echo esc_url(home_url('/games/')); ?>" class="gk-btn gk-btn-fullscreen" style="margin-right:auto; padding:10px 22px; border-radius:20px;">
                        🎮 ورود به بازی‌ها
                    </a>
                </div>
            <?php else: ?>
                <div class="gk-sub-card inactive">
                    <div class="gk-sub-icon">🔒</div>
                    <div>
                        <h3 style="margin:0 0 6px; color:#991b1b; font-weight:800;">هنوز اشتراک فعالی ندارید</h3>
                        <p style="margin:0; color:#b91c1c; font-size:0.95rem;">
                            برای دسترسی نامحدود به بازی‌های فکری و مهارتی، یکی از پلن‌های اشتراک را فعال کنید.
                        </p>
                    </div>
                    <a href="<?php echo esc_url(home_url('/pricing/')); ?>" class="gk-btn gk-btn-subscribe" style="margin-right:auto; padding:10px 22px; border-radius:20px;">
                        👑 خرید اشتراک ویژه
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function render_user_subscription_fields($user) {
        if (!current_user_can('manage_options')) return;
        $expires = get_user_meta($user->ID, '_gk_subscription_expires_at', true);
        $date_str = $expires ? date_i18n('Y/m/d H:i', $expires) : 'اشتراک غیرفعال است';
        $days_left = ($expires && $expires > time()) ? ceil(($expires - time()) / 86400) : 0;
        ?>
        <h3>👑 وضعیت اشتراک بازی‌های قربانی کیدز</h3>
        <table class="form-table">
            <tr>
                <th><label>وضعیت فعلی:</label></th>
                <td>
                    <?php if ($days_left > 0): ?>
                        <span style="color:green; font-weight:bold;">✅ فعال (<?php echo $days_left; ?> روز باقی‌مانده تا <?php echo $date_str; ?>)</span>
                    <?php else: ?>
                        <span style="color:red;">❌ منقضی / غیرفعال</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th><label for="gk_add_days">افزودن روز دستی به اشتراک:</label></th>
                <td>
                    <input type="number" name="gk_add_days" id="gk_add_days" placeholder="تعداد روز (مثلاً ۳۰)" style="width:140px;" />
                    <p class="description">برای تمدید دستی اشتراک کاربر توسط مدیر، تعداد روز را وارد کنید.</p>
                </td>
            </tr>
        </table>
        <?php
    }

    public function save_user_subscription_fields($user_id) {
        if (!current_user_can('manage_options')) return;
        if (!empty($_POST['gk_add_days'])) {
            $days = intval($_POST['gk_add_days']);
            if ($days > 0) {
                $current_expires = (int) get_user_meta($user_id, '_gk_subscription_expires_at', true);
                $base_time = ($current_expires > time()) ? $current_expires : time();
                $new_expires = $base_time + ($days * 86400);
                update_user_meta($user_id, '_gk_subscription_expires_at', $new_expires);
                if (class_exists('LiteSpeed\Purge')) {
                    LiteSpeed\Purge::purge_all();
                }
            }
        }
    }
}