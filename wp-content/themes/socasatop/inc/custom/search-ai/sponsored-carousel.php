<?php
if (!defined('ABSPATH')) {
    exit;
}



function enqueue_sponsored_carousel_scripts() {
  if (has_shortcode(get_the_content(), 'sponsored_carousel')) {
      wp_enqueue_script('react', 'https://unpkg.com/react@18.2.0/umd/react.production.min.js', [], '18.2.0', true);
      wp_enqueue_script('react-dom', 'https://unpkg.com/react-dom@18.2.0/umd/react-dom.production.min.js', ['react'], '18.2.0', true);
      
      wp_enqueue_script(
          'sponsored-carousel', 
          get_stylesheet_directory_uri() . '/inc/custom/search-ai/assets/js/sponsored-carousel.js', 
          ['react', 'react-dom'], 
          time(), 
          true
      );
      
      // Localize script com dados dos imóveis patrocinados
      $properties = get_sponsored_properties();
      wp_localize_script('sponsored-carousel', 'sponsoredCarouselConfig', [
          'apiUrl' => rest_url('smart-search/v1/'),
          'nonce' => wp_create_nonce('wp_rest'),
          'properties' => $properties
      ]);
  }
}
add_action('wp_enqueue_scripts', 'enqueue_sponsored_carousel_scripts');

function sponsored_carousel_shortcode() {
  return '<div id="sponsored-carousel-root" class="sponsored-carousel-container"></div>';
}
add_shortcode('sponsored_carousel', 'sponsored_carousel_shortcode');







function get_sponsored_properties() {
    global $wpdb;
    $current_post_id = get_the_ID();
    
    // Buscar imóveis com meta 'is_sponsored' = 'yes'
    $args = array(
        'post_type' => 'immobile',
        'post_status' => 'publish',
        'posts_per_page' => 10,
        'meta_query' => array(
            array(
                'key' => 'is_sponsored',
                'value' => 'yes',
                'compare' => '='
            )
        ),
        'post__not_in' => array($current_post_id) // Excluir o imóvel atual
    );
    
    $query = new WP_Query($args);
    $properties = array();
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            
            $gallery = get_post_meta($post_id, 'immobile_gallery', true);
            $gallery_ids = !empty($gallery) ? explode(',', $gallery) : array();
            $first_image_id = !empty($gallery_ids[0]) ? $gallery_ids[0] : 0;
            
            $properties[] = array(
                'id' => $post_id,
                'title' => get_the_title(),
                'permalink' => get_permalink(),
                'thumbnail' => $first_image_id ? wp_get_attachment_url($first_image_id) : '',
                'location' => get_post_meta($post_id, 'location', true),
                'amount' => get_post_meta($post_id, 'amount', true)
            );
        }
    }
    
    wp_reset_postdata();
    
    return $properties;
}



// Registrar shortcode
add_shortcode('sponsored_carousel', 'sponsored_carousel_shortcode');

// Registrar endpoint da API
add_action('rest_api_init', function() {
    register_rest_route('smart-search/v1', '/sponsored-properties', [
        'methods' => 'GET',
        'callback' => function() {
            return new WP_REST_Response(get_sponsored_properties(), 200);
        },
        'permission_callback' => '__return_true'
    ]);
});