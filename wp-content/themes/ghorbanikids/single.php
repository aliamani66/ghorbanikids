<?php
get_header();
?>
<main class="gk-site-main">
    <div class="gk-container" style="max-width: 900px; padding: 40px 20px;">
        <?php
        while (have_posts()) {
            the_post();
            ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <h1 style="font-size: 2rem; font-weight: 900; margin-bottom: 20px; color: #0f172a;"><?php the_title(); ?></h1>
                <?php if (has_post_thumbnail()): ?>
                    <div style="margin-bottom: 30px; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.08);">
                        <?php the_post_thumbnail('large', ['style' => 'width: 100%; height: auto; display: block;']); ?>
                    </div>
                <?php endif; ?>
                <div class="entry-content" style="line-height: 2; font-size: 1.1rem; color: #334155;">
                    <?php the_content(); ?>
                </div>
            </article>
            <?php
        }
        ?>
    </div>
</main>
<?php
get_footer();