<?php
/**
 * Template Name: Games Catalog Page
 */

get_header();
?>
<main class="gk-site-main">
    <div class="gk-container" style="padding: 30px 20px;">
        <?php
        echo do_shortcode('[ghorbanikids_catalog]');
        ?>
    </div>
</main>
<?php
get_footer();