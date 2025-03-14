<?php
function get_broker_global_metrics() {
    check_ajax_referer('ajax_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Usuário não autenticado');
    }

    $user_id = get_current_user_id();
    global $wpdb;
    $last_30_days = array();
    
    for ($i = 0; $i < 30; $i++) {
        $date = date('Y-m-d', strtotime("-$i days"));
        
        $metrics = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COALESCE(SUM(CAST(vm.meta_value AS UNSIGNED)), 0) as views,
                COALESCE(SUM(CAST(cm.meta_value AS UNSIGNED)), 0) as clicks
            FROM {$wpdb->posts} i
            JOIN {$wpdb->postmeta} pm ON i.ID = pm.post_id
            LEFT JOIN {$wpdb->postmeta} vm ON i.ID = vm.post_id AND vm.meta_key = 'total_views'
            LEFT JOIN {$wpdb->postmeta} cm ON i.ID = cm.post_id AND cm.meta_key = 'total_clicks'
            WHERE i.post_type = 'immobile'
            AND i.post_status = 'publish'
            AND pm.meta_key = 'broker'
            AND pm.meta_value = %d
            AND DATE(i.post_date) = %s
        ", $user_id, $date));

        $conversions = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT l.ID)
            FROM {$wpdb->posts} l
            JOIN {$wpdb->postmeta} pm ON l.ID = pm.post_id
            WHERE l.post_type = 'lead'
            AND l.post_status = 'publish'
            AND pm.meta_key = 'broker_id'
            AND pm.meta_value = %d
            AND DATE(l.post_date) = %s
        ", $user_id, $date));

        $last_30_days[] = array(
            'date' => $date,
            'views' => (int)$metrics->views,
            'clicks' => (int)$metrics->clicks,
            'conversions' => (int)$conversions
        );
    }

    wp_send_json_success(array('metrics' => array_reverse($last_30_days)));
}
add_action('wp_ajax_get_broker_metrics', 'get_broker_global_metrics');

function get_broker_immobile_list() {
    check_ajax_referer('ajax_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Usuário não autenticado');
    }

    $user_id = get_current_user_id();
    global $wpdb;
    
    $properties = $wpdb->get_results($wpdb->prepare("
        SELECT 
            i.ID as id,
            i.post_title as title,
            i.post_status as status,
            COALESCE(vm.meta_value, '0') as views,
            COALESCE(cm.meta_value, '0') as clicks,
            COALESCE(sp.meta_value, 'no') as sponsored
        FROM {$wpdb->posts} i
        JOIN {$wpdb->postmeta} pm ON i.ID = pm.post_id
        LEFT JOIN {$wpdb->postmeta} vm ON i.ID = vm.post_id AND vm.meta_key = 'total_views'
        LEFT JOIN {$wpdb->postmeta} cm ON i.ID = cm.post_id AND cm.meta_key = 'total_clicks'
        LEFT JOIN {$wpdb->postmeta} sp ON i.ID = sp.post_id AND sp.meta_key = 'is_sponsored'
        WHERE i.post_type = 'immobile'
        AND (i.post_status = 'publish' OR i.post_status = 'draft')
        AND pm.meta_key = 'broker'
        AND pm.meta_value = %d
    ", $user_id));

    $formatted_properties = array_map(function($prop) {
        $conversions = get_post_meta($prop->id, 'total_conversions', true);
        return array(
            'id' => $prop->id,
            'title' => $prop->title,
            'status' => $prop->status,
            'views' => (int)$prop->views,
            'clicks' => (int)$prop->clicks,
            'conversions' => (int)$conversions,
            'sponsored' => $prop->sponsored === 'yes'
        );
    }, $properties);

    wp_send_json_success(array('properties' => $formatted_properties));
}
add_action('wp_ajax_get_broker_properties', 'get_broker_immobile_list');