<?php
/**
 * GhorbaniKids Ultra-Luxury Modern Cart Template Override (Pixel-Perfect 2-Column Grid)
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_cart'); ?>

<div class="gk-cart-main-container">
    <!-- ستون اول: لیست اقلام سبد خرید -->
    <div class="gk-cart-items-column">
        <form class="woocommerce-cart-form" action="<?php echo esc_url(wc_get_cart_url()); ?>" method="post">
            <?php do_action('woocommerce_before_cart_table'); ?>

            <div class="gk-cart-box-header">
                <div class="gk-cart-box-title">
                    <span class="gk-cart-box-icon">🛒</span>
                    <h3>اقلام سبد خرید</h3>
                </div>
                <span class="gk-cart-count-badge"><?php echo esc_html(WC()->cart->get_cart_contents_count()); ?> محصول</span>
            </div>

            <div class="gk-cart-items-list">
                <?php
                foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                    $_product   = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
                    $product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);

                    if ($_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters('woocommerce_cart_item_visible', true, $cart_item, $cart_item_key)) {
                        $product_permalink = apply_filters('woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink($cart_item) : '', $cart_item, $cart_item_key);
                        $product_name = apply_filters('woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key);
                        $thumbnail = apply_filters('woocommerce_cart_item_thumbnail', $_product->get_image('thumbnail'), $cart_item, $cart_item_key);
                        $product_price = apply_filters('woocommerce_cart_item_price', WC()->cart->get_product_price($_product), $cart_item, $cart_item_key);
                        $product_subtotal = apply_filters('woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal($_product, $cart_item['quantity']), $cart_item, $cart_item_key);
                        $remove_url = wc_get_cart_remove_url($cart_item_key);
                        ?>
                        <div class="gk-cart-item-card <?php echo esc_attr(apply_filters('woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key)); ?>">
                            <!-- تصویر محصول -->
                            <div class="gk-item-thumb-box">
                                <?php if ($product_permalink): ?>
                                    <a href="<?php echo esc_url($product_permalink); ?>"><?php echo $thumbnail; ?></a>
                                <?php else: ?>
                                    <?php echo $thumbnail; ?>
                                <?php endif; ?>
                            </div>

                            <!-- مشخصات و نام محصول -->
                            <div class="gk-item-info-box">
                                <div class="gk-item-badges-row">
                                    <span class="gk-badge-item-vip">👑 اشتراک ویژه VIP</span>
                                    <?php if ($cart_item['quantity'] > 1): ?>
                                        <span class="gk-badge-item-qty">تعداد: <?php echo esc_html($cart_item['quantity']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <h4 class="gk-item-title">
                                    <?php if ($product_permalink): ?>
                                        <a href="<?php echo esc_url($product_permalink); ?>"><?php echo esc_html($product_name); ?></a>
                                    <?php else: ?>
                                        <?php echo esc_html($product_name); ?>
                                    <?php endif; ?>
                                </h4>
                            </div>

                            <!-- قیمت و دکمه حذف -->
                            <div class="gk-item-actions-box">
                                <div class="gk-item-price-wrap">
                                    <span class="gk-item-price-label">مبلغ:</span>
                                    <span class="gk-item-price-val"><?php echo $product_subtotal; ?></span>
                                </div>
                                <a href="<?php echo esc_url($remove_url); ?>" class="gk-btn-remove-item" aria-label="<?php esc_attr_e('Remove this item', 'woocommerce'); ?>" data-product_id="<?php echo esc_attr($product_id); ?>" data-product_sku="<?php echo esc_attr($_product->get_sku()); ?>" title="حذف از سبد خرید">
                                    ✕
                                </a>
                            </div>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>

            <!-- بخش کد تخفیف در انتهای کارت اقلام -->
            <div class="gk-cart-bottom-bar">
                <?php if (wc_coupons_enabled()): ?>
                    <div class="gk-coupon-inline-group">
                        <input type="text" name="coupon_code" class="gk-coupon-input-field" id="coupon_code" value="" placeholder="کد تخفیف دارید؟" />
                        <button type="submit" class="gk-btn-apply-coupon-action" name="apply_coupon" value="<?php esc_attr_e('Apply coupon', 'woocommerce'); ?>">
                            اعمال کد
                        </button>
                        <?php do_action('woocommerce_cart_coupon'); ?>
                    </div>
                <?php endif; ?>

                <div class="gk-cart-update-action-wrap">
                    <button type="submit" class="gk-btn-update-cart-action" name="update_cart" value="<?php esc_attr_e('Update cart', 'woocommerce'); ?>" style="display:none;">
                        بروزرسانی سبد خرید
                    </button>
                    <?php do_action('woocommerce_cart_actions'); ?>
                    <?php wp_nonce_field('woocommerce-cart', 'woocommerce-cart-nonce'); ?>
                </div>
            </div>

            <?php do_action('woocommerce_after_cart_table'); ?>
        </form>
    </div>

    <!-- ستون دوم: فاکتور نهایی و مجموع سفارش -->
    <div class="gk-cart-sidebar-column">
        <?php do_action('woocommerce_before_cart_collaterals'); ?>

        <div class="cart-collaterals">
            <?php
            /**
             * Cart collaterals hook.
             *
             * @hooked woocommerce_cross_sell_display
             * @hooked woocommerce_cart_totals - 10
             */
            do_action('woocommerce_cart_collaterals');
            ?>
        </div>
    </div>
</div>

<?php do_action('woocommerce_after_cart'); ?>