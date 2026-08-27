<?php
/**
 * Order details (100% Clean, Responsive & Contained for GhorbaniKids)
 */
defined( 'ABSPATH' ) || exit;

$order = wc_get_order( $order_id );
if ( ! $order ) {
    return;
}

$order_items        = $order->get_items( apply_filters( 'woocommerce_purchase_order_item_types', 'line_item' ) );
$downloads          = $order->get_downloadable_items();
$show_downloads     = $order->has_downloadable_item() && $order->is_download_permitted();

if ( $show_downloads ) {
    wc_get_template(
        'order/order-downloads.php',
        array(
            'downloads'  => $downloads,
            'show_title' => true,
        )
    );
}
?>
<section class="woocommerce-order-details gk-order-details-card">
    <h2 class="woocommerce-order-details__title">مشخصات سفارش</h2>

    <!-- اقلام سفارش -->
    <div class="gk-order-items-list">
        <?php
        foreach ( $order_items as $item_id => $item ) {
            ?>
            <div class="gk-order-item-row">
                <div class="gk-order-item-info">
                    <span class="gk-order-item-name"><?php echo esc_html( $item->get_name() ); ?></span>
                    <span class="gk-order-item-qty">× <?php echo esc_html( $item->get_quantity() ); ?></span>
                </div>
                <div class="gk-order-item-price">
                    <?php echo wp_kses_post( $order->get_formatted_line_subtotal( $item ) ); ?>
                </div>
            </div>
            <?php
        }
        ?>
    </div>

    <!-- مبالغ، روش پرداخت و قیمت نهایی -->
    <div class="gk-order-totals-list">
        <?php
        foreach ( $order->get_order_item_totals() as $key => $total ) {
            $is_final = ( $key === 'order_total' );
            ?>
            <div class="gk-order-total-row <?php echo $is_final ? 'is-final-total' : ''; ?>">
                <span class="gk-total-label"><?php echo esc_html( $total['label'] ); ?></span>
                <span class="gk-total-value"><?php echo wp_kses_post( $total['value'] ); ?></span>
            </div>
            <?php
        }
        ?>
    </div>
</section>