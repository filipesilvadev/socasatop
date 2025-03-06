<?php

function should_record_metrics() {
    if (!is_user_logged_in()) {
        return true;
    }
    
    $user = wp_get_current_user();
    $excluded_roles = array('administrator', 'author');
    
    return !array_intersect($excluded_roles, $user->roles);
}

// Registro de exibição na listagem
function register_immobile_view($post_id) {
    if (!should_record_metrics()) {
        return;
    }

    $date = date('Y-m-d');
    $views = (int)get_post_meta($post_id, "metrics_views_{$date}", true);
    update_post_meta($post_id, "metrics_views_{$date}", $views + 1);
    
    $total_views = (int)get_post_meta($post_id, 'total_views', true);
    update_post_meta($post_id, 'total_views', $total_views + 1);
    
    $broker_id = get_post_meta($post_id, 'broker', true);
    if ($broker_id) {
        $broker_views = (int)get_user_meta($broker_id, "metrics_views_{$date}", true);
        update_user_meta($broker_id, "metrics_views_{$date}", $broker_views + 1);
    }
}

// Registro de acesso ao imóvel
function register_immobile_click($post_id) {
    if (!should_record_metrics()) {
        return;
    }

    $date = date('Y-m-d');
    $clicks = (int)get_post_meta($post_id, "metrics_clicks_{$date}", true);
    update_post_meta($post_id, "metrics_clicks_{$date}", $clicks + 1);
    
    $total_clicks = (int)get_post_meta($post_id, 'total_clicks', true);
    update_post_meta($post_id, 'total_clicks', $total_clicks + 1);
    
    $broker_id = get_post_meta($post_id, 'broker', true);
    if ($broker_id) {
        $broker_clicks = (int)get_user_meta($broker_id, "metrics_clicks_{$date}", true);
        update_user_meta($broker_id, "metrics_clicks_{$date}", $broker_clicks + 1);
    }
}

// Hook para registrar visualização quando o imóvel aparecer em listagens
function register_view_in_loop($query) {
  if (!$query->is_main_query()) {
      return;
  }

  if (!should_record_metrics()) {
      return;
  }

  if ($query->is_post_type_archive('immobile') || $query->is_tax('location') || is_page('so-casa-top-ia')) {
      while ($query->have_posts()) {
          $query->the_post();
          $post_id = get_the_ID();
          register_immobile_view($post_id);
      }
      wp_reset_postdata();
  }
}
add_action('pre_get_posts', 'register_view_in_loop');

// Hook para registrar acesso quando visualizar página do imóvel
function track_immobile_click() {
    if (is_singular('immobile')) {
        $post_id = get_queried_object_id();
        register_immobile_click($post_id);
    }
}
add_action('wp', 'track_immobile_click');