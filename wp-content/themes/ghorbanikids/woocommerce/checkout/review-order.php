<?php
/**
 * GhorbaniKids Unified Checkout Review Order Template
 */

defined('ABSPATH') || exit;
?>

<div class="gk-checkout-review-wrap">
    <div class="gk-review-box-header">
        <div class="gk-review-box-title">
            <span class="gk-review-box-icon">📋</span>
            <h3>فاکتور و پرداخت سفارش</h3>
        </div>
        <span class="gk-review-count-badge"><?php echo esc_html(WC()->cart->get_cart_contents_count()); ?> محصول</span>
    </div>

    <!-- لیست محصولات -->
    <div class="gk-checkout-items-list">
        <?php
        do_action('woocommerce_review_order_before_cart_contents');

        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            $_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);

            if ($_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters('woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key)) {
                $thumbnail = apply_filters('woocommerce_cart_item_thumbnail', $_product->get_image('thumbnail'), $cart_item, $cart_item_key);
                $product_name = apply_filters('woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key);
                $product_subtotal = apply_filters('woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal($_product, $cart_item['quantity']), $cart_item, $cart_item_key);
                ?>
                <div class="gk-checkout-item-card">
                    <div class="gk-checkout-thumb-box">
                        <?php echo $thumbnail; ?>
                    </div>
                    <div class="gk-checkout-info-box">
                        <span class="gk-badge-item-vip">👑 اشتراک ویژه</span>
                        <h4 class="gk-checkout-item-title"><?php echo esc_html($product_name); ?></h4>
                    </div>
                    <div class="gk-checkout-price-box">
                        <span class="gk-checkout-price-val"><?php echo $product_subtotal; ?></span>
                    </div>
                </div>
                <?php
            }
        }

        do_action('woocommerce_review_order_after_cart_contents');
        ?>
    </div>

    <!-- ریز محاسبات فاکتور -->
    <div class="gk-checkout-breakdown">
        <div class="gk-breakdown-row">
            <span class="gk-row-label">جمع جزء:</span>
            <span class="gk-row-val"><?php wc_cart_totals_subtotal_html(); ?></span>
        </div>

        <?php foreach (WC()->cart->get_coupons() as $code => $coupon): ?>
            <div class="gk-breakdown-row gk-row-discount">
                <span class="gk-row-label">تخفیف (<?php echo esc_html($coupon->get_code()); ?>):</span>
                <span class="gk-row-val gk-discount-val"><?php wc_cart_totals_coupon_html($coupon); ?></span>
            </div>
        <?php endforeach; ?>

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

    <!-- بنر مبلغ کل نهایی -->
    <div class="gk-final-total-banner">
        <div class="gk-banner-label-wrap">
            <span class="gk-banner-main-label">مبلغ قابل پرداخت:</span>
            <span class="gk-banner-sub-label">با احتساب کلیه تخفیف‌ها</span>
        </div>
        <div class="gk-banner-price-val">
            <?php wc_cart_totals_order_total_html(); ?>
        </div>
    </div>
</div>