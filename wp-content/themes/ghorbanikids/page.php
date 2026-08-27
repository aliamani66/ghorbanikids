<?php
get_header();
?>
<main class="gk-site-main">
    <div class="gk-container" style="padding: 30px 20px;">
        <?php
        while (have_posts()) {
            the_post();
            $raw_content = get_the_content();
            echo apply_filters('the_content', $raw_content);
        }
        ?>
    </div>
</main>
<?php
get_footer();