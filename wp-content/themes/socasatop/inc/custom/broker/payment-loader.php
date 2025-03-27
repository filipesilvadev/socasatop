<?php
/**
 * Carregador do Sistema de Pagamento
 * 
 * Este arquivo centraliza o carregamento de todas as funcionalidades
 * relacionadas ao sistema de pagamento.
 */

// Impedir acesso direto ao arquivo
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Carregar dependências - garantindo que os arquivos de suporte sejam carregados primeiro
require_once get_stylesheet_directory() . '/inc/custom/broker/payment-settings.php';
require_once get_stylesheet_directory() . '/inc/custom/broker/payment-product.php';
require_once get_stylesheet_directory() . '/inc/custom/broker/payment-checkout.php';
require_once get_stylesheet_directory() . '/inc/custom/broker/payment-functions.php';

// Carregador de testes - comentar em produção
if (WP_DEBUG) {
    require_once get_stylesheet_directory() . '/inc/custom/broker/test-payment-integration.php';
}

/**
 * Registra todos os scripts e estilos para o sistema de pagamento
 */
if (!function_exists('register_payment_assets')) {
    function register_payment_assets() {
        // Mercado Pago SDK
        wp_register_script('mercadopago-sdk', 'https://sdk.mercadopago.com/js/v2', array(), null, true);
        
        // Core do sistema de pagamento
        $core_path = get_stylesheet_directory() . '/inc/custom/broker/assets/js/payment-core.js';
        $core_version = file_exists($core_path) ? filemtime($core_path) : time();
        wp_register_script('payment-core-js', get_stylesheet_directory_uri() . '/inc/custom/broker/assets/js/payment-core.js', array('jquery', 'mercadopago-sdk'), $core_version, true);
        
        // Scripts específicos
        $settings_path = get_stylesheet_directory() . '/inc/custom/broker/assets/js/payment-settings.js';
        $settings_version = file_exists($settings_path) ? filemtime($settings_path) : time();
        wp_register_script('payment-settings-js', get_stylesheet_directory_uri() . '/inc/custom/broker/assets/js/payment-settings.js', array('jquery', 'mercadopago-sdk'), $settings_version, true);
        
        // Estilos
        $checkout_css_path = get_stylesheet_directory() . '/inc/custom/broker/assets/css/payment-checkout.css';
        $checkout_css_version = file_exists($checkout_css_path) ? filemtime($checkout_css_path) : time();
        wp_register_style('payment-checkout-css', get_stylesheet_directory_uri() . '/inc/custom/broker/assets/css/payment-checkout.css', array(), $checkout_css_version);
        
        $settings_css_path = get_stylesheet_directory() . '/inc/custom/broker/assets/css/payment-settings.css';
        $settings_css_version = file_exists($settings_css_path) ? filemtime($settings_css_path) : time();
        wp_register_style('payment-settings-css', get_stylesheet_directory_uri() . '/inc/custom/broker/assets/css/payment-settings.css', array(), $settings_css_version);
    }
}

// Registrar a função no hook wp_enqueue_scripts apenas se a ação não tiver sido adicionada anteriormente
if (!has_action('wp_enqueue_scripts', 'register_payment_assets')) {
    add_action('wp_enqueue_scripts', 'register_payment_assets');
}

/**
 * Shortcode para exibir o formulário de pagamento
 * 
 * Uso: [socasa_payment_form product_id="sponsored_listing"]
 * 
 * @param array $atts Atributos do shortcode
 * @return string HTML do formulário de pagamento
 */
function socasa_payment_form_shortcode($atts) {
    // Verificar se as funções necessárias existem
    if (!function_exists('is_payment_system_configured') || 
        !function_exists('socasa_get_product') ||
        !function_exists('render_multi_product_checkout')) {
        return '<div class="payment-error">Erro na configuração do sistema de pagamento. Funções necessárias não encontradas.</div>';
    }
    
    // Extrair atributos
    $atts = shortcode_atts(array(
        'product_id' => '',
    ), $atts, 'socasa_payment_form');
    
    // Verificar se o sistema de pagamento está configurado
    if (!is_payment_system_configured()) {
        return '<div class="payment-error">Sistema de pagamento não configurado.</div>';
    }
    
    // Verificar se o usuário está logado
    if (!is_user_logged_in()) {
        return '<div class="payment-error">Você precisa estar logado para realizar um pagamento.</div>';
    }
    
    // Verificar se o produto existe
    $product = socasa_get_product($atts['product_id']);
    if (!$product) {
        return '<div class="payment-error">Produto não encontrado.</div>';
    }
    
    // Carregar estilos e scripts necessários
    wp_enqueue_style('payment-checkout-css');
    wp_enqueue_script('payment-core-js');
    wp_enqueue_script('mercadopago-sdk');
    
    // Iniciar buffer de saída
    ob_start();
    
    // Renderizar o formulário de checkout
    echo render_multi_product_checkout(array($product));
    
    // Retornar o conteúdo do buffer
    return ob_get_clean();
}
add_shortcode('socasa_payment_form', 'socasa_payment_form_shortcode');

/**
 * Atualiza as regras de reescrita do WordPress
 */
function flush_socasa_payment_rewrite_rules() {
    // Verificar se a função existe antes de chamá-la
    if (function_exists('register_mercadopago_webhook_endpoint')) {
        // Registrar as regras de reescrita
        register_mercadopago_webhook_endpoint();
        
        // Atualizar as regras
        flush_rewrite_rules();
    }
}

// Registrar a função para ser executada quando o tema for ativado
add_action('after_switch_theme', 'flush_socasa_payment_rewrite_rules');

// Executar a função uma vez para garantir que as regras sejam atualizadas
// Usar prioridade mais baixa para garantir que outras funções sejam carregadas primeiro
add_action('init', 'flush_socasa_payment_rewrite_rules', 999);