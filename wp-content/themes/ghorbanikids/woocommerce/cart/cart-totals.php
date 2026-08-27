<?php
/**
 * GhorbaniKids Ultra-Luxury Cart Totals Template Override
 */

defined('ABSPATH') || exit;

?>
<div class="cart_totals <?php echo (WC()->customer->has_calculated_shipping()) ? 'calculated_shipping' : ''; ?>">

    <?php do_action('woocommerce_before_cart_totals'); ?>

    <div class="gk-totals-card-header">
        <div class="gk-totals-title-wrap">
            <span class="gk-totals-icon">📋</span>
            <h3>فاکتور و مجموع سفارش</h3>
        </div>
    </div>

    <div class="gk-totals-breakdown">
        <!-- Subtotal Row -->
        <div class="gk-breakdown-row">
            <span class="gk-row-label">جمع جزء محصولات:</span>
            <span class="gk-row-val"><?php wc_cart_totals_subtotal_html(); ?></span>
        </div>

        <!-- Coupons Rows -->
        <?php foreach (WC()->cart->get_coupons() as $code => $coupon): ?>
            <div class="gk-breakdown-row gk-row-discount">
                <span class="gk-row-label">تخفیف (<?php echo esc_html($coupon->get_code()); ?>):</span>
                <span class="gk-row-val gk-discount-val"><?php wc_cart_totals_coupon_html($coupon); ?></span>
            </div>
        <?php endforeach; ?>

        <!-- Fees if any -->
        <?php foreach (WC()->cart->get_fees() as $fee): ?>
            <div class="gk-breakdown-row">
                <span class="gk-row-label"><?php echo esc_html($fee->name); ?>:</span>
                <span class="gk-row-val"><?php wc_cart_totals_fee_html($fee); ?></span>
            </div>
        <?php endforeach; ?>

        <div class="gk-breakdown-row gk-row-instant">
            <span class="gk-row-label">هزینه فعال‌سازی آنی:</span>
            <span class="gk-row-val gk-text-free">رایگان 🎉</span>
        </div>
    </div>

    <!-- Final Payable Total Banner -->
    <div class="gk-final-total-banner">
        <div class="gk-banner-label-wrap">
            <span class="gk-banner-main-label">مبلغ قابل پرداخت:</span>
            <span class="gk-banner-sub-label">با احتساب کلیه تخفیف‌ها</span>
        </div>
        <div class="gk-banner-price-val">
            <?php wc_cart_totals_order_total_html(); ?>
        </div>
    </div>

    <!-- Proceed to Checkout Button -->
    <div class="wc-proceed-to-checkout">
        <?php do_action('woocommerce_proceed_to_checkout'); ?>
    </div>

    <!-- Trust & Security Badges -->
    <div class="gk-cart-trust-footer">
        <div class="gk-trust-pill">
            <span class="gk-trust-icon">⚡</span>
            <span>فعال‌سازی خودکار و آنی دسترسی</span>
        </div>
        <div class="gk-trust-pill">
            <span class="gk-trust-icon">🔒</span>
            <span>پرداخت امن و تضمین‌شده</span>
        </div>
    </div>

    <?php do_action('woocommerce_after_cart_totals'); ?>

</div>