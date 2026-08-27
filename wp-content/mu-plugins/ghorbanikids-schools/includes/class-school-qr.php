<?php
/**
 * Class GK_School_QR
 * Generates Printable A4 Sheets with QR Cards and Clickable Direct Links for Parents
 */
if (!defined('ABSPATH')) exit;

class GK_School_QR {

    public static function init() {
        add_action('template_redirect', [__CLASS__, 'handle_print_request']);
    }

    public static function handle_print_request() {
        if (isset($_GET['gk_action']) && $_GET['gk_action'] === 'print_class_cards' && isset($_GET['class_id'])) {
            header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
            header("Pragma: no-cache");
            header("Expires: 0");
            if (defined('LSCWP_V')) {
                do_action('litespeed_control_set_nocache', 'Live QR cards');
            }
            $class_id = intval($_GET['class_id']);
            self::render_printable_cards($class_id);
            exit;
        }
    }

    public static function render_printable_cards($class_id) {
        global $wpdb;
        $table_classes  = $wpdb->prefix . 'gk_classes';
        $table_orgs     = $wpdb->prefix . 'gk_organizations';
        $table_students = $wpdb->prefix . 'gk_students';

        $class = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_classes WHERE id = %d", $class_id));
        if (!$class) wp_die('کلاس یافت نشد.');

        $org = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_orgs WHERE id = %d", $class->org_id));
        $students = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_students WHERE class_id = %d ORDER BY id ASC", $class_id));

        $org_name = $org ? $org->name : 'مهدکودک';
        ?>
        <!DOCTYPE html>
        <html lang="fa" dir="rtl">
        <head>
            <meta charset="UTF-8">
            <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
            <meta http-equiv="Pragma" content="no-cache" />
            <meta http-equiv="Expires" content="0" />
            <title>چاپ کارت‌های ارزیابی کلاسی - <?php echo esc_html($class->name); ?></title>
            <style>
            /* ==========================================================
               استاندارد فونت یکپارچه و لوکس مدارس قربانی کیدز (Typography Standard)
               ========================================================== */
            @font-face {
                font-family: 'aviny';
                src: url('/wp-content/uploads/2021/10/aviny-web.woff2') format('woff2'),
                     url('/wp-content/uploads/2021/10/aviny-web.woff') format('woff'),
                     url('/wp-content/uploads/2021/10/aviny.ttf') format('truetype');
                font-weight: normal;
                font-style: normal;
                font-display: swap;
            }
            @font-face {
                font-family: 'IRANSansXFaNum';
                src: url('/wp-content/uploads/2021/10/IRANSansXFaNum-Regular.woff2') format('woff2'),
                     url('/wp-content/uploads/2021/10/IRANSansXFaNum-Regular.woff') format('woff'),
                     url('/wp-content/uploads/2021/10/IRANSansXFaNum-Regular.ttf') format('truetype');
                font-weight: normal;
                font-style: normal;
                font-display: swap;
            }

            .gk-school-wrap,
            .gk-school-wrap *,
            .gk-teacher-wrap,
            .gk-teacher-wrap *,
            .gk-league-wrap,
            .gk-league-wrap *,
            .gk-modal-backdrop,
            .gk-modal-backdrop *,
            .gk-modal-card,
            .gk-modal-card *,
            input, select, textarea, button {
                font-family: 'IRANSansXFaNum', 'IRANSansX', 'IRANSans', 'Vazirmatn', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
                -webkit-font-smoothing: antialiased;
                -moz-osx-font-smoothing: grayscale;
            }

            /* تیترها با فونت شاداب آوینی */
            .gk-school-title-text,
            .gk-school-brand-title,
            .gk-box-head h3,
            .gk-modal-card h3,
            .gk-teacher-header h1,
            .gk-league-header h1,
            .gk-hero-title {
                font-family: 'aviny', 'Aviny', 'IRANSansXFaNum', 'IRANSansX', sans-serif !important;
                letter-spacing: 0.5px !important;
            }

                @page {
                    size: A4 portrait;
                    margin: 10mm;
                }
                body {
                    font-family: Tahoma, 'B Nazanin', sans-serif;
                    background: #f1f5f9;
                    margin: 0;
                    padding: 20px;
                    direction: rtl;
                }
                .no-print-bar {
                    background: #1e293b;
                    color: #fff;
                    padding: 16px 24px;
                    border-radius: 18px;
                    margin: 0 auto 24px auto;
                    max-width: 1050px;
                    box-sizing: border-box;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
                }
                .btn-print {
                    background: #6c5ce7;
                    color: #fff;
                    border: none;
                    padding: 10px 24px;
                    border-radius: 12px;
                    font-weight: 900;
                    font-size: 14px;
                    cursor: pointer;
                    box-shadow: 0 4px 12px rgba(108, 92, 231, 0.35);
                }
                @media print {
                    .no-print-bar { display: none !important; }
                    .gk-interactive-row { display: none !important; }
                    body { padding: 0 !important; background: #fff !important; }
                }
                .gk-cards-sheet {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 20px;
                    max-width: 1050px;
                    margin: 0 auto;
                }
                @media (max-width: 768px) {
                    .gk-cards-sheet { grid-template-columns: 1fr; }
                }
                .gk-student-qr-card {
                    border: 2.5px dashed #94a3b8;
                    border-radius: 24px;
                    padding: 22px;
                    background: #ffffff;
                    display: flex;
                    flex-direction: column;
                    justify-content: space-between;
                    page-break-inside: avoid;
                    box-sizing: border-box;
                    min-height: 310px;
                    box-shadow: 0 6px 20px rgba(0,0,0,0.04);
                }
                .gk-card-top {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    border-bottom: 2px solid #f1f5f9;
                    padding-bottom: 12px;
                    margin-bottom: 16px;
                }
                .gk-card-body {
                    display: flex;
                    align-items: flex-start;
                    gap: 18px;
                }
                .gk-qr-image {
                    width: 115px;
                    height: 115px;
                    border-radius: 14px;
                    border: 2px solid #e2e8f0;
                    background: #fff;
                    padding: 4px;
                    display: block;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
                    flex-shrink: 0;
                }
                .gk-card-instructions {
                    font-size: 12px;
                    color: #475569;
                    line-height: 1.75;
                    margin-top: 6px;
                }
                .gk-interactive-row {
                    display: flex;
                    gap: 8px;
                    margin-top: 14px;
                    background: #f8fafc;
                    padding: 10px;
                    border-radius: 14px;
                    border: 1.5px solid #e2e8f0;
                }
                .btn-card-action {
                    flex: 1;
                    text-align: center;
                    padding: 8px 12px;
                    border-radius: 10px;
                    font-size: 12.5px;
                    font-weight: 900;
                    text-decoration: none !important;
                    cursor: pointer;
                    border: none;
                    display: inline-block;
                }
                .btn-card-copy {
                    background: #6c5ce7;
                    color: #fff !important;
                }
                .btn-card-open {
                    background: #0984e3;
                    color: #fff !important;
                }
                .gk-card-footer {
                    margin-top: 16px;
                    font-size: 11.5px;
                    color: #64748b;
                    text-align: center;
                    border-top: 1.5px solid #f1f5f9;
                    padding-top: 10px;
                    font-weight: bold;
                }
            </style>
        </head>
        <body>
            <div class="no-print-bar">
                <div>
                    <strong style="font-size: 16px;">🖨️ چاپ کارت‌های ارزیابی دانش‌آموزان و نوآموزان (نسخه اولیا)</strong>
                    <div style="font-size: 12.5px; opacity: 0.85; margin-top: 4px;">
                        🏢 <?php echo esc_html($org_name); ?> — 🏫 کلاس: <?php echo esc_html($class->name); ?> (<?php echo count($students); ?> نوآموز)
                    </div>
                </div>
                <div>
                    <button type="button" class="btn-print" onclick="window.print();">🖨️ چاپ با پرینتر (یا ذخیره PDF)</button>
                </div>
            </div>

            <div class="gk-cards-sheet">
                <?php if (empty($students)): ?>
                    <div style="grid-column: 1/-1; text-align: center; padding: 50px; background: #fff; border-radius: 20px; border: 2px dashed #cbd5e1;">
                        <h3 style="margin: 0 0 8px 0; color: #1e293b;">هنوز نوآموزی در این کلاس ثبت نشده است!</h3>
                    </div>
                <?php else: ?>
                    <?php foreach ($students as $st): 
                        $token_url = home_url('/tests/?st_token=' . $st->student_token);
                        $qr_src = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . urlencode($token_url);
                    ?>
                        <div class="gk-student-qr-card">
                            <div>
                                <div class="gk-card-top">
                                    <div>
                                        <strong style="font-size: 14.5px; color: #1e293b;">🏢 <?php echo esc_html($org_name); ?></strong>
                                    </div>
                                    <span style="font-size: 12px; background: #e0e7ff; color: #4338ca; padding: 4px 12px; border-radius: 10px; font-weight: 900;">
                                        <?php echo esc_html($class->name); ?>
                                    </span>
                                </div>
                                <div class="gk-card-body">
                                    <a href="<?php echo esc_url($token_url); ?>" target="_blank" title="کلیک برای باز کردن آزمون">
                                        <img src="<?php echo esc_url($qr_src); ?>" alt="QR" class="gk-qr-image">
                                    </a>
                                    <div style="flex-grow: 1;">
                                        <div style="font-size: 18px; font-weight: 900; color: #0f172a; margin-bottom: 4px;">
                                            👶 نوآموز: <?php echo esc_html($st->name); ?>
                                        </div>
                                        <div style="font-size: 13px; color: #64748b; margin-bottom: 6px;">
                                            سن: <strong><?php echo esc_html($st->age); ?> ساله</strong>
                                        </div>
                                        <div class="gk-card-instructions">
                                            <strong>اولیا گرامی:</strong> لطفاً دوربین گوشی خود را روی بارکد فوق بگیرید تا بدون نیاز به ثبت‌نام، آزمون استعدادیابی فرزندتان آغاز شود.
                                        </div>
                                    </div>
                                </div>

                                <!-- ردیف دکمه‌های تعاملی برای آزمون و بازی‌ها -->
                                <div class="gk-interactive-row">
                                    <a href="<?php echo esc_url($token_url); ?>" target="_blank" class="btn-card-action btn-card-open" style="background:#6366f1;">
                                        🧠 باز کردن آزمون‌ها
                                    </a>
                                    <a href="<?php echo esc_url(home_url('/games/?st_token=' . $st->student_token)); ?>" target="_blank" class="btn-card-action btn-card-copy" style="background:#0984e3;">
                                        🎮 ورود به بازی‌ها و لیگ
                                    </a>
                                </div>
                            </div>

                            <div class="gk-card-footer">
                                سامانه هوشمند ارزیابی و استعدادیابی مدارس و مهدکودک‌ها «قربانی کیدز»
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </body>
        </html>
        <?php
    }
}