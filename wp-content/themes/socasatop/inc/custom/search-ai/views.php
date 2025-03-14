<?php

function register_search_view($properties) {
  if (!should_record_metrics()) {
      return $properties;
  }

  $date = date('Y-m-d');

  foreach ($properties as $property) {
      $views = (int)get_post_meta($property['id'], "metrics_views_{$date}", true);
      update_post_meta($property['id'], "metrics_views_{$date}", $views + 1);
      
      $total_views = (int)get_post_meta($property['id'], 'total_views', true);
      update_post_meta($property['id'], 'total_views', $total_views + 1);
      
      $broker_id = get_post_meta($property['id'], 'broker', true);
      if ($broker_id) {
          $broker_views = (int)get_user_meta($broker_id, "metrics_views_{$date}", true);
          update_user_meta($broker_id, "metrics_views_{$date}", $broker_views + 1);
      }
  }

  return $properties;
}