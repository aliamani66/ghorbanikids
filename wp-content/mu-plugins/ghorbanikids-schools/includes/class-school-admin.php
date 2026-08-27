<?php
/**
 * Class GK_School_Admin
 * Super-Admin Management for Kindergartens & Schools in WordPress wp-admin
 */
if (!defined('ABSPATH')) exit;

class GK_School_Admin {

    public static function init() {
        add_action('admin_menu', [__CLASS__, 'register_admin_menu'], 30);
    }

    public static function register_admin_menu() {
        add_submenu_page(
            'gk-assessments-admin',
            'مدیریت مهدکودک‌ها و مدارس',
            '🏢 مهدکودک‌ها (B2B)',
            'manage_options',
            'gk-schools-admin',
            [__CLASS__, 'render_schools_admin_page']
        );
    }

    public static function render_schools_admin_page() {
        global $wpdb;
        $table_orgs = $wpdb->prefix . 'gk_organizations';
        $table_students = $wpdb->prefix . 'gk_students';
        $table_classes = $wpdb->prefix . 'gk_classes';

        $orgs = $wpdb->get_results("SELECT o.*, u.user_login, u.user_email FROM $table_orgs o LEFT JOIN {$wpdb->users} u ON o.manager_user_id = u.ID ORDER BY o.id DESC");
        ?>
        <div class="wrap" style="direction: rtl; font-family: Tahoma, sans-serif;">
            <h1 style="font-size: 24px; font-weight: bold; margin-bottom: 20px; color: #1e293b;">
                🏢 مدیریت مهدکودک‌ها، مدارس و لایسنس‌های سازمانی (B2B)
            </h1>

            <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 14px rgba(0,0,0,0.04);">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>نام مهدکودک / مدرسه</th>
                            <th>مدیر (کاربر)</th>
                            <th>شهر / تلفن</th>
                            <th>تعداد کلاس‌ها</th>
                            <th>سهمیه مصرف‌شده</th>
                            <th>اعتبار لایسنس</th>
                            <th>وضعیت</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orgs)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 30px; color: #94a3b8;">
                                    هنوز مهدکودکی ثبت‌نام نکرده است.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orgs as $o): 
                                $class_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_classes WHERE org_id = %d", $o->id));
                                $student_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_students WHERE org_id = %d", $o->id));
                            ?>
                                <tr>
                                    <td><strong>#<?php echo $o->id; ?></strong></td>
                                    <td>
                                        <strong style="font-size: 14px; color: #1e293b;"><?php echo esc_html($o->name); ?></strong>
                                    </td>
                                    <td><?php echo esc_html($o->user_login . ' (' . $o->user_email . ')'); ?></td>
                                    <td><?php echo esc_html($o->city ?: '-') . ' | ' . esc_html($o->phone ?: '-'); ?></td>
                                    <td><strong><?php echo $class_count; ?> کلاس</strong></td>
                                    <td>
                                        <span style="background: #ede9fe; color: #5641e5; padding: 4px 10px; border-radius: 10px; font-weight: bold;">
                                            <?php echo $student_count; ?> از <?php echo $o->student_limit; ?> کودک
                                        </span>
                                    </td>
                                    <td><?php echo date_i18n('j F Y', strtotime($o->expires_at)); ?></td>
                                    <td>
                                        <span style="background: #dcfce7; color: #15803d; padding: 4px 10px; border-radius: 10px; font-weight: bold; font-size: 12px;">
                                            <?php echo esc_html($o->status); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
}