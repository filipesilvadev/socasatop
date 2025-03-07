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
    $table_name = $wpdb->prefix . 'sponsored_listings';
    
    // Verifica se a tabela existe
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
    error_log("Tabela $table_name existe? " . ($table_exists ? 'Sim' : 'Não'));
    
    if (!$table_exists) {
        error_log("Tabela de patrocinados não existe!");
        return [];
    }
    
    $query = "
        SELECT DISTINCT 
            p.ID,
            p.post_title,
            pm_location.meta_value as location,
            pm_amount.meta_value as amount,
            pm_gallery.meta_value as gallery_images
        FROM {$wpdb->posts} p
        INNER JOIN $table_name sl ON p.ID = sl.property_id
        LEFT JOIN {$wpdb->postmeta} pm_location ON p.ID = pm_location.post_id AND pm_location.meta_key = 'location'
        LEFT JOIN {$wpdb->postmeta} pm_amount ON p.ID = pm_amount.post_id AND pm_amount.meta_key = 'amount'
        LEFT JOIN {$wpdb->postmeta} pm_gallery ON p.ID = pm_gallery.post_id AND pm_gallery.meta_key = 'immobile_gallery'
        WHERE sl.status = 'active'
        AND sl.end_date >= CURDATE()
        AND p.post_type = 'immobile'
        AND p.post_status = 'publish'
        ORDER BY RAND()
        LIMIT 10";
        
    $results = $wpdb->get_results($query);
    error_log("Total de imóveis patrocinados: " . count($results));
    
    if ($wpdb->last_error) {
        error_log("Erro SQL: " . $wpdb->last_error);
        return [];
    }
    
    return array_map(function($post) {
        $gallery_ids = !empty($post->gallery_images) ? explode(',', $post->gallery_images) : [];
        $first_image_id = !empty($gallery_ids[0]) ? $gallery_ids[0] : 0;
        
        return [
            'id' => $post->ID,
            'title' => $post->post_title,
            'permalink' => get_permalink($post->ID),
            'thumbnail' => $first_image_id ? wp_get_attachment_url($first_image_id) : '',
            'location' => $post->location,
            'amount' => $post->amount
        ];
    }, $results);
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