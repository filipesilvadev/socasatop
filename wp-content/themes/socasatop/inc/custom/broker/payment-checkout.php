<?php
/**
 * Checkout Unificado
 * 
 * Este arquivo contém as funções relacionadas ao checkout unificado,
 * permitindo o processamento de pagamentos para diferentes produtos
 * usando o sistema do Mercado Pago
 */

// Impedir acesso direto ao arquivo
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Renderiza o formulário de checkout com suporte a múltiplos produtos
 * 
 * @param array $products Lista de produtos a serem incluídos no checkout
 * @param array $args Argumentos adicionais
 * @return string HTML do formulário de checkout
 */
function render_multi_product_checkout($products, $args = []) {
    // Verificar se o usuário está logado
    if (!is_user_logged_in()) {
        return '<div class="payment-error">Você precisa estar logado para realizar esta operação</div>';
    }
    
    // Verificar se temos produtos válidos
    if (empty($products) || !is_array($products)) {
        return '<div class="payment-error">Nenhum produto válido foi especificado para o checkout</div>';
    }
    
    // Buscar informações do usuário
    $user_id = get_current_user_id();
    $user = get_userdata($user_id);
    
    // Verificar se temos o PUBLIC_KEY do Mercado Pago
    $mp_public_key = get_option('mercadopago_public_key');
    if (empty($mp_public_key)) {
        return '<div class="payment-error">Configuração de pagamento incompleta. Entre em contato com o administrador.</div>';
    }
    
    // Verificar e carregar o JavaScript core de pagamento
    check_payment_core_js();
    
    // Calcular o valor total do checkout
    $total_amount = 0;
    foreach ($products as $product) {
        if (isset($product['price'])) {
            $total_amount += floatval($product['price']);
        }
    }
    
    // Iniciar buffer de saída para retornar o HTML
    ob_start();
    ?>
    <div class="multi-product-checkout">
        <div class="checkout-header">
            <h3>Checkout</h3>
        </div>
        
        <div class="product-list">
            <h4>Produtos selecionados</h4>
            <ul class="checkout-products">
                <?php foreach ($products as $product): ?>
                <li class="checkout-product-item">
                    <div class="product-title"><?php echo esc_html($product['name']); ?></div>
                    <div class="product-price">R$ <?php echo number_format($product['price'], 2, ',', '.'); ?></div>
                    <?php if (isset($product['description']) && !empty($product['description'])): ?>
                    <div class="product-description"><?php echo esc_html($product['description']); ?></div>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
            
            <div class="checkout-total">
                <span class="total-label">Total:</span>
                <span class="total-value">R$ <?php echo number_format($total_amount, 2, ',', '.'); ?></span>
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
                    <p>Seu pagamento foi aprovado.</p>
                    <div class="payment-details"></div>
                    <a href="#" class="continue-button">Continuar</a>
                </div>
                <div class="error-message" style="display: none;">
                    <h3>Ocorreu um erro no pagamento</h3>
                    <p class="error-details"></p>
                    <button class="retry-button">Tentar novamente</button>
                </div>
            </div>
        </div>
        
        <div class="terms-container">
            <label for="accept-terms" class="terms-label">
                <input type="checkbox" id="accept-terms" name="accept-terms">
                <span>Li e aceito os <a href="/termos-de-uso/" target="_blank">termos de uso</a> e <a href="/politica-de-privacidade/" target="_blank">política de privacidade</a>.</span>
            </label>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Inicializar o sistema de pagamento
        if (typeof SocasaPayment !== 'undefined') {
            SocasaPayment.init({
                publicKey: '<?php echo esc_js($mp_public_key); ?>',
                amount: <?php echo $total_amount; ?>,
                ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
                nonce: '<?php echo wp_create_nonce('payment_nonce'); ?>',
                productIds: <?php echo json_encode(array_map(function($p) { return $p['id']; }, $products)); ?>,
                entityIds: <?php echo json_encode(array_map(function($p) { return $p['entity_id'] ?? 0; }, $products)); ?>,
                successRedirect: '<?php echo isset($args['success_url']) ? esc_js($args['success_url']) : home_url(); ?>',
                multiProduct: true
            });
        } else {
            console.error('SocasaPayment não encontrado. Verifique se o arquivo payment-core.js está carregado corretamente.');
        }
    });
    </script>
    <?php
    return ob_get_clean();
}

