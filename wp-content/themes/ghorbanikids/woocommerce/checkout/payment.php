<?php
/**
 * GhorbaniKids Checkout Payment Section (Unified Payment Method & Bank Card)
 */

defined('ABSPATH') || exit;

if (!wp_doing_ajax()) {
    do_action('woocommerce_review_order_before_payment');
}

$card_num = class_exists('GK_Utils') ? GK_Utils::get_bank_card_formatted() : '6037-9979-6037-9979';
$card_digits_clean = class_exists('GK_Utils') ? GK_Utils::get_bank_card_digits() : '6037997960379979';
$bank_name = class_exists('GK_Utils') ? GK_Utils::get_bank_name() : 'بانک ملی ایران';
$bank_sub = class_exists('GK_Utils') ? GK_Utils::get_bank_card_sub() : 'حساب اختصاصی قربانی کیدز';
$card_holder = class_exists('GK_Utils') ? GK_Utils::get_bank_card_holder() : 'مدیریت قربانی کیدز';
$ble_url = class_exists('GK_Utils') ? GK_Utils::get_bale_support_url() : 'https://ble.ir/ghorbanikids';
?>
<div id="payment" class="woocommerce-checkout-payment">
    
    <!-- ۱. دکمه پرداخت نهایی دقیقاً در بالاترین نقطه زیر مبلغ کل -->
    <div class="form-row place-order gk-place-order-top">
        <noscript>
            <?php
            printf(esc_html__('Since your browser does not support JavaScript, or it is disabled, please ensure you click the %1$sUpdate Totals%2$s button before placing your order. You may be charged more than the amount stated above if you fail to do so.', 'woocommerce'), '<em>', '</em>');
            ?>
            <br/><button type="submit" class="button alt" name="woocommerce_checkout_update_totals" value="<?php esc_attr_e('Update totals', 'woocommerce'); ?>"><?php esc_html_e('Update totals', 'woocommerce'); ?></button>
        </noscript>

        <?php do_action('woocommerce_review_order_before_submit'); ?>

        <?php echo apply_filters('woocommerce_order_button_html', '<button type="submit" class="button alt gk-btn-place-order-main" name="woocommerce_checkout_place_order" id="place_order" value="' . esc_attr($order_button_text) . '" data-value="' . esc_attr($order_button_text) . '">🔒 ' . esc_html($order_button_text) . '</button>'); ?>

        <?php do_action('woocommerce_review_order_after_submit'); ?>

        <?php wp_nonce_field('woocommerce-process_checkout', 'woocommerce-process-checkout-nonce'); ?>
    </div>

    <!-- ۲. قوانین و حریم خصوصی به صورت فشرده -->
    <div class="gk-checkout-terms-compact">
        <?php wc_get_template('checkout/terms.php'); ?>
    </div>

    <!-- ۳. روش پرداخت یکپارچه و ادغام‌شده با کارت بانکی -->
    <div class="gk-unified-payment-box">
        <div class="gk-payment-methods-title">
            <span class="gk-pm-icon">💳</span>
            <span>روش پرداخت:</span>
        </div>

        <div class="gk-unified-bacs-card">
            <!-- هدر روش پرداخت -->
            <div class="gk-bacs-header">
                <input type="radio" id="payment_method_bacs" class="input-radio" name="payment_method" value="bacs" checked="checked" data-order_button_text="<?php echo esc_attr($order_button_text); ?>" />
                <label for="payment_method_bacs" class="gk-bacs-label">
                    <span class="gk-bacs-title-text">کارت به کارت (انتقال مستقیم بانکی)</span>
                </label>
            </div>

            <!-- کارت بانکی ویزوال مستقیماً داخل روش پرداخت -->
            <div class="gk-bank-card-visual">
                <div class="gk-card-top-row">
                    <div class="gk-card-bank-info">
                        <span class="gk-card-bank-name"><?php echo esc_html($bank_name); ?></span>
                        <span class="gk-card-bank-sub"><?php echo esc_html($bank_sub); ?></span>
                    </div>
                    <div class="gk-card-chip">
                        <div class="gk-chip-line"></div>
                        <div class="gk-chip-line"></div>
                    </div>
                </div>

                <div class="gk-card-number-box">
                    <span class="gk-card-number" id="gkCardNumber"><?php echo esc_html($card_num); ?></span>
                    <button type="button" class="gk-btn-copy-card" id="gkCopyCardBtn" data-copy="<?php echo esc_attr($card_digits_clean); ?>">
                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                        <span id="gkCopyText">کپی</span>
                    </button>
                </div>

                <div class="gk-card-bottom-row">
                    <div>
                        <span class="gk-card-label">صاحب حساب:</span>
                        <span class="gk-card-holder"><?php echo esc_html($card_holder); ?></span>
                    </div>
                    <div class="gk-card-verified-badge">
                        <span>✓ حساب تجاری تأیید‌شده</span>
                    </div>
                </div>
            </div>

            <!-- راهنمای ۲ مرحله‌ای پرداخت همراه با لینک بله -->
            <div class="gk-payment-guide-box">
                <div class="gk-guide-step">
                    <span class="gk-step-badge">۱</span>
                    <span>مبلغ سفارش را از طریق همراه بانک یا اپلیکیشن‌های پرداخت به شماره کارت بالا انتقال دهید.</span>
                </div>
                <div class="gk-guide-step">
                    <span class="gk-step-badge">۲</span>
                    <div class="gk-guide-step-content">
                        <span>تصویر رسید یا شماره پیگیری را پس از ثبت سفارش در پیام‌رسان بله ارسال فرمایید:</span>
                        <a href="<?php echo esc_url($ble_url); ?>" target="_blank" rel="noopener noreferrer" class="gk-bale-inline-pill">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none">
                                <circle cx="12" cy="12" r="11" fill="#10b981"/>
                                <path d="M7 12.5C7 9.5 9.2 7 12 7C14.8 7 17 9.5 17 12.5C17 15.5 14.8 17.5 12 17.5C10.8 17.5 9.7 17 8.9 16.2L7 17L7.6 15.2C7.2 14.4 7 13.5 7 12.5Z" fill="#ffffff"/>
                            </svg>
                            <span>ارسال به پشتیبانی بله</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ۴. فوتر نمادهای امنیت و تضمین -->
    <div class="gk-checkout-trust-footer">
        <div class="gk-trust-pill">
            <span class="gk-trust-icon">⚡</span>
            <span>فعال‌سازی خودکار و آنی دسترسی پس از پرداخت</span>
        </div>
        <div class="gk-trust-pill">
            <span class="gk-trust-icon">🔒</span>
            <span>متصل به شبکه شاپرک و درگاه رسمی بانکی</span>
        </div>
    </div>

    <script data-no-optimize="1">
        document.addEventListener("DOMContentLoaded", function() {
            var copyBtn = document.getElementById("gkCopyCardBtn");
            if (copyBtn) {
                copyBtn.addEventListener("click", function(e) {
                    e.preventDefault();
                    var text = copyBtn.getAttribute("data-copy");
                    navigator.clipboard.writeText(text).then(function() {
                        var copyText = document.getElementById("gkCopyText");
                        if (copyText) {
                            copyText.textContent = "کپی شد! ✓";
                            setTimeout(function() { copyText.textContent = "کپی"; }, 2500);
                        }
                    });
                });
            }
        });
    </script>

</div>
<?php
if (!wp_doing_ajax()) {
    do_action('woocommerce_review_order_after_payment');
}