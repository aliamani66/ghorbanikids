<?php
/**
 * Utility & Central Contact / Bank Settings for GhorbaniKids
 */

if (!defined('ABSPATH')) {
    exit;
}

class GK_Utils {

    public static function init() {
        $instance = new self();
        add_action('wp_enqueue_scripts', [$instance, 'disable_select2_bug'], 9999);
        add_filter('cron_request', [$instance, 'prevent_hanging_cron_loopback'], 10, 1);
        add_filter('pre_wp_mail', [$instance, 'safe_pre_wp_mail'], 10, 2);
        add_action('admin_init', [__CLASS__, 'register_contact_settings']);
        add_action('admin_init', [__CLASS__, 'register_bank_settings']);
    }

    public static function register_contact_settings() {
        register_setting('general', 'gk_support_phone', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '09306197877'
        ]);
        register_setting('general', 'gk_bale_support_url', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'https://ble.ir/ghorbanikids'
        ]);
        register_setting('general', 'gk_bale_channel_url', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'https://ble.ir/ghorbanikids'
        ]);
        register_setting('general', 'gk_instagram_url', [
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => 'https://instagram.com/ghorbanikids'
        ]);
        register_setting('general', 'gk_telegram_url', [
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => 'https://t.me/ghorbanikids'
        ]);

        add_settings_section(
            'gk_contact_settings_section',
            '📞 راه‌های ارتباطی، پیام‌رسان‌ها و پشتیبانی قربانی کیدز',
            function() {
                echo '<p style="color:#0284c7; font-weight:700;">لینک‌های پیام‌رسان‌ها و اکانت پشتیبانی را در این بخش تنظیم کنید. این اطلاعات به صورت خودکار در هدر، سایدبار، فوتر و دکمه‌های ارسال فیش واریز اعمال می‌شوند.</p>';
            },
            'general'
        );

        add_settings_field(
            'gk_support_phone',
            'شماره تماس پشتیبانی',
            function() {
                $val = self::get_phone();
                echo '<input type="text" name="gk_support_phone" value="' . esc_attr($val) . '" class="regular-text" style="direction:ltr; font-family:monospace;" placeholder="09306197877" />';
            },
            'general',
            'gk_contact_settings_section'
        );

        add_settings_field(
            'gk_bale_support_url',
            '🟢 اکانت پشتیبانی بله (جهت چت مستقیم و ارسال فیش)',
            function() {
                $val = get_option('gk_bale_support_url', 'https://ble.ir/ghorbanikids');
                echo '<input type="text" name="gk_bale_support_url" value="' . esc_attr($val) . '" class="regular-text" style="direction:ltr;" placeholder="https://ble.ir/ghorbanikids_support یا آیدی" />';
                echo '<p class="description">مشتریان پس از ثبت سفارش، تصویر فیش و پیام‌های خود را به این اکانت ارسال می‌کنند (می‌توانید لینک کامل https://ble.ir/... یا فقط آیدی را بنویسید).</p>';
            },
            'general',
            'gk_contact_settings_section'
        );

        add_settings_field(
            'gk_bale_channel_url',
            '📢 کانال اطلاع‌رسانی بله (جهت اخبار و آموزش‌ها)',
            function() {
                $val = get_option('gk_bale_channel_url', 'https://ble.ir/ghorbanikids');
                echo '<input type="text" name="gk_bale_channel_url" value="' . esc_attr($val) . '" class="regular-text" style="direction:ltr;" placeholder="https://ble.ir/ghorbanikids" />';
                echo '<p class="description">لینک کانال عمومی بله که در آیکون‌های شبکه‌های اجتماعی منو و فوتر جهت عضویت نمایش داده می‌شود.</p>';
            },
            'general',
            'gk_contact_settings_section'
        );

        add_settings_field(
            'gk_instagram_url',
            'لینک اینستاگرام',
            function() {
                $val = self::get_instagram_url();
                echo '<input type="url" name="gk_instagram_url" value="' . esc_attr($val) . '" class="regular-text" style="direction:ltr;" placeholder="https://instagram.com/ghorbanikids" />';
            },
            'general',
            'gk_contact_settings_section'
        );

        add_settings_field(
            'gk_telegram_url',
            'لینک کانال تلگرام',
            function() {
                $val = self::get_telegram_url();
                echo '<input type="url" name="gk_telegram_url" value="' . esc_attr($val) . '" class="regular-text" style="direction:ltr;" placeholder="https://t.me/ghorbanikids" />';
            },
            'general',
            'gk_contact_settings_section'
        );
    }

    public static function register_bank_settings() {
        register_setting('general', 'gk_bank_card_number', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '6037-9979-6037-9979'
        ]);
        register_setting('general', 'gk_bank_card_holder', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'مدیریت قربانی کیدز'
        ]);
        register_setting('general', 'gk_bank_name', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'بانک ملی ایران'
        ]);
        register_setting('general', 'gk_bank_card_sub', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'حساب اختصاصی قربانی کیدز'
        ]);

        add_settings_section(
            'gk_bank_settings_section',
            '💳 اطلاعات کارت و حساب بانکی جهت واریز و تسویه‌حساب (GhorbaniKids)',
            function() {
                echo '<p style="color:#4f46e5; font-weight:700;">شماره کارت و اطلاعات حساب بانکی زیر در صفحه پرداخت و تشکر از خرید (کارت به کارت) نمایش داده می‌شود و کاربران واریز را به این شماره انجام می‌دهند.</p>';
            },
            'general'
        );

        add_settings_field(
            'gk_bank_card_number',
            'شماره ۱۶ رقمی کارت بانکی',
            function() {
                $val = self::get_bank_card_formatted();
                echo '<input type="text" name="gk_bank_card_number" value="' . esc_attr($val) . '" class="regular-text" style="direction:ltr; font-family:monospace; font-weight:bold; font-size:15px; letter-spacing:2px;" placeholder="6037-9979-6037-9979" />';
                echo '<p class="description">شماره ۱۶ رقمی کارت (می‌توانید با خط تیره یا بدون خط تیره وارد کنید).</p>';
            },
            'general',
            'gk_bank_settings_section'
        );

        add_settings_field(
            'gk_bank_card_holder',
            'نام صاحب حساب (دارنده کارت)',
            function() {
                $val = self::get_bank_card_holder();
                echo '<input type="text" name="gk_bank_card_holder" value="' . esc_attr($val) . '" class="regular-text" placeholder="مدیریت قربانی کیدز" />';
            },
            'general',
            'gk_bank_settings_section'
        );

        add_settings_field(
            'gk_bank_name',
            'نام بانک',
            function() {
                $val = self::get_bank_name();
                echo '<input type="text" name="gk_bank_name" value="' . esc_attr($val) . '" class="regular-text" placeholder="بانک ملی ایران" />';
            },
            'general',
            'gk_bank_settings_section'
        );

        add_settings_field(
            'gk_bank_card_sub',
            'توضیحات حساب (زیر نام بانک)',
            function() {
                $val = self::get_bank_card_sub();
                echo '<input type="text" name="gk_bank_card_sub" value="' . esc_attr($val) . '" class="regular-text" placeholder="حساب اختصاصی قربانی کیدز" />';
            },
            'general',
            'gk_bank_settings_section'
        );
    }

    public static function get_phone() {
        return get_option('gk_support_phone', '09306197877') ?: '09306197877';
    }

    public static function get_phone_display() {
        $raw = self::get_phone();
        $p = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        $e = ['0','1','2','3','4','5','6','7','8','9'];
        return str_replace($e, $p, $raw);
    }

    private static function format_bale_url($val, $fallback = 'https://ble.ir/ghorbanikids') {
        if (empty($val)) return $fallback;
        $val = trim($val);
        if (strpos($val, 'http://') === 0 || strpos($val, 'https://') === 0) {
            return $val;
        }
        $val = ltrim($val, '@');
        return 'https://ble.ir/' . $val;
    }

    public static function get_bale_support_url() {
        $val = get_option('gk_bale_support_url', '');
        if (empty($val)) {
            $val = get_option('gk_ble_url', 'https://ble.ir/ghorbanikids');
        }
        return self::format_bale_url($val, 'https://ble.ir/ghorbanikids');
    }

    public static function get_bale_channel_url() {
        $val = get_option('gk_bale_channel_url', '');
        if (empty($val)) {
            $val = get_option('gk_ble_url', 'https://ble.ir/ghorbanikids');
        }
        return self::format_bale_url($val, 'https://ble.ir/ghorbanikids');
    }

    public static function get_ble_url() {
        return self::get_bale_support_url();
    }

    public static function get_instagram_url() {
        return get_option('gk_instagram_url', 'https://instagram.com/ghorbanikids') ?: 'https://instagram.com/ghorbanikids';
    }

    public static function get_telegram_url() {
        return get_option('gk_telegram_url', 'https://t.me/ghorbanikids') ?: 'https://t.me/ghorbanikids';
    }

    /**
     * Bank Card & Payment Helpers
     */
    public static function get_bank_card() {
        $card = get_option('gk_bank_card_number');
        if (empty($card)) {
            $bacs_settings = get_option('woocommerce_bacs_settings', []);
            $instructions = isset($bacs_settings['instructions']) ? $bacs_settings['instructions'] : '';
            if (preg_match('/(\d{4}[-\s]?\d{4}[-\s]?\d{4}[-\s]?\d{4})/u', $instructions, $m)) {
                $card = str_replace(' ', '-', $m[1]);
            } else {
                $card = '6037-9979-6037-9979';
            }
        }
        return $card;
    }

    public static function get_bank_card_formatted() {
        $raw = self::get_bank_card();
        $digits = preg_replace('/[^\d]/', '', $raw);
        if (strlen($digits) === 16) {
            return substr($digits, 0, 4) . '-' . substr($digits, 4, 4) . '-' . substr($digits, 8, 4) . '-' . substr($digits, 12, 4);
        }
        return $raw ?: '6037-9979-6037-9979';
    }

    public static function get_bank_card_digits() {
        $raw = self::get_bank_card();
        return preg_replace('/[^\d]/', '', $raw) ?: '6037997960379979';
    }

    public static function get_bank_card_holder() {
        return get_option('gk_bank_card_holder', 'مدیریت قربانی کیدز') ?: 'مدیریت قربانی کیدز';
    }

    public static function get_bank_name() {
        return get_option('gk_bank_name', 'بانک ملی ایران') ?: 'بانک ملی ایران';
    }

    public static function get_bank_card_sub() {
        return get_option('gk_bank_card_sub', 'حساب اختصاصی قربانی کیدز') ?: 'حساب اختصاصی قربانی کیدز';
    }

    public function disable_select2_bug() {
        wp_dequeue_script('selectWoo');
        wp_deregister_script('selectWoo');
        wp_dequeue_script('select2');
        wp_deregister_script('select2');
        wp_dequeue_style('select2');
        wp_deregister_style('select2');
        wp_dequeue_style('selectWoo');
        wp_deregister_style('selectWoo');
    }

    public function prevent_hanging_cron_loopback($cron_request_array) {
        if (is_array($cron_request_array)) {
            $cron_request_array['args']['timeout'] = 0.01;
            $cron_request_array['args']['blocking'] = false;
        }
        return $cron_request_array;
    }

    public function safe_pre_wp_mail($null, $atts) {
        if (!function_exists('mail')) {
            return true;
        }
        return null;
    }
}