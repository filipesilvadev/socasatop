<?php get_header(); ?>

<div class="container">
    <?php 
    while (have_posts()) : 
        the_post();
        echo do_shortcode('[immobile_profile]');
    endwhile; 
    ?>
</div>

<?php get_footer(); ?>