<?php
$locations = get_terms(array(
    'taxonomy' => 'locations',
    'hide_empty' => false,
));
$options = ['Sim', 'Não'];
$property_types = ['Sobrado', 'Térreo'];
?>
<div class="wrap">
    <div>
        <label for="name">Views:</label><br>
        <input type="text" name="views" id="views" value="<?php echo get_post_meta($post->ID, 'views', true); ?>" disabled>
    </div>
    <div>
        <label for="immobile_ids">Ids Imóveis:</label><br>
        <input type="text" name="immobile_ids" id="immobile_ids" value="<?php echo get_post_meta($post->ID, 'immobile_ids', true); ?>" disabled>
    </div>
</div>