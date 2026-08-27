<?php
/**
 * GhorbaniKids Ultra-Luxury Checkout Form Template Override (Pixel-Perfect 2-Column Layout)
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_checkout_form', $checkout);

if (!$checkout->is_registration_enabled() && $checkout->is_registration_required() && !is_user_logged_in()) {
    echo esc_html(apply_filters('woocommerce_checkout_must_be_logged_in_message', __('You must be logged in to checkout.', 'woocommerce')));
    return;
}
?>

<form name="checkout" method="post" class="checkout woocommerce-checkout gk-checkout-form-grid" action="<?php echo esc_url(wc_get_checkout_url()); ?>" enctype="multipart/form-data" aria-label="<?php echo esc_attr__('Checkout', 'woocommerce'); ?>">

    <!-- ستون اول: مشخصات مشتری و کد تخفیف و شماره کارت -->
    <div class="gk-checkout-col-customer">
        <?php if ($checkout->get_checkout_fields()): ?>
            <?php do_action('woocommerce_checkout_before_customer_details'); ?>

            <div class="gk-customer-details-wrap" id="customer_details">
                <div class="gk-billing-fields-wrap">
                    <?php do_action('woocommerce_checkout_billing'); ?>
                </div>

                <?php if (WC()->cart && WC()->cart->needs_shipping_address()): ?>
                    <div class="gk-shipping-fields-wrap">
                        <?php do_action('woocommerce_checkout_shipping'); ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php do_action('woocommerce_checkout_after_customer_details'); ?>
        <?php endif; ?>
    </div>

    <!-- ستون دوم: خلاصه فاکتور، مجموع و دکمه پرداخت -->
    <div class="gk-checkout-col-summary">
        <?php do_action('woocommerce_checkout_before_order_review_heading'); ?>
        <?php do_action('woocommerce_checkout_before_order_review'); ?>

        <div id="order_review" class="woocommerce-checkout-review-order">
            <?php do_action('woocommerce_checkout_order_review'); ?>
        </div>

        <?php do_action('woocommerce_checkout_after_order_review'); ?>
    </div>

</form>

<?php do_action('woocommerce_after_checkout_form', $checkout); ?>