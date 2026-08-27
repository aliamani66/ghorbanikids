<?php
/**
 * Plugin Name: GhorbaniKids Games & Subscriptions Manager (Modular v2)
 * Description: سیستم ماژولار و استاندارد مدیریت بازی‌ها، اشتراک‌های VIP، تسویه‌حساب ووکامرس و هدر اختصاصی قربانی کیدز
 * Version: 6.1.0
 * Author: GhorbaniKids
 */

if (!defined('ABSPATH')) {
    exit;
}

// تعریف ثوابت ماژول
define('GK_GAMES_MANAGER_VERSION', '6.1.0');
define('GK_GAMES_MANAGER_PATH', plugin_dir_path(__FILE__));
define('GK_GAMES_MANAGER_URL', plugin_dir_url(__FILE__));

class GhorbaniKids_Games_Manager {

    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->init_modules();
    }

    private function load_dependencies() {
        // ۱. ابزارها و پایداری هسته
        require_once GK_GAMES_MANAGER_PATH . 'includes/class-gk-utils.php';

        // ۲. هدر، ناوبری، فوتر و صفحات اطلاعاتی
        require_once GK_GAMES_MANAGER_PATH . 'includes/class-gk-header.php';
        require_once GK_GAMES_MANAGER_PATH . 'includes/class-gk-sidebar.php';
                require_once GK_GAMES_MANAGER_PATH . 'includes/class-gk-footer.php';
        require_once GK_GAMES_MANAGER_PATH . 'includes/class-gk-homepage.php';
        require_once GK_GAMES_MANAGER_PATH . 'includes/class-gk-cache-control.php';
        require_once GK_GAMES_MANAGER_PATH . 'includes/class-gk-pages.php';

        // ۳. احراز هویت، اشتراک و حساب کاربری
        require_once GK_GAMES_MANAGER_PATH . 'includes/class-gk-auth.php';
        require_once GK_GAMES_MANAGER_PATH . 'includes/class-gk-subscriptions.php';

        // ۴. فروشگاه، سبد خرید، تسویه‌حساب و قیمت‌گذاری
        require_once GK_GAMES_MANAGER_PATH . 'includes/class-gk-cart.php';
        require_once GK_GAMES_MANAGER_PATH . 'includes/class-gk-checkout.php';
        require_once GK_GAMES_MANAGER_PATH . 'includes/class-gk-pricing.php';

        // ۵. بازی‌ها، پلیر و کاتالوگ
        require_once GK_GAMES_MANAGER_PATH . 'includes/class-gk-game-assets.php';
        require_once GK_GAMES_MANAGER_PATH . 'includes/class-gk-cpt.php';
        require_once GK_GAMES_MANAGER_PATH . 'includes/class-gk-player.php';
        require_once GK_GAMES_MANAGER_PATH . 'includes/class-gk-catalog.php';
        require_once GK_GAMES_MANAGER_PATH . 'includes/class-gk-curriculum.php';
        require_once GK_GAMES_MANAGER_PATH . 'includes/class-gk-curriculum-tests.php';
    }

    private function init_modules() {
        GK_Utils::init();
        GK_Header::init();
        GK_Sidebar::init();
                GK_Footer::init();
        GK_Homepage::init();
        GK_Pages::init();
        GK_Auth::init();
        GK_Subscriptions::init();
        GK_Cart::init();
        GK_Checkout::init();
        GK_Pricing::init();
        GK_CPT::init();
        GK_Player::init();
        GK_Catalog::init();
        GK_Curriculum::init();
        GK_Curriculum_Tests::init();
    }
}

// راه‌اندازی ماژول
function ghorbanikids_games_manager_init() {
    return GhorbaniKids_Games_Manager::get_instance();
}

// نام مستعار جهت سازگاری کامل کدهای قبلی (Backward Compatibility)
if (!class_exists('GhorbaniKids_Games')) {
    class_alias('GK_Subscriptions', 'GhorbaniKids_Games');
}

// Auto-initialize
ghorbanikids_games_manager_init();
