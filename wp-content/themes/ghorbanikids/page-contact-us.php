<?php
/**
 * Template Name: Contact Us Page - GhorbaniKids
 */

get_header();
?>
<main class="gk-site-main gk-contact-page-main">
    <div class="gk-container">
        <!-- ۱. هیرو تماس با ما -->
        <section class="gk-contact-hero-card">
            <span class="gk-contact-badge">📞 پشتیبانی و ارتباط مستقیم</span>
            <h1 class="gk-contact-title">همواره در کنارتان هستیم</h1>
            <p class="gk-contact-subtitle">
                تیم پشتیبانی و کارشناسان قربانی کیدز آماده پاسخگویی به پرسش‌ها، پیشنهادات و راهنمایی والدین، مربیان و مدیران گرامی می‌باشند.
            </p>
        </section>

        <!-- ۲. کارت‌های ارتباطی ۳ گانه -->
        <section class="gk-contact-cards-grid">
            <div class="gk-contact-method-card">
                <div class="gk-c-icon-badge bg-blue">📞</div>
                <h3 class="gk-c-card-title">تماس تلفنی و پشتیبانی</h3>
                <p class="gk-c-card-desc">شنبه تا پنج‌شنبه از ساعت ۹ الی ۱۹</p>
                <a href="tel:<?php echo esc_html(GK_Utils::get_phone()); ?>" class="gk-c-action-btn">
                    <span>۰۹۳۰ ۶۱۹ ۷۸۷۷</span>
                </a>
            </div>

            <div class="gk-contact-method-card">
                <div class="gk-c-icon-badge bg-cyan">📱</div>
                <h3 class="gk-c-card-title">پیام‌رسان بله و تلگرام</h3>
                <p class="gk-c-card-desc">پاسخگویی آنلاین و ثبت نام مهدها</p>
                <a href="<?php echo esc_url(GK_Utils::get_ble_url()); ?>" target="_blank" rel="noopener" class="gk-c-action-btn">
                    <span>@GhorbaniKids</span>
                </a>
            </div>

            <div class="gk-contact-method-card">
                <div class="gk-c-icon-badge bg-purple">✉️</div>
                <h3 class="gk-c-card-title">پشتیبانی ایمیل</h3>
                <p class="gk-c-card-desc">پاسخگویی سریع در کمتر از ۲۴ ساعت</p>
                <a href="mailto:info@ghorbanikids.ir" class="gk-c-action-btn">
                    <span>info@ghorbanikids.ir</span>
                </a>
            </div>
        </section>

        <!-- ۳. فرم ارسال پیام -->
        <section class="gk-contact-form-section">
            <div class="gk-contact-form-card">
                <h2 class="gk-form-heading">ارسال پیام مستقیم به مدیریت و پشتیبانی</h2>
                <p class="gk-form-subheading">دیدگاه‌ها، پیشنهادات و درخواست‌های همکاری خود را برای ما ارسال فرمایید.</p>

                <?php
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gk_contact_nonce']) && wp_verify_nonce($_POST['gk_contact_nonce'], 'gk_send_contact_msg')) {
                    $name    = sanitize_text_field($_POST['cf_name'] ?? '');
                    $contact = sanitize_text_field($_POST['cf_contact'] ?? '');
                    $role    = sanitize_text_field($_POST['cf_role'] ?? '');
                    $subject = sanitize_text_field($_POST['cf_subject'] ?? '');
                    $msg     = sanitize_textarea_field($_POST['cf_message'] ?? '');

                    $to = 'info@ghorbanikids.ir';
                    $email_subject = "پیام جدید از سایت: {$subject} ({$role})";
                    $body = "نام: {$name}\nتماس: {$contact}\nنقش: {$role}\nموضوع: {$subject}\n\nمتن پیام:\n{$msg}";
                    $headers = ['Content-Type: text/plain; charset=UTF-8'];

                    wp_mail($to, $email_subject, $body, $headers);
                    echo '<div class="gk-form-success-alert">✅ پیام شما با موفقیت ارسال گردید. کارشناسان ما به زودی با شما تماس خواهند گرفت.</div>';
                }
                ?>

                <form action="" method="post" class="gk-direct-contact-form">
                    <?php wp_nonce_field('gk_send_contact_msg', 'gk_contact_nonce'); ?>

                    <div class="gk-form-row-2">
                        <div class="gk-form-group">
                            <label class="gk-form-label">نام و نام خانوادگی: <span class="req">*</span></label>
                            <input type="text" name="cf_name" required placeholder="مثال: علی رضایی" class="gk-form-input" />
                        </div>
                        <div class="gk-form-group">
                            <label class="gk-form-label">شماره موبایل یا ایمیل: <span class="req">*</span></label>
                            <input type="text" name="cf_contact" required placeholder="۰۹۱۲... یا info@..." class="gk-form-input" />
                        </div>
                    </div>

                    <div class="gk-form-row-2">
                        <div class="gk-form-group">
                            <label class="gk-form-label">شما کدام هستید؟</label>
                            <select name="cf_role" class="gk-form-select">
                                <option value="والدین">والدین گرامی</option>
                                <option value="مدیر مهدکودک">مدیر مهدکودک یا پیش‌دبستانی</option>
                                <option value="معلم / مربی">معلم / مربی آموزشی</option>
                                <option value="روانشناس / مشاور">روانشناس / مشاور کودک</option>
                                <option value="سایر">سایر</option>
                            </select>
                        </div>
                        <div class="gk-form-group">
                            <label class="gk-form-label">موضوع پیام: <span class="req">*</span></label>
                            <input type="text" name="cf_subject" required placeholder="مثال: درخواست اشتراک مهدکودک" class="gk-form-input" />
                        </div>
                    </div>

                    <div class="gk-form-group">
                        <label class="gk-form-label">متن پیام: <span class="req">*</span></label>
                        <textarea name="cf_message" rows="5" required placeholder="متن پیام، پرسش یا پیشنهاد خود را اینجا بنویسید..." class="gk-form-textarea"></textarea>
                    </div>

                    <div class="gk-form-submit-row">
                        <button type="submit" class="gk-btn-submit-contact">
                            <span>✉️ ارسال پیام به پشتیبانی</span>
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </div>
</main>
<?php
get_footer();