<?php
get_header();
?>
<main class="gk-site-main">
    <div class="gk-container" style="padding: 40px 20px;">
        <?php
        if (have_posts()) {
            while (have_posts()) {
                the_post();
                ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class(); ?> style="margin-bottom: 30px;">
                    <h1 style="font-size: 1.8rem; font-weight: 800;"><?php the_title(); ?></h1>
                    <div class="entry-content" style="line-height: 1.9; font-size: 1.05rem;">
                        <?php the_content(); ?>
                    </div>
                </article>
                <?php
            }
        }
        ?>
    </div>
</main>
<?php
get_footer();