/**
 * Cria uma entrada de registro para o pagamento multi-produtos
 * 
 * @param int $user_id ID do usuário que fez o pagamento
 * @param array $products Produtos incluídos no pagamento
 * @param array $payment_data Dados do pagamento
 * @return int|false ID do registro ou false em caso de erro
 */
function create_multi_product_payment_record($user_id, $products, $payment_data) {
    global $wpdb;
    
    $payment_table = $wpdb->prefix . 'socasa_payments';
    
    // Verificar se a tabela existe
    if ($wpdb->get_var("SHOW TABLES LIKE '$payment_table'") != $payment_table) {
        // Criar a tabela se não existir
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $payment_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            payment_id varchar(100) NOT NULL,
            payment_method varchar(50) NOT NULL,
            amount decimal(10,2) NOT NULL,
            status varchar(20) NOT NULL,
            products longtext NOT NULL,
            payment_data longtext NOT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    // Inserir registro de pagamento
    $result = $wpdb->insert(
        $payment_table,
        [
            'user_id' => $user_id,
            'payment_id' => isset($payment_data['id']) ? $payment_data['id'] : '',
            'payment_method' => isset($payment_data['payment_method']) ? $payment_data['payment_method'] : 'card',
            'amount' => isset($payment_data['transaction_amount']) ? $payment_data['transaction_amount'] : 0,
            'status' => isset($payment_data['status']) ? $payment_data['status'] : 'pending',
            'products' => json_encode($products),
            'payment_data' => json_encode($payment_data),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ]
    );
    
    if ($result) {
        return $wpdb->insert_id;
    }
    
    return false;
}

/**
 * Processa a ativação dos produtos após pagamento bem-sucedido
 * 
 * @param array $products Lista de produtos a serem ativados
 * @param array $payment_data Dados do pagamento
 * @return boolean Sucesso da operação
 */
function activate_purchased_products($products, $payment_data) {
    if (empty($products) || !is_array($products)) {
        return false;
    }
    
    $all_activated = true;
    
    foreach ($products as $product) {
        // Verificar se temos os dados necessários
        if (!isset($product['id']) || !isset($product['entity_id'])) {
            continue;
        }
        
        $product_info = socasa_get_product($product['id']);
        $entity_id = intval($product['entity_id']);
        
        if (!$product_info || $entity_id <= 0) {
            $all_activated = false;
            continue;
        }
        
        // Verificar se existe callback específico para este produto
        if (!empty($product_info['callback']) && function_exists($product_info['callback'])) {
            $result = call_user_func($product_info['callback'], $entity_id, $payment_data);
            if (!$result || (isset($result['success']) && !$result['success'])) {
                $all_activated = false;
            }
        } else {
            // Processamento padrão
            update_post_meta($entity_id, 'product_' . $product_info['id'] . '_active', 'yes');
            update_post_meta($entity_id, 'product_' . $product_info['id'] . '_expiry', date('Y-m-d H:i:s', strtotime('+30 days')));
        }
    }
    
    return $all_activated;
}

/**
 * Shortcode para renderizar o checkout multi-produtos
 * 
 * @param array $atts Atributos do shortcode
 * @return string HTML do formulário de checkout
 */
function multi_product_checkout_shortcode($atts) {
    $atts = shortcode_atts([
        'product_ids' => '',  // Lista de IDs dos produtos separados por vírgula
        'entity_ids' => '',   // Lista de IDs das entidades separados por vírgula
        'success_url' => ''   // URL de redirecionamento após sucesso
    ], $atts, 'multi_checkout');
    
    // Verificar se temos produtos especificados
    if (empty($atts['product_ids'])) {
        return '<div class="payment-error">Nenhum produto especificado para o checkout</div>';
    }
    
    // Obter arrays de produtos e entidades
    $product_ids = explode(',', $atts['product_ids']);
    $entity_ids = !empty($atts['entity_ids']) ? explode(',', $atts['entity_ids']) : [];
    
    // Padronizar o tamanho dos arrays
    $max_count = max(count($product_ids), count($entity_ids));
    $entity_ids = array_pad($entity_ids, $max_count, 0);
    
    // Montar lista de produtos
    $products = [];
    foreach ($product_ids as $index => $product_id) {
        $product = socasa_get_product(trim($product_id));
        if ($product) {
            $product['entity_id'] = isset($entity_ids[$index]) ? intval($entity_ids[$index]) : 0;
            $products[] = $product;
        }
    }
    
    // Verificar se temos produtos válidos
    if (empty($products)) {
        return '<div class="payment-error">Nenhum produto válido encontrado</div>';
    }
    
    // Renderizar checkout
    return render_multi_product_checkout($products, [
        'success_url' => $atts['success_url']
    ]);
}
add_shortcode('multi_checkout', 'multi_product_checkout_shortcode');

/**
 * Manipula a requisição AJAX para processamento de pagamento multi-produtos
 */
function handle_multi_product_payment_ajax() {
    // Verificar nonce para segurança
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'payment_nonce')) {
        wp_send_json_error(['message' => 'Erro de segurança. Tente novamente.']);
    }
    
    // Verificar se o usuário está logado
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Usuário não autenticado.']);
    }
    
    // Verificar se temos os dados necessários
    if (!isset($_POST['payment_data']) || !isset($_POST['product_ids']) || !isset($_POST['entity_ids'])) {
        wp_send_json_error(['message' => 'Dados incompletos.']);
    }
    
    // Obter dados do pagamento
    $payment_data = json_decode(stripslashes($_POST['payment_data']), true);
    $product_ids = json_decode(stripslashes($_POST['product_ids']), true);
    $entity_ids = json_decode(stripslashes($_POST['entity_ids']), true);
    
    if (!$payment_data || !$product_ids || !is_array($product_ids)) {
        wp_send_json_error(['message' => 'Formato de dados inválido.']);
    }
    
    // Padronizar o tamanho dos arrays
    $max_count = max(count($product_ids), count($entity_ids));
    $entity_ids = array_pad($entity_ids, $max_count, 0);
    
    // Montar lista de produtos
    $products = [];
    foreach ($product_ids as $index => $product_id) {
        $product = socasa_get_product($product_id);
        if ($product) {
            $product['entity_id'] = isset($entity_ids[$index]) ? intval($entity_ids[$index]) : 0;
            $products[] = $product;
        }
    }
    
    // Verificar se temos produtos válidos
    if (empty($products)) {
        wp_send_json_error(['message' => 'Nenhum produto válido encontrado.']);
    }
    
    // Processar o pagamento com o Mercado Pago
    $mp_access_token = get_option('mercadopago_access_token');
    if (empty($mp_access_token)) {
        wp_send_json_error(['message' => 'Configuração de pagamento incompleta.']);
    }
    
    // Realizar a chamada à API do Mercado Pago
    $response = wp_remote_post(
        'https://api.mercadopago.com/v1/payments',
        [
            'headers' => [
                'Authorization' => 'Bearer ' . $mp_access_token,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($payment_data),
            'timeout' => 30
        ]
    );
    
    // Verificar resposta
    if (is_wp_error($response)) {
        wp_send_json_error([
            'message' => 'Erro ao processar pagamento: ' . $response->get_error_message()
        ]);
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = json_decode(wp_remote_retrieve_body($response), true);
    
    if ($response_code != 200 && $response_code != 201) {
        wp_send_json_error([
            'message' => 'Erro no processamento do pagamento.',
            'details' => isset($response_body['message']) ? $response_body['message'] : 'Erro desconhecido'
        ]);
    }
    
    // Processar sucesso
    $user_id = get_current_user_id();
    
    // Registrar pagamento
    $payment_record_id = create_multi_product_payment_record($user_id, $products, $response_body);
    
    // Ativar produtos
    if ($response_body['status'] === 'approved') {
        activate_purchased_products($products, $response_body);
    }
    
    // Retornar sucesso
    wp_send_json_success([
        'message' => 'Pagamento processado com sucesso!',
        'payment_id' => isset($response_body['id']) ? $response_body['id'] : '',
        'status' => isset($response_body['status']) ? $response_body['status'] : 'pending',
        'payment_record_id' => $payment_record_id
    ]);
}
add_action('wp_ajax_handle_multi_product_payment', 'handle_multi_product_payment_ajax'); 