<?php
/**
 * WooCommerce Template Router
 */
get_header();
?>
<main class="gk-site-main">
    <div class="gk-container">
        <?php
        if (is_singular('product') || is_post_type_archive('product') || is_product_taxonomy()) {
            if (function_exists('woocommerce_content')) {
                woocommerce_content();
            }
        } else {
            while (have_posts()) {
                the_post();
                the_content();
            }
        }
        ?>
    </div>
</main>
<?php
get_footer();