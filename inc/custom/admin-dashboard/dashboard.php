<?php
require_once 'debug-logger.php';
require_once 'debug-search.php';

function display_admin_dashboard() {
    if (!current_user_can('administrator')) {
        return 'Acesso restrito a administradores.';
    }

    wp_deregister_script('chart-js');
    wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js', array(), '4.4.1', false);
    
    wp_deregister_script('react');
    wp_enqueue_script('react', 'https://unpkg.com/react@17/umd/react.production.min.js', array('chart-js'), '17.0', true);
    
    wp_deregister_script('react-dom');
    wp_enqueue_script('react-dom', 'https://unpkg.com/react-dom@17/umd/react-dom.production.min.js', array('react'), '17.0', true);
    
    wp_enqueue_style('tailwindcss', 'https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css', array(), '2.2.19');

    wp_register_script(
        'admin-dashboard',
        get_stylesheet_directory_uri() . '/inc/custom/admin-dashboard/assets/js/admin-dashboard.js',
        array('react', 'react-dom', 'chart-js'),
        time(),
        true
    );

    wp_localize_script('admin-dashboard', 'siteAdmin', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ajax_nonce')
    ));

    wp_enqueue_script('admin-dashboard');

    return '<div id="admin-dashboard-root" class="bg-gray-100 min-h-screen py-8"></div>';
}
add_shortcode('admin_dashboard', 'display_admin_dashboard');

function get_admin_metrics_data() {
    global $wpdb, $admin_logger;
    
    check_ajax_referer('ajax_nonce', 'nonce');
    
    if (!current_user_can('administrator')) {
        $admin_logger->log('Acesso negado: usuário não é administrador', 'error');
        wp_send_json_error('Usuário não autorizado');
    }

    $broker_id = isset($_POST['broker_id']) ? intval($_POST['broker_id']) : 0;
    $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : date('Y-m-d', strtotime('-30 days'));
    $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : date('Y-m-d');

    $admin_logger->log("Filtros aplicados: broker_id={$broker_id}, start_date={$start_date}, end_date={$end_date}");

    $broker_query = $broker_id ? "AND broker.meta_value = '$broker_id'" : "";

    $active_brokers = count(get_users(['role' => 'author', 'fields' => ['ID']]));
    $admin_logger->log("Corretores ativos encontrados: {$active_brokers}");

    $total_leads = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'lead' AND post_status = 'publish'");
    $admin_logger->log("Total de leads encontrados: {$total_leads}");

    $total_searches_query = "
        SELECT COALESCE(SUM(CASE 
            WHEN post_type = 'smart-search' AND post_status = 'publish' 
            THEN 1 ELSE 0 END), 0)
        FROM {$wpdb->posts}";
    $total_searches = (int)$wpdb->get_var($total_searches_query);
    $admin_logger->log("Query pesquisas: {$total_searches_query}");
    $admin_logger->log("Total de pesquisas encontradas: {$total_searches}");

    $total_views_query = "
        SELECT COALESCE(SUM(meta_value), 0)
        FROM {$wpdb->postmeta} pm
        JOIN {$wpdb->posts} p ON p.ID = pm.post_id
        WHERE pm.meta_key = 'total_clicks'
        AND p.post_type = 'immobile'";
    $total_views = (int)$wpdb->get_var($total_views_query);
    $admin_logger->log("Query visualizações: {$total_views_query}");
    $admin_logger->log("Total de visualizações encontradas: {$total_views}");

    $metrics = [
        'active_brokers' => $active_brokers,
        'total_leads' => $total_leads,
        'total_searches' => $total_searches,
        'total_views' => $total_views,
        'sponsored_properties' => (int)$wpdb->get_var("
            SELECT COUNT(DISTINCT p.ID) 
            FROM {$wpdb->posts} p 
            INNER JOIN {$wpdb->postmeta} m ON p.ID = m.post_id 
            WHERE p.post_type = 'immobile' 
            AND m.meta_key = 'is_sponsored' 
            AND m.meta_value = 'yes'
        ")
    ];

    $daily_metrics_query = $wpdb->prepare("
        SELECT 
            DATE(p.post_date) as date,
            COALESCE(SUM(CASE WHEN pm.meta_key = 'total_clicks' THEN pm.meta_value END), 0) as views,
            COUNT(DISTINCT CASE WHEN p.post_type = 'lead' THEN p.ID END) as leads,
            COUNT(CASE WHEN p.post_type = 'smart-search' AND p.post_status = 'publish' THEN 1 END) as searches,
            COUNT(DISTINCT CASE WHEN p.post_type = 'broker' THEN p.ID END) as brokers,
            COUNT(DISTINCT CASE WHEN pm_sponsored.meta_key = 'is_sponsored' AND pm_sponsored.meta_value = 'yes' THEN p.ID END) as sponsored
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
        LEFT JOIN {$wpdb->postmeta} broker ON p.ID = broker.post_id AND broker.meta_key = 'broker'
        LEFT JOIN {$wpdb->postmeta} pm_sponsored ON p.ID = pm_sponsored.post_id AND pm_sponsored.meta_key = 'is_sponsored'
        WHERE p.post_date BETWEEN %s AND %s
        {$broker_query}
        GROUP BY DATE(p.post_date)
        ORDER BY date ASC
    ", $start_date, $end_date);

    $admin_logger->log("Query métricas diárias: {$daily_metrics_query}");
    
    $daily_metrics = $wpdb->get_results($daily_metrics_query);
    $admin_logger->log_metrics($daily_metrics, 'daily_metrics');

    $brokers = get_users([
        'role' => 'author',
        'orderby' => 'display_name',
        'order' => 'ASC'
    ]);

    $brokers_list = array_map(function($broker) {
        return [
            'id' => $broker->ID,
            'name' => $broker->display_name ?: $broker->user_login
        ];
    }, $brokers);

    $admin_logger->log_metrics($brokers_list, 'brokers_list');

    $metrics['daily_metrics'] = $daily_metrics;
    $metrics['brokers'] = $brokers_list;

    $admin_logger->log_metrics($metrics, 'final_metrics');
    wp_send_json_success($metrics);
}
add_action('wp_ajax_get_admin_metrics', 'get_admin_metrics_data');