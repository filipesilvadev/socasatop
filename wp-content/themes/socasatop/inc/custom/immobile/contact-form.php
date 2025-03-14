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

    // Obter dados do imóvel e corretor
    $immobile = get_post($post_id);
    $broker = get_userdata($broker_id);
    
    if (!$immobile || !$broker) {
        wp_send_json_error('Imóvel ou corretor não encontrado');
    }
    
    // Criar o lead como post type
    $lead_data = array(
        'post_title'    => $name,
        'post_type'     => 'lead',
        'post_status'   => 'publish'
    );

    $lead_id = wp_insert_post($lead_data);

    if ($lead_id) {
        // Salvar metadados do lead
        update_post_meta($lead_id, 'email', $email);
        update_post_meta($lead_id, 'whatsapp', $whatsapp);
        update_post_meta($lead_id, 'immobile_id', $post_id);
        update_post_meta($lead_id, 'broker_id', $broker_id);
        update_post_meta($lead_id, 'immobile_title', $immobile->post_title);
        update_post_meta($lead_id, 'broker_name', $broker->display_name);
        update_post_meta($lead_id, 'lead_date', current_time('mysql'));
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
    
    // Obter dados do corretor e preparar URL do WhatsApp
    $broker_phone = get_user_meta($broker_id, 'whatsapp', true);
    
    // Se o WhatsApp estiver vazio, tenta usar o telefone regular
    if (empty($broker_phone)) {
        $broker_phone = get_user_meta($broker_id, 'phone', true);
    }
    
    // Se ainda estiver vazio, usa um padrão ou retorna erro
    if (empty($broker_phone)) {
        wp_send_json_error('Corretor não possui número de WhatsApp cadastrado');
    }
    
    $formatted_phone = preg_replace('/[^0-9]/', '', $broker_phone);
    
    // Organizar mensagem para o WhatsApp com os espaçamentos corretos
    $message = "Olá! Encontrei seu anúncio no Só Casa Top\n\n\n";
    $message .= "Vi o imóvel " . $immobile->post_title . "\n";
    $message .= get_permalink($post_id) . "\n\n\n";
    $message .= "Gostaria de saber mais.";
    
    // Codificar mensagem para URL
    $encoded_message = urlencode($message);
    
    // Construir URL do WhatsApp
    $whatsapp_url = "https://wa.me/{$formatted_phone}?text={$encoded_message}";
    
    wp_send_json_success([
        'broker' => [
            'name' => $broker->display_name,
            'email' => $broker->user_email,
            'phone' => $broker_phone
        ],
        'immobile' => [
            'title' => $immobile->post_title,
            'url' => get_permalink($post_id)
        ],
        'whatsapp_url' => $whatsapp_url
    ]);
}
add_action('wp_ajax_submit_contact_form', 'handle_contact_form_submission');
add_action('wp_ajax_nopriv_submit_contact_form', 'handle_contact_form_submission');

// Incluir o arquivo da página de administração de leads
require_once get_stylesheet_directory() . '/inc/custom/lead/admin-page.php';