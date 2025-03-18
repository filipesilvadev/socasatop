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
 * Verifica e carrega os scripts e estilos necessários para o checkout
 */
function check_payment_core_js() {
    // Mercado Pago SDK
    wp_enqueue_script('mercadopago-sdk', 'https://sdk.mercadopago.com/js/v2', array(), null, true);
    
    // Core JS
    $core_path = get_template_directory() . '/inc/custom/broker/assets/js/payment-core.js';
    $core_version = file_exists($core_path) ? filemtime($core_path) : time();
    wp_enqueue_script('payment-core-js', get_template_directory_uri() . '/inc/custom/broker/assets/js/payment-core.js', array('jquery', 'mercadopago-sdk'), $core_version, true);
    
    // CSS para checkout
    $css_path = get_template_directory() . '/inc/custom/broker/assets/css/payment-checkout.css';
    $css_version = file_exists($css_path) ? filemtime($css_path) : time();
    wp_enqueue_style('payment-checkout-css', get_template_directory_uri() . '/inc/custom/broker/assets/css/payment-checkout.css', array(), $css_version);
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
    
    // Buscar cartões salvos do usuário
    $saved_cards = get_user_mercadopago_cards($user_id);
    $default_card_id = get_user_meta($user_id, 'default_payment_card', true);
    
    // Log para debug
    error_log('Cartões salvos encontrados: ' . json_encode($saved_cards));
    error_log('Cartão padrão: ' . $default_card_id);
    
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
                <?php if (!empty($saved_cards)) : ?>
                <!-- Lista de cartões salvos -->
                <div class="saved-cards-section">
                    <h4>Escolha um cartão salvo</h4>
                    <div class="saved-cards-list">
                        <?php foreach ($saved_cards as $card_id => $card) : ?>
                        <div class="saved-card-option">
                            <label class="card-radio-label">
                                <input type="radio" name="payment_method" value="saved_card" 
                                       data-card-id="<?php echo esc_attr($card_id); ?>" 
                                       <?php checked($card_id, $default_card_id); ?>>
                                <div class="card-info">
                                    <div class="card-brand">
                                        <img src="<?php echo get_card_brand_logo($card['brand']); ?>" alt="<?php echo esc_attr($card['brand']); ?>">
                                    </div>
                                    <div class="card-details">
                                        <span class="card-number">•••• •••• •••• <?php echo esc_html($card['last_four']); ?></span>
                                        <span class="card-expiry">Validade: <?php echo esc_html($card['expiry_month']); ?>/<?php echo esc_html($card['expiry_year']); ?></span>
                                    </div>
                                </div>
                            </label>
                        </div>
                        <?php endforeach; ?>
                        <div class="new-card-option">
                            <label class="card-radio-label">
                                <input type="radio" name="payment_method" id="new-card-option" value="new_card">
                                <div class="card-info">
                                    <div class="card-details">
                                        <span>Usar outro cartão</span>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Formulário para novo cartão -->
                <div class="new-card-form" style="display: none;">
                    <h4>Novo Cartão</h4>
                    <div id="card-form" class="mp-form">
                        <div class="mp-input-container">
                            <label for="cardholderName">Nome no cartão</label>
                            <input type="text" id="cardholderName" data-checkout="cardholderName" placeholder="Nome como está no cartão">
                        </div>
                        <div class="mp-input-container">
                            <label>Número do cartão</label>
                            <div id="cardNumberContainer"></div>
                        </div>
                        <div class="mp-row">
                            <div class="mp-col-6">
                                <label>Data de validade</label>
                                <div id="expirationDateContainer"></div>
                            </div>
                            <div class="mp-col-6">
                                <label>Código de segurança</label>
                                <div id="securityCodeContainer"></div>
                            </div>
                        </div>
                        <div class="save-card-option">
                            <label>
                                <input type="checkbox" id="save-card-checkbox" checked>
                                <span>Salvar este cartão para futuras compras</span>
                            </label>
                        </div>
                    </div>
                </div>
                <?php else : ?>
                <!-- Apenas o formulário de novo cartão quando não há cartões salvos -->
                <div class="new-card-form">
                    <h4>Dados de Pagamento</h4>
                    <div id="card-form" class="mp-form">
                        <div class="mp-input-container">
                            <label for="cardholderName">Nome no cartão</label>
                            <input type="text" id="cardholderName" data-checkout="cardholderName" placeholder="Nome como está no cartão">
                        </div>
                        <div class="mp-input-container">
                            <label>Número do cartão</label>
                            <div id="cardNumberContainer"></div>
                        </div>
                        <div class="mp-row">
                            <div class="mp-col-6">
                                <label>Data de validade</label>
                                <div id="expirationDateContainer"></div>
                            </div>
                            <div class="mp-col-6">
                                <label>Código de segurança</label>
                                <div id="securityCodeContainer"></div>
                            </div>
                        </div>
                        <div class="save-card-option">
                            <label>
                                <input type="checkbox" id="save-card-checkbox" checked>
                                <span>Salvar este cartão para futuras compras</span>
                            </label>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Área de mensagens e botão de pagamento -->
        <div class="payment-actions">
            <div class="payment-messages"></div>
            <button type="submit" class="checkout-button payment-button">Finalizar Pagamento</button>
        </div>
    </div>
    <?php
    
    // Configurações para o JavaScript
    $payment_config = [
        'publicKey' => $mp_public_key,
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('payment_nonce'),
        'debug' => defined('WP_DEBUG') && WP_DEBUG,
        'multiProduct' => true,
        'amount' => number_format($total_amount, 2, '.', '')
    ];
    ?>
    <script>
        // Configurações para o processador de pagamento
        window.socasaPaymentConfig = <?php echo json_encode($payment_config); ?>;
    </script>
    <?php
    
    // Retornar o HTML gerado
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
 * Manipulador AJAX para pagamentos multi-produto
 */
function handle_multi_product_payment_ajax() {
    // Verificar nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'payment_nonce')) {
        wp_send_json_error(['message' => 'Verificação de segurança falhou. Por favor, recarregue a página e tente novamente.']);
        return;
    }
    
    // Verificar se o usuário está logado
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Você precisa estar logado para realizar esta operação.']);
        return;
    }
    
    $user_id = get_current_user_id();
    
    // Verificar método de pagamento
    $payment_method = isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : '';
    
    // Validar campos obrigatórios
    if (empty($payment_method)) {
        wp_send_json_error(['message' => 'Método de pagamento não especificado.']);
        return;
    }
    
    // Recuperar produtos da sessão
    $session_products = isset($_SESSION['checkout_products']) ? $_SESSION['checkout_products'] : [];
    if (empty($session_products)) {
        wp_send_json_error(['message' => 'Nenhum produto selecionado para pagamento.']);
        return;
    }
    
    // Verificar se temos um processador de pagamento
    if (!function_exists('process_payment_with_method')) {
        error_log('Função process_payment_with_method não encontrada. Verifique se o arquivo payment-unified.php está carregado.');
        wp_send_json_error(['message' => 'Processador de pagamento não disponível. Entre em contato com o administrador.']);
        return;
    }
    
    try {
        // Preparar dados de pagamento
        $payment_data = [
            'payment_method' => $payment_method,
            'payment_data' => []
        ];
        
        // Calcular valor total
        $total_amount = 0;
        foreach ($session_products as $product) {
            $total_amount += isset($product['price']) ? floatval($product['price']) : 0;
        }
        
        // Definir descrição padrão
        $description = count($session_products) > 1 
            ? 'Compra de ' . count($session_products) . ' produtos' 
            : 'Compra de ' . $session_products[0]['name'];
        
        // Processar com base no método de pagamento
        if ($payment_method === 'new_card') {
            // Processar pagamento com novo cartão
            if (!isset($_POST['card_token']) || empty($_POST['card_token'])) {
                wp_send_json_error(['message' => 'Token do cartão não fornecido.']);
                return;
            }
            
            $payment_data['payment_data'] = [
                'token' => sanitize_text_field($_POST['card_token']),
                'payment_method_id' => isset($_POST['payment_method_id']) ? sanitize_text_field($_POST['payment_method_id']) : '',
                'issuer_id' => isset($_POST['issuer_id']) ? sanitize_text_field($_POST['issuer_id']) : '',
                'transaction_amount' => $total_amount,
                'installments' => 1,
                'description' => $description,
                'payer' => [
                    'email' => get_user_meta($user_id, 'billing_email', true) ?: get_userdata($user_id)->user_email
                ]
            ];
            
            // Verificar se deve salvar o cartão
            $save_card = isset($_POST['save_card']) && $_POST['save_card'] == 'true';
            if ($save_card) {
                $payment_data['save_card'] = true;
            }
            
        } elseif ($payment_method === 'saved_card') {
            // Processar pagamento com cartão salvo
            if (!isset($_POST['saved_card_id']) || empty($_POST['saved_card_id'])) {
                wp_send_json_error(['message' => 'ID do cartão salvo não fornecido.']);
                return;
            }
            
            $card_id = sanitize_text_field($_POST['saved_card_id']);
            
            // Buscar cartões salvos do usuário
            $saved_cards = get_user_mercadopago_cards($user_id);
            
            // Verificar se o cartão existe
            if (!isset($saved_cards[$card_id])) {
                wp_send_json_error(['message' => 'Cartão não encontrado. Por favor, selecione outro cartão ou adicione um novo.']);
                return;
            }
            
            $payment_data['payment_data'] = [
                'saved_card_id' => $card_id,
                'description' => $description,
                'amount' => $total_amount
            ];
        } else {
            wp_send_json_error(['message' => 'Método de pagamento não suportado.']);
            return;
        }
        
        // Log para debug
        error_log('Dados de pagamento: ' . json_encode($payment_data));
        
        // Processar pagamento
        $result = process_payment_with_method('mercadopago', $payment_data, $session_products[0]); // Usa o primeiro produto como referência
        
        if ($result['success']) {
            // Criar registro de pagamento
            $payment_id = create_multi_product_payment_record($user_id, $session_products, $result);
            
            // Ativar produtos comprados
            activate_purchased_products($session_products, $result);
            
            // Limpar produtos da sessão
            unset($_SESSION['checkout_products']);
            
            // Retornar sucesso
            wp_send_json_success([
                'message' => 'Pagamento processado com sucesso!',
                'payment_id' => $payment_id,
                'redirect_url' => home_url('/corretores/pagamentos/') . '?payment_success=1'
            ]);
        } else {
            wp_send_json_error([
                'message' => 'Erro no processamento do pagamento: ' . $result['message']
            ]);
        }
        
    } catch (Exception $e) {
        error_log('Erro ao processar pagamento: ' . $e->getMessage());
        wp_send_json_error([
            'message' => 'Erro interno ao processar pagamento: ' . $e->getMessage()
        ]);
    }
}
add_action('wp_ajax_handle_multi_product_payment', 'handle_multi_product_payment_ajax');

