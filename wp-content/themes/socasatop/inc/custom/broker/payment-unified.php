<?php
/**
 * Sistema de Pagamento Unificado - Configuração Base
 * 
 * Este arquivo serve como ponto de entrada para o sistema de pagamento,
 * mas delegando responsabilidades para módulos específicos.
 */

// Impedir acesso direto ao arquivo
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Incluir arquivos necessários
require_once(get_stylesheet_directory() . '/inc/custom/broker/payment-product.php');
require_once(get_stylesheet_directory() . '/inc/custom/broker/payment-processors/mercadopago.php');

/**
 * Renderiza o formulário de pagamento unificado
 * 
 * @param string $product_id ID do produto a ser pago
 * @param int $entity_id ID da entidade relacionada (imóvel, anúncio, etc)
 * @param array $args Argumentos adicionais
 * @return string HTML do formulário de pagamento
 */
function render_unified_payment_form($product_id, $entity_id, $args = []) {
    // Obter informações do produto
    $product = socasa_get_product($product_id);
    
    if (!$product) {
        return '<div class="payment-error">Produto não encontrado</div>';
    }
    
    // Verificar se o usuário está logado
    if (!is_user_logged_in()) {
        return '<div class="payment-error">Você precisa estar logado para realizar esta operação</div>';
    }
    
    // Buscar informações do usuário
    $user_id = get_current_user_id();
    $user = get_userdata($user_id);
    
    // Verificar se temos o PUBLIC_KEY do Mercado Pago
    $mp_public_key = get_option('mercadopago_public_key');
    if (empty($mp_public_key)) {
        return '<div class="payment-error">Configuração de pagamento incompleta. Entre em contato com o administrador.</div>';
    }
    
    // Preparar dados para o formulário
    $payment_data = [
        'product' => $product,
        'entity_id' => $entity_id,
        'user_id' => $user_id,
        'user_email' => $user->user_email,
        'user_name' => $user->display_name,
        'mp_public_key' => $mp_public_key,
        'return_url' => home_url()
    ];
    
    // Incorporar dados personalizados
    $payment_data = array_merge($payment_data, $args);
    
    // Enfileirar scripts necessários
    wp_enqueue_script('mercadopago-sdk', 'https://sdk.mercadopago.com/js/v2', array(), null, true);
    wp_enqueue_script('socasa-payment-core', get_stylesheet_directory_uri() . '/inc/custom/broker/assets/js/payment-core.js', array('jquery'), '1.0.0', true);
    
    // Iniciar buffer de saída para retornar o HTML
    ob_start();
    ?>
    <div class="mp-payment-container">
        <div class="product-info">
            <h3><?php echo esc_html($product['name']); ?></h3>
            <p class="description"><?php echo esc_html($product['description']); ?></p>
            
            <?php if (!empty($product['features'])): ?>
            <div class="product-features">
                <ul>
                    <?php foreach ($product['features'] as $feature): ?>
                    <li><?php echo esc_html($feature); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <div class="product-price">
                <p><strong>Valor:</strong> R$ <?php echo number_format($product['price'], 2, ',', '.'); ?></p>
                <?php if (isset($product['recurrence']) && $product['recurrence'] == 'monthly'): ?>
                <p class="recurrence">Cobrança: Mensal</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="payment-form">
            <div id="payment-form">
                <!-- O formulário de pagamento será renderizado aqui -->
                <div class="mp-wallet-button-container"></div>
                <div class="mp-form-container"></div>
            </div>
            
            <div id="payment-status" style="display: none;">
                <div class="success-message" style="display: none;">
                    <h3>Pagamento processado com sucesso!</h3>
                    <p class="status-message"></p>
                </div>
                <div class="error-message" style="display: none;">
                    <h3>Erro no processamento do pagamento</h3>
                    <p class="status-message"></p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Configurar dados para o processador de pagamento
        window.mpPaymentData = <?php echo json_encode($payment_data); ?>;
        
        // Iniciar o sistema de pagamento
        if (typeof SocasaPayment !== 'undefined') {
            SocasaPayment.init({
                publicKey: '<?php echo esc_js($mp_public_key); ?>',
                amount: <?php echo floatval($product['price']); ?>,
                ajaxUrl: '<?php echo esc_js(admin_url('admin-ajax.php')); ?>',
                nonce: '<?php echo esc_js(wp_create_nonce('socasa_payment_nonce')); ?>',
                productId: '<?php echo esc_js($product_id); ?>',
                entityId: <?php echo intval($entity_id); ?>,
                successRedirect: '<?php echo esc_js($payment_data['return_url']); ?>'
            });
        } else {
            console.error('SocasaPayment não está definido. Verifique se o script foi carregado corretamente.');
        }
    });
    </script>
    
    <style>
    .mp-payment-container {
        max-width: 800px;
        margin: 0 auto;
        font-family: Arial, sans-serif;
    }
    
    .product-info {
        margin-bottom: 30px;
        padding: 20px;
        background-color: #f9f9f9;
        border-radius: 8px;
    }
    
    .product-features ul {
        padding-left: 20px;
    }
    
    .product-price {
        margin-top: 15px;
        font-size: 1.1em;
    }
    
    .payment-form {
        padding: 20px;
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .success-message {
        color: #28a745;
        padding: 15px;
        background-color: #d4edda;
        border-radius: 5px;
    }
    
    .error-message {
        color: #dc3545;
        padding: 15px;
        background-color: #f8d7da;
        border-radius: 5px;
    }
    </style>
    <?php
    return ob_get_clean();
}

