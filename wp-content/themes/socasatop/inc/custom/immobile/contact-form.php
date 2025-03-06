<?php

function register_immobile_scripts() {
    if (!is_singular('immobile')) {
        return;
    }

    wp_enqueue_script('react');
    wp_enqueue_script('react-dom');
    wp_enqueue_style('tailwind');
    
    wp_register_script(
        'immobile-contact-form',
        get_stylesheet_directory_uri() . '/inc/custom/immobile/assets/js/contact-form.js',
        array('react', 'react-dom', 'sweetalert2', 'jquery'),
        time(),
        true
    );

    wp_localize_script('immobile-contact-form', 'site', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ajax_nonce')
    ));

    wp_enqueue_script('immobile-contact-form');
}
add_action('wp_enqueue_scripts', 'register_immobile_scripts', 1);

function display_broker_contact_form() {
    if (!is_singular('immobile')) {
        return '';
    }
    return '<div id="immobile-contact-form-container"></div>';
}
add_shortcode('immobile_contact_form', 'display_broker_contact_form');

function handle_contact_form_submission() {
    check_ajax_referer('ajax_nonce', 'nonce');
    
    $post_id = intval($_POST['post_id']);
    $broker_id = intval($_POST['broker_id']);
    $name = sanitize_text_field($_POST['name']);
    $email = sanitize_email($_POST['email']);
    $whatsapp = sanitize_text_field($_POST['whatsapp']);
    
    if (!$broker_id) {
        wp_send_json_error('Corretor não encontrado');
    }

    $lead_data = array(
        'post_title'    => $name,
        'post_type'     => 'lead',
        'post_status'   => 'publish'
    );

    $lead_id = wp_insert_post($lead_data);

    if ($lead_id) {
        update_post_meta($lead_id, 'email', $email);
        update_post_meta($lead_id, 'whatsapp', $whatsapp);
        update_post_meta($lead_id, 'immobile_id', $post_id);
        update_post_meta($lead_id, 'broker_id', $broker_id);
    }
    
    // Registra conversão para o imóvel
    $date = date('Y-m-d');
    $conversions = (int)get_post_meta($post_id, "metrics_conversions_{$date}", true);
    update_post_meta($post_id, "metrics_conversions_{$date}", $conversions + 1);
    
    $total_conversions = (int)get_post_meta($post_id, 'total_conversions', true);
    update_post_meta($post_id, 'total_conversions', $total_conversions + 1);
    
    // Registra conversão para o corretor
    $broker_conversions = (int)get_user_meta($broker_id, "metrics_conversions_{$date}", true);
    update_user_meta($broker_id, "metrics_conversions_{$date}", $broker_conversions + 1);
    
    $broker = get_userdata($broker_id);
    $phone = get_user_meta($broker_id, 'whatsapp', true);
    
    wp_send_json_success([
        'broker' => [
            'name' => $broker->display_name,
            'email' => $broker->user_email,
            'phone' => $phone
        ]
    ]);
}
add_action('wp_ajax_submit_contact_form', 'handle_contact_form_submission');
add_action('wp_ajax_nopriv_submit_contact_form', 'handle_contact_form_submission');