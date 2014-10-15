<?php
/**
 * Rename this template file to single-initiative_profile.php for it to take effect
 *
 * The Template for displaying all single initiaitve_profile
 *
 * @package IIRS
 * @since IIRS 1.0
 */

get_header(); ?>

        <div id="primary" class="content-area">
                <div id="content" class="site-content" role="main">
                yea
                        <?php
                                // Start the Loop.
                                while ( have_posts() ) : the_post();

                                        /*
                                         * Include the post format-specific template for the content. If you want to
                                         * use this in a child theme, then include a file called called content-___.php
                                         * (where ___ is the post format) and that will be used instead.
                                         */
                                        get_template_part( 'content', get_post_format() );

                                        // If comments are open or we have at least one comment, load up the comment template.
                                        if ( comments_open() || get_comments_number() ) {
                                                comments_template();
                                        }
                                endwhile;
                        ?>
                </div><!-- #content -->
        </div><!-- #primary -->

<?php
get_sidebar( 'content' );
get_sidebar();
get_footer();
