<?php
/**
 * Inicialização do sistema de pagamento
 */

// Impedir acesso direto ao arquivo
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function init_payment_settings() {
    // Registrar scripts e estilos
    add_action('wp_enqueue_scripts', 'register_payment_settings_assets');
    
    // Registrar endpoints AJAX
    add_action('wp_ajax_save_card', 'process_save_card');
    add_action('wp_ajax_set_default_card', 'set_default_card');
    add_action('wp_ajax_delete_card', 'delete_card');
    add_action('wp_ajax_get_user_saved_cards', 'get_user_saved_cards_ajax');
}

function register_payment_settings_assets() {
    // Verificar se estamos em uma página que precisa dos assets
    if (is_page('configuracoes-pagamento') || is_page('adicionar-imoveis')) {
        // Registrar e enfileirar o CSS
        wp_enqueue_style(
            'payment-styles',
            get_template_directory_uri() . '/inc/custom/broker/assets/css/payment-styles.css',
            array(),
            filemtime(get_template_directory() . '/inc/custom/broker/assets/css/payment-styles.css')
        );
        
        // Registrar e enfileirar o JavaScript do MercadoPago
        wp_enqueue_script('mercadopago-js', 'https://sdk.mercadopago.com/js/v2', array(), null, true);
        
        // Registrar e enfileirar o JavaScript personalizado
        wp_enqueue_script(
            'payment-settings',
            get_template_directory_uri() . '/inc/custom/broker/assets/js/payment-settings.js',
            array('jquery', 'mercadopago-js'),
            filemtime(get_template_directory() . '/inc/custom/broker/assets/js/payment-settings.js'),
            true
        );
        
        // Obter configurações do Mercado Pago
        $mp_config = get_mercadopago_config();
        
        // Verificar se as configurações são válidas
        if (empty($mp_config) || !isset($mp_config['public_key'])) {
            error_log('Configurações do Mercado Pago não encontradas ou inválidas');
            $mp_config = array(
                'public_key' => '',
                'sandbox' => true
            );
        }
        
        // Passar dados para o JavaScript
        wp_localize_script('payment-settings', 'payment_settings', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('payment_settings_nonce'),
            'public_key' => $mp_config['public_key'],
            'user_id' => get_current_user_id(),
            'user_email' => wp_get_current_user()->user_email,
            'sandbox' => $mp_config['sandbox'],
            'debug' => defined('WP_DEBUG') && WP_DEBUG
        ));
    }
}

/**
 * Obter configurações do Mercado Pago
 */
function get_mercadopago_config() {
    $config = array(
        'public_key' => get_option('mercadopago_public_key', ''),
        'access_token' => get_option('mercadopago_access_token', ''),
        'sandbox' => get_option('mercadopago_sandbox', 'yes') === 'yes'
    );
    
    // Se estiver em modo sandbox e as chaves de teste não estiverem definidas, usar as chaves de teste padrão
    if ($config['sandbox']) {
        if (empty($config['public_key'])) {
            $config['public_key'] = 'TEST-70b46d06-add9-499a-942e-0f5c01b8769a';
        }
        if (empty($config['access_token'])) {
            $config['access_token'] = 'TEST-110512347004016-010319-784660b8cba90a127251b50a9e066db6-242756635';
        }
    }
    
    return $config;
}

// Inicializar as configurações de pagamento
add_action('init', 'init_payment_settings'); 