/**
 * Processa o webhook do Mercado Pago
 */
function handle_mercadopago_webhook() {
    // Verificar se é uma requisição POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        status_header(405);
        die('Método não permitido');
    }
    
    // Obter o corpo da requisição
    $request_body = file_get_contents('php://input');
    $data = json_decode($request_body, true);
    
    // Verificar se os dados são válidos
    if (empty($data) || !isset($data['action']) || !isset($data['data'])) {
        status_header(400);
        die('Dados inválidos');
    }
    
    // Registrar o webhook no log
    if (function_exists('write_log')) {
        write_log('Webhook do Mercado Pago recebido: ' . $request_body);
    }
    
    // Processar apenas notificações de pagamento
    if ($data['action'] !== 'payment.created' && $data['action'] !== 'payment.updated') {
        status_header(200);
        die('Evento ignorado: ' . $data['action']);
    }
    
    // Obter o ID do pagamento
    $payment_id = isset($data['data']['id']) ? $data['data']['id'] : '';
    if (empty($payment_id)) {
        status_header(400);
        die('ID do pagamento não encontrado');
    }
    
    // Obter os detalhes do pagamento da API do Mercado Pago
    $access_token = get_option('mercadopago_access_token', '');
    if (empty($access_token)) {
        status_header(500);
        die('Token de acesso não configurado');
    }
    
    $api_url = "https://api.mercadopago.com/v1/payments/{$payment_id}";
    $args = array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type' => 'application/json'
        )
    );
    
    $response = wp_remote_get($api_url, $args);
    
    if (is_wp_error($response)) {
        status_header(500);
        die('Erro ao consultar a API do Mercado Pago: ' . $response->get_error_message());
    }
    
    $payment_data = json_decode(wp_remote_retrieve_body($response), true);
    
    if (empty($payment_data) || !isset($payment_data['status'])) {
        status_header(500);
        die('Dados do pagamento inválidos');
    }
    
    // Verificar se o pagamento foi aprovado
    if ($payment_data['status'] !== 'approved') {
        status_header(200);
        die('Status do pagamento: ' . $payment_data['status']);
    }
    
    // Extrair informações do pagamento
    $external_reference = isset($payment_data['external_reference']) ? $payment_data['external_reference'] : '';
    if (empty($external_reference)) {
        status_header(400);
        die('Referência externa não encontrada');
    }
    
    // A referência externa deve estar no formato "product_id:user_id"
    $reference_parts = explode(':', $external_reference);
    if (count($reference_parts) !== 2) {
        status_header(400);
        die('Formato de referência externa inválido');
    }
    
    $product_id = $reference_parts[0];
    $user_id = $reference_parts[1];
    
    // Processar o pagamento
    $payment_confirmation_data = array(
        'payment_id' => $payment_id,
        'product_id' => $product_id,
        'user_id' => $user_id,
        'payment_method' => 'mercadopago',
        'status' => $payment_data['status'],
        'amount' => $payment_data['transaction_amount'],
        'payment_data' => $payment_data
    );
    
    $result = process_payment_confirmation($payment_confirmation_data);
    
    if (!$result['success']) {
        status_header(500);
        die('Erro ao processar o pagamento: ' . $result['message']);
    }
    
    status_header(200);
    die('Pagamento processado com sucesso');
}

// Registrar o endpoint do webhook
function register_mercadopago_webhook_endpoint() {
    add_rewrite_rule('^mercadopago-webhook/?$', 'index.php?mercadopago_webhook=1', 'top');
    add_rewrite_tag('%mercadopago_webhook%', '([0-9]+)');
}
add_action('init', 'register_mercadopago_webhook_endpoint');

// Processar o webhook quando o endpoint for acessado
function process_mercadopago_webhook_request() {
    global $wp;
    if (isset($wp->query_vars['mercadopago_webhook'])) {
        handle_mercadopago_webhook();
        exit;
    }
}
add_action('parse_request', 'process_mercadopago_webhook_request'); 