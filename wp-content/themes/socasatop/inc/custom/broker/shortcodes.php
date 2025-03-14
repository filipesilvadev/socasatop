<?php
/**
 * Shortcodes para o sistema de corretores
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Shortcode para o Dashboard de Corretores
function broker_dashboard_shortcode($atts) {
    // Incluir o arquivo que contém a função broker_dashboard_content
    include_once(get_template_directory() . '/inc/custom/broker/dashboard.php');
    
    // Executar a função que renderiza o dashboard
    return broker_dashboard_content($atts);
}
add_shortcode('broker_dashboard', 'broker_dashboard_shortcode');

// Shortcode para o Formulário de Pagamento para Destacar Imóveis
function highlight_payment_form_shortcode($atts) {
    // Incluir o arquivo que contém a função render_payment_settings_page para obter as funções auxiliares
    include_once(get_template_directory() . '/inc/custom/broker/payment-settings.php');
    
    // Incluir o arquivo que contém a função highlight_payment_shortcode
    include_once(get_template_directory() . '/inc/custom/broker/highlight-payment.php');
    
    // Verificar se a função existe antes de chamá-la
    if (function_exists('highlight_payment_shortcode')) {
        return highlight_payment_shortcode($atts);
    }
    
    return 'Formulário de pagamento indisponível';
}
add_shortcode('highlight_payment', 'highlight_payment_form_shortcode');

// Shortcode para as Configurações de Pagamento
function payment_settings_shortcode($atts) {
    // Incluir o arquivo que contém a função render_payment_settings_page
    include_once(get_template_directory() . '/inc/custom/broker/payment-settings.php');
    
    // Verificar se a função existe antes de chamá-la
    if (function_exists('render_payment_settings_page')) {
        return render_payment_settings_page();
    }
    
    return 'Configurações de pagamento indisponíveis';
}
add_shortcode('payment_settings', 'payment_settings_shortcode');

// Garantir que este arquivo seja carregado
function load_broker_shortcodes() {
    // Já estamos dentro do arquivo, então não precisamos importá-lo novamente
}

// Carregar na inicialização do WordPress
add_action('init', 'load_broker_shortcodes'); 