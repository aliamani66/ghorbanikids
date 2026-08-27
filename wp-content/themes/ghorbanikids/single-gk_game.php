<?php
get_header();
?>
<main class="gk-site-main">
    <div class="gk-container" style="padding: 20px;">
        <?php
        while (have_posts()) {
            the_post();
            the_content();
        }
        ?>
    </div>
</main>
<?php
get_footer();