/**
 * Inicializa os hooks para processamento de pagamentos
 */
function init_unified_payment_system() {
    // Registrar o Ajax handler para processamento de pagamentos
    add_action('wp_ajax_process_unified_payment', 'handle_unified_payment_ajax');
    add_action('wp_ajax_nopriv_process_unified_payment', 'handle_unified_payment_error_ajax');
    add_action('wp_ajax_handle_unified_payment', 'handle_unified_payment_ajax');
    add_action('wp_ajax_nopriv_handle_unified_payment', 'handle_unified_payment_error_ajax');
    
    // Preparar variáveis JavaScript
    add_action('wp_enqueue_scripts', 'register_unified_payment_scripts');
}

/**
 * Registra scripts e variáveis para o sistema de pagamentos
 */
function register_unified_payment_scripts() {
    // Localizar variáveis para o JavaScript
    wp_localize_script('jquery', 'ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php')
    ));
}

/**
 * Processa requisição de pagamento via Ajax (usuários não logados)
 */
function handle_unified_payment_error_ajax() {
    wp_send_json_error([
        'message' => 'Você precisa estar logado para realizar pagamentos.'
    ]);
}

/**
 * Processa requisição de pagamento via Ajax
 */
function handle_unified_payment_ajax() {
    // Verificar segurança
    if (!is_user_logged_in()) {
        wp_send_json_error([
            'message' => 'Usuário não autenticado.'
        ]);
        return;
    }
    
    // Obter dados da requisição
    $product_id = isset($_POST['product_id']) ? sanitize_text_field($_POST['product_id']) : '';
    $entity_id = isset($_POST['entity_id']) ? intval($_POST['entity_id']) : 0;
    $payment_data = isset($_POST['payment_data']) ? $_POST['payment_data'] : [];
    
    // Se o payment_data foi enviado como string JSON, decodificar
    if (is_string($payment_data)) {
        $payment_data = json_decode(stripslashes($payment_data), true);
    }
    
    // Validar dados
    if (empty($product_id) || empty($entity_id)) {
        wp_send_json_error([
            'message' => 'Dados de pagamento incompletos.'
        ]);
        return;
    }
    
    // Obter produto
    $product = socasa_get_product($product_id);
    if (!$product) {
        wp_send_json_error([
            'message' => 'Produto não encontrado.'
        ]);
        return;
    }
    
    // Processar o pagamento usando a API de produtos
    global $socasa_products;
    $result = $socasa_products->process_product_payment($product_id, $entity_id, $payment_data);
    
    if ($result['success']) {
        wp_send_json_success($result);
    } else {
        wp_send_json_error($result);
    }
}

// Inicializar o sistema de pagamento
init_unified_payment_system();

/**
 * Shortcode para o sistema de pagamento unificado
 */
function unified_payment_shortcode($atts) {
    $atts = shortcode_atts(array(
        'product' => '',
        'entity_id' => 0,
        'return_url' => '',
    ), $atts, 'unified_payment');
    
    if (empty($atts['product']) || empty($atts['entity_id'])) {
        return '<div class="payment-error">Parâmetros insuficientes. Defina product e entity_id.</div>';
    }
    
    $args = [];
    if (!empty($atts['return_url'])) {
        $args['return_url'] = $atts['return_url'];
    }
    
    return render_unified_payment_form($atts['product'], $atts['entity_id'], $args);
}

// Registrar shortcode
add_shortcode('unified_payment', 'unified_payment_shortcode'); 