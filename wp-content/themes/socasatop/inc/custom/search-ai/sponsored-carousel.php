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
      
      // Adicionar estilos específicos para o carrossel
      wp_add_inline_style('socasatop-style', '
        .sponsored-carousel-container {
            margin: 40px 0;
            position: relative;
        }
        .destaque-interna {
            padding: 30px 0;
            background-color: #f8f9fa;
        }
        .destaque-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        .destaque-title {
            font-size: 28px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }
        .imovel {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .imovel:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .imovel .thumb-wrapper {
            height: 200px;
            overflow: hidden;
        }
        .imovel img {
            object-fit: cover;
            width: 100%;
            height: 100%;
            transition: transform 0.3s ease;
        }
        .imovel:hover img {
            transform: scale(1.05);
        }
        .pagination-dot {
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .carousel-nav {
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        @media (max-width: 768px) {
            .destaque-title {
                font-size: 22px;
            }
            .carrosel-patrocinados {
                gap: 20px;
            }
        }
      ');
      
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


// Função para sincronizar o status de destaque entre tabela e meta
function sync_sponsored_properties_status() {
    global $wpdb;
    
    // 1. Buscar todos os imóveis com destacados na tabela broker_immobile
    $broker_immobile_table = $wpdb->prefix . 'broker_immobile';
    $sponsored_from_table = $wpdb->get_col("
        SELECT DISTINCT immobile_id 
        FROM {$broker_immobile_table} 
        WHERE is_sponsor = 1
    ");
    
    // 2. Buscar todos os imóveis com meta 'is_sponsored' = 'yes'
    $args = array(
        'post_type' => 'immobile',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => 'is_sponsored',
                'value' => 'yes',
                'compare' => '='
            )
        )
    );
    
    $query = new WP_Query($args);
    $sponsored_from_meta = array();
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $sponsored_from_meta[] = get_the_ID();
        }
    }
    
    wp_reset_postdata();
    
    // 3. Sincronizar: Adicionar meta para os que estão na tabela mas não na meta
    foreach ($sponsored_from_table as $immobile_id) {
        if (!in_array($immobile_id, $sponsored_from_meta)) {
            update_post_meta($immobile_id, 'is_sponsored', 'yes');
        }
    }
    
    // 4. Log de sincronização (opcional, para depuração)
    error_log('Sincronização de imóveis destacados: ' . count($sponsored_from_table) . ' na tabela, ' . count($sponsored_from_meta) . ' na meta');
}

// Executar sincronização diariamente ou em eventos específicos
add_action('admin_init', 'sync_sponsored_properties_status');
add_action('save_post_immobile', 'sync_sponsored_properties_status');




function get_sponsored_properties() {
    global $wpdb;
    $current_post_id = get_the_ID();
    
    // Buscar imóveis destacados de duas formas:
    // 1. Pela meta 'is_sponsored' = 'yes'
    // 2. Pela tabela broker_immobile onde is_sponsor = 1

    // Buscar IDs de imóveis associados a corretores destacados
    $broker_immobile_table = $wpdb->prefix . 'broker_immobile';
    $sponsored_ids_from_table = $wpdb->get_col("
        SELECT DISTINCT immobile_id 
        FROM {$broker_immobile_table} 
        WHERE is_sponsor = 1
    ");
    
    // Buscar imóveis com meta 'is_sponsored' = 'yes'
    $args = array(
        'post_type' => 'immobile',
        'post_status' => 'publish',
        'posts_per_page' => -1, // Buscar todos os imóveis destacados
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
    
    // Processar imóveis da meta query
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            
            // Evitar duplicatas
            if (in_array($post_id, $properties)) {
                continue;
            }
            
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
    
    // Processar imóveis da tabela broker_immobile
    if (!empty($sponsored_ids_from_table)) {
        $sponsored_ids_to_fetch = array_diff($sponsored_ids_from_table, wp_list_pluck($properties, 'id'));
        
        if (!empty($sponsored_ids_to_fetch)) {
            $args = array(
                'post_type' => 'immobile',
                'post_status' => 'publish',
                'post__in' => $sponsored_ids_to_fetch,
                'post__not_in' => array($current_post_id)
            );
            
            $query = new WP_Query($args);
            
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
        }
    }
    
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