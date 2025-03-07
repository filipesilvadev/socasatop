<?php
function display_broker_first_name() {
    $user = wp_get_current_user();
    return $user->first_name ?: $user->display_name;
}
add_shortcode('user_first_name', 'display_broker_first_name');

function display_total_imoveis() {
    global $wpdb;
    $user_id = get_current_user_id();
    
    $total = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT i.ID) 
        FROM {$wpdb->posts} i
        JOIN {$wpdb->postmeta} pm ON i.ID = pm.post_id
        WHERE i.post_type = 'immobile'
        AND i.post_status = 'publish'
        AND pm.meta_key = 'broker'
        AND pm.meta_value = %d
    ", $user_id));
    
    return $total ?: '0';
}
add_shortcode('total_imoveis', 'display_total_imoveis');

function display_imoveis_destaque() {
    global $wpdb;
    $user_id = get_current_user_id();
    
    $total = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT i.ID)
        FROM {$wpdb->posts} i
        JOIN {$wpdb->postmeta} pm ON i.ID = pm.post_id
        JOIN {$wpdb->postmeta} pm2 ON i.ID = pm2.post_id
        WHERE i.post_type = 'immobile'
        AND i.post_status = 'publish'
        AND pm.meta_key = 'broker'
        AND pm.meta_value = %d
        AND pm2.meta_key = 'is_sponsored'
        AND pm2.meta_value = 'yes'
    ", $user_id));
    
    return $total ?: '0';
}
add_shortcode('imoveis_destaque', 'display_imoveis_destaque');

function display_total_views() {
    global $wpdb;
    $user_id = get_current_user_id();
    
    $total = $wpdb->get_var($wpdb->prepare("
        SELECT COALESCE(SUM(CAST(vm.meta_value AS UNSIGNED)), 0)
        FROM {$wpdb->posts} i
        JOIN {$wpdb->postmeta} pm ON i.ID = pm.post_id
        JOIN {$wpdb->postmeta} vm ON i.ID = vm.post_id
        WHERE i.post_type = 'immobile'
        AND i.post_status = 'publish'
        AND pm.meta_key = 'broker'
        AND pm.meta_value = %d
        AND vm.meta_key = 'total_views'
    ", $user_id));
    
    return $total;
}
add_shortcode('total_views', 'display_total_views');

function display_total_clicks() {
    global $wpdb;
    $user_id = get_current_user_id();
    
    $total = $wpdb->get_var($wpdb->prepare("
        SELECT COALESCE(SUM(CAST(cm.meta_value AS UNSIGNED)), 0)
        FROM {$wpdb->posts} i
        JOIN {$wpdb->postmeta} pm ON i.ID = pm.post_id
        JOIN {$wpdb->postmeta} cm ON i.ID = cm.post_id
        WHERE i.post_type = 'immobile'
        AND i.post_status = 'publish'
        AND pm.meta_key = 'broker'
        AND pm.meta_value = %d
        AND cm.meta_key = 'total_clicks'
    ", $user_id));
    
    return $total;
}
add_shortcode('total_clicks', 'display_total_clicks');

function display_total_conversions() {
    global $wpdb;
    $user_id = get_current_user_id();
    
    $total = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT l.ID)
        FROM {$wpdb->posts} l
        JOIN {$wpdb->postmeta} pm ON l.ID = pm.post_id
        WHERE l.post_type = 'lead'
        AND l.post_status = 'publish'
        AND pm.meta_key = 'broker_id'
        AND pm.meta_value = %d
    ", $user_id));
    
    return $total;
}
add_shortcode('total_conversions', 'display_total_conversions');