<?php
$immobileIds = get_post_meta(get_the_ID(), 'immobile_ids', true);
$immobileIds = explode(',', $immobileIds);

$args = [
    'post_type' => 'immobile',
    'posts_per_page' => -1,
    'post__in' => $immobileIds
];

$immobile = new WP_Query($args);
?>
<?php if ($immobile->have_posts()) : ?>
    <div class="grid-immobile">
        <?php
        while ($immobile->have_posts()) {
            $immobile->the_post();
            echo do_shortcode('[elementor-template id="1435"]');
        }
        ?>
    </div>
<?php endif; ?>
<?php wp_reset_postdata(); ?>
<script>
    jQuery(document).ready(function($) {
        $(".total-immobile span").text("<?php echo $immobile->found_posts; ?>");
    });
</script>