<?php
function display_broker_dashboard() {
    global $wp;
    $current_url = home_url($wp->request);
    
    if (!is_user_logged_in()) {
        if (wp_get_referer() && strpos(wp_get_referer(), 'novos-corretores') !== false) {
            return '<div class="loading-dashboard">Carregando seu dashboard...</div>';
        }
        return 'Você precisa estar logado para acessar esta página.';
    }

    $user = wp_get_current_user();
    if (!in_array('author', (array) $user->roles)) {
        return 'Acesso restrito a corretores.';
    }



  // Carregamento explícito dos scripts na ordem correta
  wp_deregister_script('chart-js');
  wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js', array(), '4.4.1', false);
  
  wp_deregister_script('react');
  wp_enqueue_script('react', 'https://unpkg.com/react@17/umd/react.production.min.js', array('chart-js'), '17.0', true);
  
  wp_deregister_script('react-dom');
  wp_enqueue_script('react-dom', 'https://unpkg.com/react-dom@17/umd/react-dom.production.min.js', array('react'), '17.0', true);
  
  wp_enqueue_style('tailwindcss', 'https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css', array(), '2.2.19');

  wp_register_script(
      'broker-dashboard',
      get_stylesheet_directory_uri() . '/inc/custom/broker/assets/js/broker-dashboard.js',
      array('react', 'react-dom', 'chart-js'),
      time(),
      true
  );

  wp_localize_script('broker-dashboard', 'site', array(
      'ajax_url' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('ajax_nonce')
  ));

  wp_enqueue_script('broker-dashboard');

  return '<div id="broker-dashboard-root" class="bg-gray-100 min-h-screen py-8"></div>';
}
add_shortcode('broker_dashboard', 'display_broker_dashboard');





function get_broker_metrics() {
    check_ajax_referer('ajax_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Usuário não autenticado');
    }

    $user_id = get_current_user_id();
    $last_30_days = array();
    
    for ($i = 0; $i < 30; $i++) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $views = get_user_meta($user_id, "metrics_views_{$date}", true) ?: 0;
        $clicks = get_user_meta($user_id, "metrics_clicks_{$date}", true) ?: 0;
        $conversions = get_user_meta($user_id, "metrics_conversions_{$date}", true) ?: 0;

        $last_30_days[] = array(
            'date' => $date,
            'views' => (int)$views,
            'clicks' => (int)$clicks,
            'conversions' => (int)$conversions
        );
    }

    wp_send_json_success(array('metrics' => array_reverse($last_30_days)));
}
add_action('wp_ajax_get_broker_metrics', 'get_broker_metrics');

function get_broker_properties() {
    check_ajax_referer('ajax_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Usuário não autenticado');
    }

    $user_id = get_current_user_id();
    
    $args = array(
        'post_type' => 'immobile',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => 'broker',
                'value' => $user_id
            )
        )
    );

    $query = new WP_Query($args);
    $properties = array();

    while ($query->have_posts()) {
        $query->the_post();
        $post_id = get_the_ID();
        
        $properties[] = array(
            'id' => $post_id,
            'title' => get_the_title(),
            'views' => (int)get_post_meta($post_id, 'total_views', true) ?: 0,
            'clicks' => (int)get_post_meta($post_id, 'total_clicks', true) ?: 0,
            'conversions' => (int)get_post_meta($post_id, 'total_conversions', true) ?: 0,
            'sponsored' => get_post_meta($post_id, 'is_sponsored', true) === 'yes'
        );
    }

    wp_reset_postdata();
    wp_send_json_success(array('properties' => $properties));
}
add_action('wp_ajax_get_broker_properties', 'get_broker_properties');