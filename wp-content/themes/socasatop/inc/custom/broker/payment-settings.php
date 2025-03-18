<?php
/**
 * Configurações de Pagamento
 * 
 * Este arquivo contém funções relacionadas às configurações de pagamento.
 */

// Impedir acesso direto ao arquivo
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Renderiza a página de configurações de pagamento
 * 
 * @return string HTML da página de configurações
 */
function render_payment_settings_page() {
    if (!is_user_logged_in()) {
        return '<div class="alert alert-warning">Você precisa estar logado para visualizar suas configurações de pagamento.</div>';
    }
    
    // Obter dados do usuário
    $user_id = get_current_user_id();
    $cards = get_user_mercadopago_cards($user_id);
    $subscriptions = get_user_mercadopago_subscriptions($user_id);
    $default_card_id = get_user_meta($user_id, 'default_payment_card', true);
    
    // Carregar o SDK do Mercado Pago
    $mp_config = get_mercadopago_config();
    
    // Verificar se a configuração foi carregada corretamente
    if (empty($mp_config) || !isset($mp_config['public_key']) || !isset($mp_config['access_token'])) {
        error_log("Configuração do Mercado Pago não encontrada ou incompleta");
        $mp_config = [
            'sandbox' => true,
            'public_key' => 'TEST-70b46d06-add9-499a-942e-0f5c01b8769a',
            'access_token' => 'TEST-110512347004016-010319-784660b8cba90a127251b50a9e066db6-242756635'
        ];
    }
    
    // Enqueue scripts
    wp_enqueue_script('mercadopago-sdk', 'https://sdk.mercadopago.com/js/v2', array(), null, true);
    wp_enqueue_script('payment-settings-js', get_template_directory_uri() . '/inc/custom/broker/assets/js/payment-settings.js', array('jquery', 'mercadopago-sdk'), time(), true);
    
    // Passar variáveis para o JavaScript
    wp_localize_script('payment-settings-js', 'payment_settings', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('payment_settings_nonce'),
        'public_key' => $mp_config['public_key'],
        'user_id' => $user_id,
        'is_sandbox' => $mp_config['sandbox'],
        'site_url' => site_url()
    ));
    
    // Iniciar buffer de saída
    ob_start();
    ?>
    <div class="payment-settings-container">
        <h2>Configurações de Pagamento</h2>
        
        <?php if ($mp_config['sandbox']): ?>
        <div class="test-environment-notice">
            <strong>Ambiente de Teste!</strong> Use um <a href="https://www.mercadopago.com.br/developers/pt/docs/checkout-api/testing/cards" target="_blank">cartão de teste do Mercado Pago</a> para testar.
        </div>
        <?php endif; ?>
        
        <div class="payment-settings-section">
            <h3>Cartões Salvos</h3>
            <p>Gerencie seus cartões de crédito para pagamentos recorrentes.</p>
            
            <?php if (!empty($cards)) : ?>
                <div class="cards-container">
                    <?php foreach ($cards as $card) : ?>
                        <div class="card-item <?php echo $card['is_default'] ? 'default-card' : ''; ?>">
                            <div class="card-details">
                                <div class="card-brand">
                                    <img src="<?php echo get_card_brand_logo($card['brand']); ?>" alt="<?php echo esc_attr($card['brand']); ?>">
                                </div>
                                <div class="card-info">
                                    <span class="card-number">•••• •••• •••• <?php echo esc_html($card['last_four']); ?></span>
                                    <span class="card-expiry">Válido até: <?php echo esc_html($card['expiry_month']); ?>/<?php echo esc_html($card['expiry_year']); ?></span>
                                    <?php if ($card['is_default']) : ?>
                                        <span class="default-badge">Padrão</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-actions">
                                <?php if (!$card['is_default']) : ?>
                                    <button type="button" class="set-default-card button button-secondary" data-card-id="<?php echo esc_attr($card['id']); ?>">
                                        Definir como padrão
                                    </button>
                                <?php endif; ?>
                                <button type="button" class="delete-card button button-link-delete" data-card-id="<?php echo esc_attr($card['id']); ?>">
                                    Remover
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <div class="no-cards-message">
                    <p>Você ainda não possui cartões salvos.</p>
                </div>
            <?php endif; ?>
            
            <div class="add-card-section">
                <button type="button" id="add-new-card" class="button button-primary">
                    Adicionar novo cartão
                </button>
                
                <div id="card-form-container" style="display: none;">
                    <form id="card-form" class="mp-form">
                        <div class="mp-form-row">
                            <div class="mp-col-12">
                                <label>Nome no cartão</label>
                                <input type="text" id="cardholderName" placeholder="Nome como está no cartão" />
                            </div>
                        </div>
                        
                        <div class="mp-form-row">
                            <div class="mp-col-12">
                                <label>Número do cartão</label>
                                <div id="cardNumberContainer" class="mp-input-container"></div>
                            </div>
                        </div>
                        
                        <div class="mp-form-row">
                            <div class="mp-col-6">
                                <label>Data de validade</label>
                                <div id="expirationDateContainer" class="mp-input-container"></div>
                            </div>
                            <div class="mp-col-6">
                                <label>Código de segurança</label>
                                <div id="securityCodeContainer" class="mp-input-container"></div>
                            </div>
                        </div>
                        
                        <div class="mp-form-row">
                            <div class="mp-col-12">
                                <label>CPF</label>
                                <input type="text" id="identificationNumber" placeholder="Digite seu CPF" />
                                <input type="hidden" id="identificationType" value="CPF" />
                            </div>
                        </div>
                        
                        <div class="mp-form-row">
                            <div class="mp-col-12">
                                <label>Banco emissor</label>
                                <select id="issuer" class="mp-input-select"></select>
                                <input type="hidden" id="installments" value="1" />
                            </div>
                        </div>

                        <div class="mp-form-actions">
                            <button type="button" id="cancel-card-form" class="button button-secondary">Cancelar</button>
                            <button type="submit" id="save-card" class="button button-primary">Salvar cartão</button>
                        </div>
                        
                        <div id="result-message"></div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="payment-settings-section">
            <h3>Histórico de Pagamentos</h3>
            <p>Visualize seu histórico de pagamentos e assinaturas ativas.</p>
            
            <?php if (!empty($subscriptions)) : ?>
                <div class="subscriptions-table-wrapper">
                    <h4>Assinaturas Ativas</h4>
                    <table class="subscriptions-table">
                        <thead>
                            <tr>
                                <th>Imóvel</th>
                                <th>Data de início</th>
                                <th>Próxima cobrança</th>
                                <th>Valor</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subscriptions as $subscription) : 
                                $immobile = get_post($subscription['immobile_id']);
                                $immobile_title = $immobile ? $immobile->post_title : 'Imóvel #' . $subscription['immobile_id'];
                            ?>
                                <tr>
                                    <td>
                                        <?php if ($immobile) : ?>
                                            <a href="<?php echo get_permalink($immobile->ID); ?>" target="_blank">
                                                <?php echo esc_html($immobile_title); ?>
                                            </a>
                                        <?php else : ?>
                                            <?php echo esc_html($immobile_title); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date_i18n(get_option('date_format'), strtotime($subscription['start_date'])); ?></td>
                                    <td><?php echo date_i18n(get_option('date_format'), strtotime($subscription['next_charge'])); ?></td>
                                    <td>R$ <?php echo number_format($subscription['amount'], 2, ',', '.'); ?></td>
                                    <td>
                                        <span class="subscription-status status-<?php echo $subscription['status']; ?>">
                                            <?php 
                                                $status_labels = array(
                                                    'active' => 'Ativa',
                                                    'paused' => 'Pausada',
                                                    'cancelled' => 'Cancelada'
                                                );
                                                echo isset($status_labels[$subscription['status']]) ? $status_labels[$subscription['status']] : $subscription['status'];
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($subscription['status'] === 'active') : ?>
                                            <button class="pause-subscription" 
                                                    data-subscription-id="<?php echo esc_attr($subscription['id']); ?>"
                                                    data-immobile-id="<?php echo esc_attr($subscription['immobile_id']); ?>">
                                                Cancelar
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else : ?>
                <div class="notice notice-info">
                    Você não possui assinaturas ativas no momento.
                </div>
            <?php endif; ?>
            
            <div class="payment-history">
                <h4>Transações Recentes</h4>
                <?php
                    // Buscar histórico de transações
                    $transactions = get_user_recent_transactions($user_id);
                    
                    if (!empty($transactions)) :
                ?>
                    <div class="transactions-table-wrapper">
                        <table class="transactions-table">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Descrição</th>
                                    <th>Valor</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $transaction) : ?>
                                    <tr>
                                        <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($transaction['date'])); ?></td>
                                        <td><?php echo esc_html($transaction['description']); ?></td>
                                        <td>R$ <?php echo number_format($transaction['amount'], 2, ',', '.'); ?></td>
                                        <td>
                                            <span class="transaction-status status-<?php echo $transaction['status']; ?>">
                                                <?php 
                                                    $status_labels = array(
                                                        'approved' => 'Aprovado',
                                                        'pending' => 'Pendente',
                                                        'rejected' => 'Rejeitado'
                                                    );
                                                    echo isset($status_labels[$transaction['status']]) ? $status_labels[$transaction['status']] : $transaction['status'];
                                                ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else : ?>
                    <div class="notice notice-info">
                        Nenhuma transação encontrada no seu histórico.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <style>
        .payment-settings-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .payment-settings-section {
            margin-bottom: 40px;
        }

        /* Estilos para o formulário do MercadoPago */
        .mp-form {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-top: 20px;
        }

        .mp-form-row {
            margin-bottom: 20px;
        }

        .mp-col-12 {
            width: 100%;
        }

        .mp-col-6 {
            width: 48%;
            display: inline-block;
            margin-right: 2%;
        }

        .mp-col-6:last-child {
            margin-right: 0;
        }

        .mp-input-container {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 8px;
            background: #fff;
            min-height: 40px;
        }

        .mp-form label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .mp-form input[type="text"],
        .mp-form select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .mp-form select {
            height: 40px;
            background: #fff;
        }

        .payment-submit-button {
            width: 100%;
            padding: 12px 24px;
            background: #0056b3;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .payment-submit-button:hover {
            background: #004494;
        }

        .payment-submit-button:disabled {
            background: #cccccc;
            cursor: not-allowed;
        }

        .error-message {
            color: #dc3545;
            padding: 10px;
            margin-top: 10px;
            border: 1px solid #dc3545;
            border-radius: 4px;
            background: #fff;
        }

        /* Estilos para cartões salvos */
        .cards-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 20px;
        }

        .card-item {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
            width: calc(33% - 20px);
        }

        .card-item.default {
            border: 2px solid #0056b3;
        }

        .default-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #0056b3;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
        }

        .card-brand {
            margin-bottom: 10px;
        }

        .card-brand img {
            height: 30px;
            width: auto;
        }

        .card-number {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .card-expiry {
            color: #666;
            font-size: 14px;
        }

        .card-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }

        .card-actions button {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }

        .set-default-card {
            background: #28a745;
            color: white;
        }

        .delete-card {
            background: #dc3545;
            color: white;
        }

        .set-default-card:hover {
            background: #218838;
        }

        .delete-card:hover {
            background: #c82333;
        }

        @media (max-width: 768px) {
            .mp-col-6 {
                width: 100%;
                margin-right: 0;
                margin-bottom: 15px;
            }

            .cards-container {
                flex-direction: column;
            }
        }
    </style>
    <?php
    
    // Retornar conteúdo do buffer
    return ob_get_clean();
}

/**
 * Gerenciamento de Cartões e Configurações de Pagamento do Corretor
 */

/**
 * Obtém os cartões salvos do usuário no Mercado Pago
 */
function get_user_mercadopago_cards($user_id) {
    // Verificar se o usuário existe
    if (!get_user_by('id', $user_id)) {
        error_log("Tentativa de acessar cartões para usuário inexistente: $user_id");
        return array();
    }
    
    // Obter cartões salvos
    $saved_cards = get_user_meta($user_id, 'mercadopago_cards', true);
    
    // Se não for um array, retornar array vazio
    if (!is_array($saved_cards)) {
        return array();
    }
    
    // Filtrar cartões válidos
    $valid_cards = array();
    foreach ($saved_cards as $id => $card) {
        // Verificar se o cartão tem as informações mínimas necessárias
        if (isset($card['last_four']) || isset($card['last_four_digits'])) {
            // Padronizar o campo last_four
            if (!isset($card['last_four']) && isset($card['last_four_digits'])) {
                $card['last_four'] = $card['last_four_digits'];
            } elseif (!isset($card['last_four']) && !isset($card['last_four_digits'])) {
                $card['last_four'] = '0000'; // Valor padrão se não houver informação
            }
            
            // Padronizar o campo brand
            if (!isset($card['brand']) || empty($card['brand'])) {
                $card['brand'] = isset($card['payment_method_id']) ? $card['payment_method_id'] : 'unknown';
            }
            
            // Garantir que os campos de expiração existam
            if (!isset($card['expiry_month']) || empty($card['expiry_month'])) {
                $card['expiry_month'] = isset($card['expiration_month']) ? $card['expiration_month'] : '12';
            }
            
            if (!isset($card['expiry_year']) || empty($card['expiry_year'])) {
                $card['expiry_year'] = isset($card['expiration_year']) ? $card['expiration_year'] : '2030';
            }
            
            // Adicionar ao array válido
            $valid_cards[$id] = $card;
        }
    }
    
    return $valid_cards;
}

/**
 * Obtém as assinaturas ativas do usuário no Mercado Pago
 */
function get_user_mercadopago_subscriptions($user_id) {
    global $wpdb;
    
    // Buscar imóveis destacados do usuário
    $immobile_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT post_id 
         FROM {$wpdb->postmeta} 
         WHERE meta_key = 'broker' 
         AND meta_value = %d",
        $user_id
    ));
    
    if (empty($immobile_ids)) {
        return array();
    }
    
    $immobile_ids_placeholders = implode(',', array_fill(0, count($immobile_ids), '%d'));
    $query = $wpdb->prepare(
        "SELECT p.ID as immobile_id, pm.meta_value as subscription_id 
         FROM {$wpdb->posts} p
         JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'mercadopago_subscription_id'
         JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = 'is_sponsored' AND pm2.meta_value = 'yes'
         WHERE p.ID IN (" . $immobile_ids_placeholders . ")
         AND p.post_status = 'publish'",
        $immobile_ids
    );
    
    $results = $wpdb->get_results($query, ARRAY_A);
    
    // Dados fictícios para demonstração
    $subscriptions = array();
    foreach ($results as $result) {
        $start_date = date('Y-m-d', strtotime('-30 days'));
        $next_charge = date('Y-m-d', strtotime('+1 days'));
        
        $subscriptions[] = array(
            'id' => $result['subscription_id'],
            'immobile_id' => $result['immobile_id'],
            'start_date' => $start_date,
            'next_charge' => $next_charge,
            'amount' => 49.90,
            'status' => 'active'
        );
    }
    
    return $subscriptions;
}

/**
 * Obter o nome da bandeira do cartão
 */
function get_card_brand_name($brand) {
    $brands = array(
        'visa' => 'Visa',
        'mastercard' => 'Mastercard',
        'amex' => 'American Express',
        'elo' => 'Elo',
        'hipercard' => 'Hipercard',
        'discover' => 'Discover',
        'diners' => 'Diners Club',
        'jcb' => 'JCB',
        'aura' => 'Aura',
        'hiper' => 'Hiper'
    );
    
    return isset($brands[$brand]) ? $brands[$brand] : 'Cartão';
}

/**
 * Obter o logo da bandeira do cartão
 */
function get_card_brand_logo($brand) {
    $default_logo = get_template_directory_uri() . '/inc/custom/broker/assets/img/credit-card.png';
    
    // Verificar se o diretório de imagens existe
    $img_dir = get_template_directory() . '/inc/custom/broker/assets/img';
    if (!file_exists($img_dir)) {
        // Tentar criar o diretório se não existir
        if (!mkdir($img_dir, 0755, true)) {
            return $default_logo;
        }
    }
    
    $brand_logos = array(
        'visa' => 'visa.png',
        'mastercard' => 'mastercard.png',
        'amex' => 'amex.png',
        'elo' => 'elo.png',
        'hipercard' => 'hipercard.png',
        'discover' => 'discover.png',
        'diners' => 'diners.png',
        'jcb' => 'jcb.png',
        'aura' => 'aura.png',
        'hiper' => 'hiper.png'
    );
    
    // Verificar se a marca tem um logo definido
    if (isset($brand_logos[$brand])) {
        $logo_file = $img_dir . '/' . $brand_logos[$brand];
        
        // Verificar se o arquivo existe
        if (file_exists($logo_file)) {
            return get_template_directory_uri() . '/inc/custom/broker/assets/img/' . $brand_logos[$brand];
        }
        
        // Se chegou aqui, o arquivo não existe
        // Vamos criar um arquivo básico para a marca
        $logo_url = get_template_directory_uri() . '/inc/custom/broker/assets/img/' . $brand_logos[$brand];
        
        // Criar um placeholder para a marca
        create_card_brand_placeholder($brand, $img_dir . '/' . $brand_logos[$brand]);
        
        if (file_exists($logo_file)) {
            return $logo_url;
        }
    }
    
    // Se chegou aqui, não conseguiu encontrar ou criar um logo
    return $default_logo;
}

/**
 * Criar um placeholder para a marca do cartão
 */
function create_card_brand_placeholder($brand, $file_path) {
    // Verificar se o GD está disponível
    if (!function_exists('imagecreatetruecolor')) {
        return false;
    }
    
    // Criar uma imagem de 120x80
    $image = imagecreatetruecolor(120, 80);
    
    // Definir cores
    $bg_color = imagecolorallocate($image, 240, 240, 240);
    $text_color = imagecolorallocate($image, 50, 50, 50);
    
    // Preencher o fundo
    imagefill($image, 0, 0, $bg_color);
    
    // Adicionar texto
    $brand_name = get_card_brand_name($brand);
    imagestring($image, 5, 10, 30, $brand_name, $text_color);
    
    // Salvar imagem
    return imagepng($image, $file_path);
}

/**
 * Adiciona um novo cartão para o usuário
 */
function add_payment_card() {
    check_ajax_referer('payment_settings_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Usuário não autenticado');
    }
    
    $user_id = get_current_user_id();
    $card_data = isset($_POST['card_data']) ? $_POST['card_data'] : array();
    
    if (empty($card_data)) {
        wp_send_json_error('Dados do cartão não fornecidos');
    }
    
    // Simular processamento de cartão e tokenização
    $new_card = array(
        'id' => 'card_' . md5(time() . rand(1000, 9999)),
        'brand' => isset($card_data['brand']) ? sanitize_text_field($card_data['brand']) : 'visa',
        'last_four' => isset($card_data['last_four']) ? sanitize_text_field($card_data['last_four']) : '1234',
        'expiry_month' => isset($card_data['expiry_month']) ? sanitize_text_field($card_data['expiry_month']) : '12',
        'expiry_year' => isset($card_data['expiry_year']) ? sanitize_text_field($card_data['expiry_year']) : '2025',
    );
    
    // Adicionar cartão aos cartões salvos do usuário
    $saved_cards = get_user_meta($user_id, 'mercadopago_cards', true);
    if (empty($saved_cards)) {
        $saved_cards = array();
    }
    
    $saved_cards[] = $new_card;
    update_user_meta($user_id, 'mercadopago_cards', $saved_cards);
    
    // Se for o primeiro cartão, definir como padrão
    if (count($saved_cards) === 1) {
        update_user_meta($user_id, 'default_payment_card', $new_card['id']);
    }
    
    wp_send_json_success(array(
        'message' => 'Cartão adicionado com sucesso',
        'card' => $new_card
    ));
}
add_action('wp_ajax_add_payment_card', 'add_payment_card');

/**
 * Define um cartão como padrão
 */
function set_default_card() {
    // Debug
    error_log('Função set_default_card chamada');
    error_log('POST: ' . print_r($_POST, true));
    
    // Verifica o nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'payment_settings_nonce')) {
        error_log('Falha na verificação do nonce');
        wp_send_json_error('Falha na verificação de segurança. Por favor, atualize a página e tente novamente.');
        return;
    }
    
    if (!is_user_logged_in()) {
        error_log('Usuário não autenticado');
        wp_send_json_error('Usuário não autenticado');
        return;
    }
    
    // Validar campos obrigatórios
    if (!isset($_POST['card_id']) || empty($_POST['card_id'])) {
        error_log('ID do cartão não fornecido');
        wp_send_json_error('ID do cartão não fornecido');
        return;
    }
    
    $card_id = sanitize_text_field($_POST['card_id']);
    $user_id = get_current_user_id();
    
    // Obter os cartões existentes
    $saved_cards = get_user_meta($user_id, 'mercadopago_cards', true);
    
    if (!is_array($saved_cards) || empty($saved_cards)) {
        error_log('Nenhum cartão encontrado para o usuário');
        wp_send_json_error('Nenhum cartão encontrado');
        return;
    }
    
    // Verificar se o cartão existe
    if (!isset($saved_cards[$card_id])) {
        error_log('Cartão não encontrado: ' . $card_id);
        wp_send_json_error('Cartão não encontrado');
        return;
    }
    
    // Atualizar todos os cartões para não serem padrão
    foreach ($saved_cards as $id => $card) {
        $saved_cards[$id]['is_default'] = false;
    }
    
    // Definir o cartão selecionado como padrão
    $saved_cards[$card_id]['is_default'] = true;
    
    // Salvar as alterações
    update_user_meta($user_id, 'mercadopago_cards', $saved_cards);
    
    error_log('Cartão definido como padrão: ' . $card_id);
    
    // Retornar sucesso
    wp_send_json_success(array(
        'message' => 'Cartão definido como padrão com sucesso!',
        'card_id' => $card_id
    ));
}

/**
 * Remove um cartão
 */
function delete_card() {
    // Debug
    error_log('Função delete_card chamada');
    error_log('POST: ' . print_r($_POST, true));
    
    // Verifica o nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'payment_settings_nonce')) {
        error_log('Falha na verificação do nonce');
        wp_send_json_error('Falha na verificação de segurança. Por favor, atualize a página e tente novamente.');
        return;
    }
    
    if (!is_user_logged_in()) {
        error_log('Usuário não autenticado');
        wp_send_json_error('Usuário não autenticado');
        return;
    }
    
    // Validar campos obrigatórios
    if (!isset($_POST['card_id']) || empty($_POST['card_id'])) {
        error_log('ID do cartão não fornecido');
        wp_send_json_error('ID do cartão não fornecido');
        return;
    }
    
    $card_id = sanitize_text_field($_POST['card_id']);
    $user_id = get_current_user_id();
    
    // Obter os cartões existentes
    $saved_cards = get_user_meta($user_id, 'mercadopago_cards', true);
    
    if (!is_array($saved_cards) || empty($saved_cards)) {
        error_log('Nenhum cartão encontrado para o usuário');
        wp_send_json_error('Nenhum cartão encontrado');
        return;
    }
    
    // Verificar se o cartão existe
    if (!isset($saved_cards[$card_id])) {
        error_log('Cartão não encontrado: ' . $card_id);
        wp_send_json_error('Cartão não encontrado');
        return;
    }
    
    // Verificar se é o cartão padrão
    $is_default = isset($saved_cards[$card_id]['is_default']) && $saved_cards[$card_id]['is_default'];
    
    // Remover o cartão
    unset($saved_cards[$card_id]);
    
    // Se não houver mais cartões, atualizar meta
    if (empty($saved_cards)) {
        delete_user_meta($user_id, 'mercadopago_cards');
        error_log('Todos os cartões removidos para o usuário: ' . $user_id);
    } else {
        // Se o cartão removido era o padrão, definir o primeiro cartão como padrão
        if ($is_default && !empty($saved_cards)) {
            // Obter o primeiro cartão
            reset($saved_cards);
            $first_card_id = key($saved_cards);
            
            // Definir como padrão
            $saved_cards[$first_card_id]['is_default'] = true;
            error_log('Novo cartão padrão definido: ' . $first_card_id);
        }
        
        // Salvar as alterações
        update_user_meta($user_id, 'mercadopago_cards', $saved_cards);
    }
    
    error_log('Cartão removido: ' . $card_id);
    
    // Retornar sucesso
    wp_send_json_success(array(
        'message' => 'Cartão removido com sucesso!',
        'card_id' => $card_id
    ));
}

/**
 * Pausa uma assinatura
 */
function pause_subscription() {
    check_ajax_referer('payment_settings_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Usuário não autenticado');
    }
    
    $user_id = get_current_user_id();
    $subscription_id = isset($_POST['subscription_id']) ? sanitize_text_field($_POST['subscription_id']) : '';
    $immobile_id = isset($_POST['immobile_id']) ? intval($_POST['immobile_id']) : 0;
    
    if (empty($subscription_id) || empty($immobile_id)) {
        wp_send_json_error('Parâmetros inválidos');
    }
    
    // Verificar se o imóvel pertence ao usuário
    $broker_id = get_post_meta($immobile_id, 'broker', true);
    if ($broker_id != $user_id) {
        wp_send_json_error('Você não tem permissão para modificar este imóvel');
    }
    
    // Chamar a função de cancelamento da assinatura no Mercado Pago
    $cancel_result = cancel_mercadopago_subscription($subscription_id);
    
    if (!$cancel_result['success']) {
        wp_send_json_error('Erro ao cancelar a assinatura: ' . $cancel_result['message']);
    }
    
    // Atualizar meta dados do imóvel
    update_post_meta($immobile_id, 'is_sponsored', 'no');
    delete_post_meta($immobile_id, 'mercadopago_subscription_id');
    
    wp_send_json_success(array(
        'message' => 'Assinatura cancelada com sucesso'
    ));
}
add_action('wp_ajax_pause_subscription', 'pause_subscription');

/**
 * Cria o JavaScript para a página de configurações de pagamento
 */
function create_payment_settings_js() {
    $js_file = __DIR__ . '/assets/js/payment-settings.js';
    
    if (!file_exists(__DIR__ . '/assets/js')) {
        mkdir(__DIR__ . '/assets/js', 0755, true);
    }
    
    $js_content = <<<EOT
(function($) {
    $(document).ready(function() {
        // Inicializar SDK do Mercado Pago
        const mp = new MercadoPago(payment_settings.public_key, {
            locale: 'pt-BR'
        });
        
        let cardForm;
        
        // Mostrar formulário de novo cartão
        $('#add-new-card').on('click', function() {
            $('#card-form-container').show();
            $(this).hide();
            
            // Inicializar formulário de cartão
            cardForm = mp.cardForm({
                amount: "49.90",
                autoMount: true,
                form: {
                    id: "card-form",
                    cardholderName: {
                        id: "cardholderName",
                        placeholder: "Titular do cartão"
                    },
                    cardNumber: {
                        id: "cardNumberContainer",
                        placeholder: "Número do cartão"
                    },
                    expirationDate: {
                        id: "expirationDateContainer",
                        placeholder: "MM/YY"
                    },
                    securityCode: {
                        id: "securityCodeContainer",
                        placeholder: "CVV"
                    },
                    installments: {
                        id: "installments",
                        placeholder: "Parcelas"
                    },
                    identificationType: {
                        id: "identificationType"
                    },
                    identificationNumber: {
                        id: "identificationNumber",
                        placeholder: "Número do documento"
                    }
                },
                callbacks: {
                    onFormMounted: error => {
                        if (error) {
                            console.log("Form Mounted error: ", error);
                            showError("Erro ao montar o formulário: " + error);
                        }
                    },
                    onFormUnmounted: error => {
                        if (error) {
                            console.log("Form Unmounted error: ", error);
                        }
                    },
                    onIdentificationTypesReceived: (error, identificationTypes) => {
                        if (error) {
                            console.log("identificationTypes error: ", error);
                        }
                    },
                    onPaymentMethodsReceived: (error, paymentMethods) => {
                        if (error) {
                            console.log("paymentMethods error: ", error);
                        }
                    },
                    onIssuersReceived: (error, issuers) => {
                        if (error) {
                            console.log("issuers error: ", error);
                        }
                    },
                    onInstallmentsReceived: (error, installments) => {
                        if (error) {
                            console.log("installments error: ", error);
                        }
                    },
                    onCardTokenReceived: (error, token) => {
                        if (error) {
                            console.log("Token error: ", error);
                            showError("Erro ao processar o cartão: " + error);
                        }
                    },
                    onSubmit: event => {
                        event.preventDefault();
                    },
                    onFetching: (resource) => {
                        console.log("Fetching resource: ", resource);
                    },
                    onValidityChange: (error, field) => {
                        // Mudança no estado de validação de um campo
                    },
                    onError: (error) => {
                        console.log("Form error: ", error);
                        showError("Erro no formulário: " + error);
                    }
                }
            });
        });
        
        // Cancelar adição de cartão
        $('#cancel-card-form').on('click', function() {
            $('#card-form-container').hide();
            $('#add-new-card').show();
            $('#result-message').html('');
        });
        
        // Salvar novo cartão
        $('#save-card').on('click', function() {
            const formData = cardForm.getCardFormData();
            
            if (formData.validate) {
                // Obter token do cartão
                cardForm.createCardToken().then(function(token) {
                    saveCardToMercadoPago(token);
                }).catch(function(error) {
                    console.error("Error creating token:", error);
                    showError("Erro ao processar o cartão. Verifique os dados e tente novamente.");
                });
            } else {
                showError("Por favor, preencha corretamente todos os campos.");
            }
        });
        
        // Definir cartão como padrão
        $('.set-default-card').on('click', function() {
            const cardId = $(this).data('card-id');
            
            $.ajax({
                url: payment_settings.ajax_url,
                type: 'POST',
                data: {
                    action: 'set_default_card',
                    nonce: payment_settings.nonce,
                    card_id: cardId
                },
                success: function(response) {
                    if (response.success) {
                        window.location.reload();
                    } else {
                        showError(response.data || "Erro ao definir cartão como padrão.");
                    }
                },
                error: function() {
                    showError("Erro de comunicação ao definir cartão como padrão.");
                }
            });
        });
        
        // Remover cartão
        $('.delete-card').on('click', function() {
            const cardId = $(this).data('card-id');
            
            if (confirm("Tem certeza que deseja remover este cartão?")) {
                $.ajax({
                    url: payment_settings.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'delete_card',
                        nonce: payment_settings.nonce,
                        card_id: cardId
                    },
                    success: function(response) {
                        if (response.success) {
                            window.location.reload();
                        } else {
                            showError(response.data || "Erro ao remover cartão.");
                        }
                    },
                    error: function() {
                        showError("Erro de comunicação ao remover cartão.");
                    }
                });
            }
        });
        
        // Função para salvar o cartão no Mercado Pago
        function saveCardToMercadoPago(token) {
            $.ajax({
                url: payment_settings.ajax_url,
                type: 'POST',
                data: {
                    action: 'save_card',
                    nonce: payment_settings.nonce,
                    token: token,
                    user_id: payment_settings.user_id
                },
                success: function(response) {
                    if (response.success) {
                        showSuccess("Cartão salvo com sucesso!");
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                    } else {
                        showError(response.data || "Erro ao salvar cartão.");
                    }
                },
                error: function() {
                    showError("Erro de comunicação ao salvar cartão.");
                }
            });
        }
        
        // Mostrar mensagem de erro
        function showError(message) {
            $('#result-message').html('<div class="error-message">' + message + '</div>');
        }
        
        // Mostrar mensagem de sucesso
        function showSuccess(message) {
            $('#result-message').html('<div class="success-message">' + message + '</div>');
        }
    });
})(jQuery);
EOT;
    
    file_put_contents($js_file, $js_content);
}

// Criar o arquivo JavaScript se ainda não existir
if (!file_exists(__DIR__ . '/assets/js/payment-settings.js')) {
    create_payment_settings_js();
}

/**
 * Obtém a configuração do Mercado Pago
 * 
 * @return array Configuração do Mercado Pago
 */
function get_mercadopago_config() {
    // Verificar se estamos no modo sandbox
    $sandbox = get_option('mercadopago_sandbox', 'yes') === 'yes';
    
    // Obter chaves de acordo com o modo
    if ($sandbox) {
        $public_key = get_option('mercadopago_test_public_key', '');
        $access_token = get_option('mercadopago_test_access_token', '');
    } else {
        $public_key = get_option('mercadopago_prod_public_key', '');
        $access_token = get_option('mercadopago_prod_access_token', '');
    }
    
    // Usar valores padrão se não houver configuração
    if (empty($public_key)) {
        $public_key = 'TEST-70b46d06-add9-499a-942e-0f5c01b8769a';
    }
    
    if (empty($access_token)) {
        $access_token = 'TEST-1105123470040162-010319-784660b8cba90a127251b50a9e066db6-242756635';
    }
    
    // Retornar configuração
    return [
        'sandbox' => $sandbox,
        'public_key' => $public_key,
        'access_token' => $access_token
    ];
}

/**
 * Registra a página de configurações do Mercado Pago no menu de administração
 */
function register_mercadopago_admin_menu() {
    add_submenu_page(
        'options-general.php',
        'Configurações do Mercado Pago',
        'Mercado Pago',
        'manage_options',
        'mercadopago_settings',
        'render_mercadopago_settings_page'
    );
    
    // Registrar configurações
    register_setting('mercadopago_settings', 'mercadopago_sandbox');
    register_setting('mercadopago_settings', 'mercadopago_test_public_key');
    register_setting('mercadopago_settings', 'mercadopago_test_access_token');
    register_setting('mercadopago_settings', 'mercadopago_prod_public_key');
    register_setting('mercadopago_settings', 'mercadopago_prod_access_token');
}
add_action('admin_menu', 'register_mercadopago_admin_menu');

/**
 * Renderiza a página de configurações do Mercado Pago
 */
function render_mercadopago_settings_page() {
    // Verificar permissões
    if (!current_user_can('manage_options')) {
        wp_die('Você não tem permissão para acessar esta página.');
    }
    
    // Obter configurações atuais
    $sandbox = get_option('mercadopago_sandbox', 'yes') === 'yes';
    $test_public_key = get_option('mercadopago_test_public_key', '');
    $test_access_token = get_option('mercadopago_test_access_token', '');
    $prod_public_key = get_option('mercadopago_prod_public_key', '');
    $prod_access_token = get_option('mercadopago_prod_access_token', '');
    
    // Renderizar página
    ?>
    <div class="wrap">
        <h1>Configurações do Mercado Pago</h1>
        
        <form method="post" action="options.php">
            <?php settings_fields('mercadopago_settings'); ?>
            <?php do_settings_sections('mercadopago_settings'); ?>
            
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Modo de Ambiente</th>
                    <td>
                        <label>
                            <input type="checkbox" name="mercadopago_sandbox" value="yes" <?php checked($sandbox, true); ?> />
                            Ativar modo de teste (sandbox)
                        </label>
                        <p class="description">
                            Quando ativado, o sistema usará as credenciais de teste. Desative para ambiente de produção.
                        </p>
                    </td>
                </tr>
                
                <tr valign="top">
                    <th scope="row" colspan="2">
                        <h3 style="margin: 0; padding: 10px 0; border-bottom: 1px solid #ccc;">Credenciais de Teste (Sandbox)</h3>
                    </th>
                </tr>
                
                <tr valign="top">
                    <th scope="row">Chave Pública de Teste</th>
                    <td>
                        <input type="text" name="mercadopago_test_public_key" value="<?php echo esc_attr($test_public_key); ?>" class="regular-text" />
                        <p class="description">
                            Chave pública do Mercado Pago para ambiente de teste (começa com TEST-).
                        </p>
                    </td>
                </tr>
                
                <tr valign="top">
                    <th scope="row">Token de Acesso de Teste</th>
                    <td>
                        <input type="text" name="mercadopago_test_access_token" value="<?php echo esc_attr($test_access_token); ?>" class="regular-text" />
                        <p class="description">
                            Token de acesso do Mercado Pago para ambiente de teste (começa com TEST-).
                        </p>
                    </td>
                </tr>
                
                <tr valign="top">
                    <th scope="row" colspan="2">
                        <h3 style="margin: 0; padding: 10px 0; border-bottom: 1px solid #ccc;">Credenciais de Produção</h3>
                    </th>
                </tr>
                
                <tr valign="top">
                    <th scope="row">Chave Pública de Produção</th>
                    <td>
                        <input type="text" name="mercadopago_prod_public_key" value="<?php echo esc_attr($prod_public_key); ?>" class="regular-text" />
                        <p class="description">
                            Chave pública do Mercado Pago para ambiente de produção (começa com APP_USR-).
                        </p>
                    </td>
                </tr>
                
                <tr valign="top">
                    <th scope="row">Token de Acesso de Produção</th>
                    <td>
                        <input type="text" name="mercadopago_prod_access_token" value="<?php echo esc_attr($prod_access_token); ?>" class="regular-text" />
                        <p class="description">
                            Token de acesso do Mercado Pago para ambiente de produção (começa com APP_USR-).
                        </p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button('Salvar Configurações'); ?>
        </form>
        
        <div class="card" style="max-width: 800px; margin-top: 20px; padding: 20px;">
            <h3>Como obter suas credenciais do Mercado Pago</h3>
            <ol>
                <li>Acesse sua conta no <a href="https://www.mercadopago.com.br/" target="_blank">Mercado Pago</a></li>
                <li>Vá para a seção "Developers" ou "Desenvolvedores"</li>
                <li>Acesse "Credenciais"</li>
                <li>Você encontrará as credenciais de teste e produção</li>
            </ol>
            <p>Para ambiente de produção, você precisará ativar suas credenciais de produção seguindo o processo de homologação do Mercado Pago.</p>
            <p><a href="https://www.mercadopago.com.br/developers/pt/guides/overview#credentials" target="_blank" class="button">Ver documentação do Mercado Pago</a></p>
        </div>
    </div>
    <?php
}

/**
 * Processa o salvamento de cartão em ambiente de produção
 */
function process_save_card() {
    check_ajax_referer('payment_settings_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        error_log("Tentativa de salvar cartão sem estar autenticado");
        wp_send_json_error(['message' => 'Usuário não autenticado']);
        return;
    }
    
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : get_current_user_id();
    $token = isset($_POST['token']) ? sanitize_text_field($_POST['token']) : '';
    
    if (empty($token)) {
        error_log("Tentativa de salvar cartão sem token");
        wp_send_json_error(['message' => 'Token do cartão não fornecido']);
        return;
    }
    
    error_log("Iniciando processamento de token do cartão: " . $token);
    
    // Verificar se o arquivo mercadopago.php existe
    $mercadopago_file = get_stylesheet_directory() . '/inc/custom/immobile/mercadopago.php';
    if (!file_exists($mercadopago_file)) {
        error_log("Arquivo mercadopago.php não encontrado em: " . $mercadopago_file);
        wp_send_json_error(['message' => 'Configuração do módulo de pagamento não encontrada']);
        return;
    }
    
    require_once $mercadopago_file;
    
    try {
        // Verificar se a classe existe
        if (!class_exists('Immobile_Payment')) {
            error_log("Classe Immobile_Payment não encontrada");
            throw new Exception('Classe de pagamento não encontrada');
        }
        
        // Obter informações do cartão a partir do token
        $mp_payment = new Immobile_Payment();
        $mp_config = get_mercadopago_config();
        
        error_log("Configuração do Mercado Pago: " . json_encode([
            'sandbox' => $mp_config['sandbox'],
            'token_prefix' => substr($mp_config['access_token'], 0, 10),
            'public_key_prefix' => substr($mp_config['public_key'], 0, 10)
        ]));
        
        if (empty($mp_config['access_token'])) {
            error_log("Token de acesso do Mercado Pago não configurado");
            throw new Exception('Token de acesso do Mercado Pago não configurado');
        }
        
        // Em ambiente de testes, podemos simular os dados do cartão
        if ($mp_config['sandbox'] && strpos($token, 'TEST-') === 0) {
            error_log("Ambiente de teste detectado com token válido: " . $token);
            
            // Criar cartão simulado para ambiente de teste
            $card_data = [
                'payment_method' => [
                    'id' => 'master',
                    'name' => 'Mastercard Teste'
                ],
                'last_four_digits' => '1234',
                'expiration_month' => '12',
                'expiration_year' => '2030'
            ];
        } else {
            // Fazer uma requisição para a API do Mercado Pago para obter os detalhes do cartão
            error_log("Fazendo requisição para API do Mercado Pago para obter detalhes do cartão");
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://api.mercadopago.com/v1/payment_methods/card_tokens/" . $token);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "Authorization: Bearer " . $mp_config['access_token'],
            ));
            
            $response = curl_exec($ch);
            $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);
            
            if ($err) {
                error_log("Erro de CURL ao obter informações do cartão: " . $err);
                throw new Exception('Erro ao obter informações do cartão: ' . $err);
            }
            
            if ($http_status != 200) {
                error_log("Erro na API do Mercado Pago: HTTP status " . $http_status . ", Resposta: " . $response);
                throw new Exception('Erro na API do Mercado Pago: ' . $http_status);
            }
            
            $card_data = json_decode($response, true);
            
            if (empty($card_data)) {
                error_log("Resposta vazia da API do Mercado Pago");
                throw new Exception('Resposta vazia da API do Mercado Pago');
            }
            
            if (isset($card_data['error'])) {
                error_log("Erro retornado pela API do Mercado Pago: " . json_encode($card_data));
                throw new Exception('Erro retornado pela API: ' . $card_data['error']);
            }
            
            // Verificar se temos os dados necessários do cartão
            if (!isset($card_data['payment_method']) || !isset($card_data['last_four_digits'])) {
                error_log("Dados do cartão incompletos na resposta da API: " . json_encode($card_data));
                throw new Exception('Dados do cartão incompletos na resposta da API');
            }
        }
        
        // Verificar campos de expiração
        $expiry_month = isset($card_data['expiration_month']) ? $card_data['expiration_month'] : '';
        $expiry_year = isset($card_data['expiration_year']) ? $card_data['expiration_year'] : '';
        
        // Pegar o nome da bandeira do cartão
        $brand = isset($card_data['payment_method']['id']) ? $card_data['payment_method']['id'] : '';
        $brand_name = isset($card_data['payment_method']['name']) ? $card_data['payment_method']['name'] : $brand;
        
        // Salvar o cartão no usuário
        $saved_cards = get_user_meta($user_id, 'mercadopago_cards', true);
        if (empty($saved_cards) || !is_array($saved_cards)) {
            $saved_cards = array();
        }
        
        $new_card = array(
            'id' => 'card_' . md5(time() . rand(1000, 9999)),
            'token' => $token,
            'brand' => $brand_name,
            'last_four' => $card_data['last_four_digits'],
            'expiry_month' => $expiry_month,
            'expiry_year' => $expiry_year,
            'created_at' => current_time('mysql'),
            'is_test' => $mp_config['sandbox'] ? true : false
        );
        
        error_log("Salvando novo cartão: " . json_encode($new_card));
        
        $saved_cards[] = $new_card;
        update_user_meta($user_id, 'mercadopago_cards', $saved_cards);
        
        // Se for o primeiro cartão, definir como padrão
        if (count($saved_cards) === 1) {
            update_user_meta($user_id, 'default_payment_card', $new_card['id']);
        }
        
        wp_send_json_success(array(
            'message' => 'Cartão salvo com sucesso',
            'card' => $new_card
        ));
        
    } catch (Exception $e) {
        error_log('Erro ao processar cartão: ' . $e->getMessage());
        wp_send_json_error(['message' => 'Erro ao processar cartão: ' . $e->getMessage()]);
    }
}
add_action('wp_ajax_save_card', 'process_save_card');

/**
 * Obtém as transações recentes do usuário
 */
function get_user_recent_transactions($user_id) {
    global $wpdb;
    
    $transactions = array();
    
    // Buscar dados de pagamentos existentes
    $payment_data = $wpdb->get_results($wpdb->prepare(
        "SELECT meta_value, post_id FROM {$wpdb->postmeta} 
         WHERE meta_key = 'payment_data' 
         AND post_id IN (
             SELECT post_id FROM {$wpdb->postmeta} 
             WHERE meta_key = 'broker' 
             AND meta_value = %d
         )",
        $user_id
    ));
    
    if (!empty($payment_data)) {
        foreach ($payment_data as $data) {
            $payment_info = maybe_unserialize($data->meta_value);
            if (is_array($payment_info) && isset($payment_info['date'])) {
                $immobile = get_post($data->post_id);
                $transactions[] = array(
                    'id' => isset($payment_info['id']) ? $payment_info['id'] : 'payment_' . uniqid(),
                    'date' => $payment_info['date'],
                    'description' => isset($payment_info['description']) ? $payment_info['description'] : 'Pagamento - ' . ($immobile ? $immobile->post_title : 'Imóvel #' . $data->post_id),
                    'amount' => isset($payment_info['amount']) ? $payment_info['amount'] : 49.90,
                    'status' => isset($payment_info['status']) ? $payment_info['status'] : 'approved'
                );
            }
        }
    }
    
    // Em ambiente de produção, não adiciona exemplos de transações
    // Ordenar por data (mais recente primeiro)
    usort($transactions, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    
    return $transactions;
}

/**
 * Adiciona dados simulados de cartão para ambiente de teste
 * Função desativada no ambiente de produção
 */
function add_simulated_card_data() {
    // Em produção, não permitimos dados de teste
    wp_send_json_error('Não é permitido adicionar cartões de teste no ambiente de produção.');
    return;
    
    // Código anterior comentado para referência futura
    /*
    // Debug
    error_log('Função add_simulated_card_data chamada');
    error_log('POST: ' . print_r($_POST, true));
    
    // Verifica o nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'payment_settings_nonce')) {
        error_log('Falha na verificação do nonce');
        wp_send_json_error('Falha na verificação de segurança. Por favor, atualize a página e tente novamente.');
        return;
    }
    
    if (!is_user_logged_in()) {
        error_log('Usuário não autenticado');
        wp_send_json_error('Usuário não autenticado');
        return;
    }
    
    // Validar campos obrigatórios
    if (!isset($_POST['card_name']) || !isset($_POST['card_number']) || !isset($_POST['expiry_month']) || !isset($_POST['expiry_year'])) {
        error_log('Campos obrigatórios não fornecidos');
        wp_send_json_error('Por favor, preencha todos os campos obrigatórios.');
        return;
    }
    */
}
add_action('wp_ajax_add_simulated_card', 'add_simulated_card_data');

/**
 * Adiciona estilos CSS para o formulário de pagamento
 */
function payment_settings_styles() {
    if (!is_page('configuracoes-pagamento')) {
        return;
    }
    
    // Estilos inline
    ?>
    <style>
        .payment-settings-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .payment-settings-section {
            margin-bottom: 40px;
        }

        /* Estilos para o formulário do MercadoPago */
        .mp-form {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-top: 20px;
        }

        .mp-form-row {
            margin-bottom: 20px;
        }

        .mp-col-12 {
            width: 100%;
        }

        .mp-col-6 {
            width: 48%;
            display: inline-block;
            margin-right: 2%;
        }

        .mp-col-6:last-child {
            margin-right: 0;
        }

        .mp-input-container {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 8px;
            background: #fff;
            min-height: 40px;
        }

        .mp-form label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .mp-form input[type="text"],
        .mp-form select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .mp-form select {
            height: 40px;
            background: #fff;
        }

        .payment-submit-button {
            width: 100%;
            padding: 12px 24px;
            background: #0056b3;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .payment-submit-button:hover {
            background: #004494;
        }

        .payment-submit-button:disabled {
            background: #cccccc;
            cursor: not-allowed;
        }

        .error-message {
            color: #dc3545;
            padding: 10px;
            margin-top: 10px;
            border: 1px solid #dc3545;
            border-radius: 4px;
            background: #fff;
        }

        /* Estilos para cartões salvos */
        .cards-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 20px;
        }

        .card-item {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
            width: calc(33% - 20px);
        }

        .card-item.default {
            border: 2px solid #0056b3;
        }

        .default-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #0056b3;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
        }

        .card-brand {
            margin-bottom: 10px;
        }

        .card-brand img {
            height: 30px;
            width: auto;
        }

        .card-number {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .card-expiry {
            color: #666;
            font-size: 14px;
        }

        .card-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }

        .card-actions button {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }

        .set-default-card {
            background: #28a745;
            color: white;
        }

        .delete-card {
            background: #dc3545;
            color: white;
        }

        .set-default-card:hover {
            background: #218838;
        }

        .delete-card:hover {
            background: #c82333;
        }

        @media (max-width: 768px) {
            .mp-col-6 {
                width: 100%;
                margin-right: 0;
                margin-bottom: 15px;
            }

            .cards-container {
                flex-direction: column;
            }
        }
    </style>
    <?php
}
add_action('wp_head', 'payment_settings_styles');

/**
 * Inicializa as configurações de pagamento
 */
function init_payment_settings() {
    // Registrar ações AJAX
    if (!has_action('wp_ajax_add_simulated_card', 'add_simulated_card_data')) {
        add_action('wp_ajax_add_simulated_card', 'add_simulated_card_data');
        error_log('Ação AJAX add_simulated_card registrada');
    }
    
    if (!has_action('wp_ajax_set_default_card', 'set_default_card')) {
        add_action('wp_ajax_set_default_card', 'set_default_card');
        error_log('Ação AJAX set_default_card registrada');
    }
    
    if (!has_action('wp_ajax_delete_card', 'delete_card')) {
        add_action('wp_ajax_delete_card', 'delete_card');
        error_log('Ação AJAX delete_card registrada');
    }
    
    // Registrar scripts e estilos
    add_action('wp_enqueue_scripts', 'register_payment_settings_assets');
}
add_action('init', 'init_payment_settings');

/**
 * Registra os scripts e estilos para as configurações de pagamento
 */
function register_payment_settings_assets() {
    if (is_page('payment-settings') || is_page('configuracoes-pagamento') || is_page('configuracoes-de-pagamento')) {
        // Registrar e enfileirar o CSS
        wp_register_style('payment-settings-css', 
                          get_template_directory_uri() . '/inc/custom/broker/assets/css/payment-settings.css', 
                          array(), 
                          '1.0.0');
        wp_enqueue_style('payment-settings-css');
        
        // Enfileirar o SDK do Mercado Pago antes do nosso script
        wp_enqueue_script('mercadopago-sdk', 'https://sdk.mercadopago.com/js/v2', array(), null, true);
        
        // Registrar e enfileirar o JavaScript
        wp_register_script('payment-settings-js', 
                          get_template_directory_uri() . '/inc/custom/broker/assets/js/payment-settings.js', 
                          array('jquery', 'mercadopago-sdk'), 
                          time(), 
                          true);
        
        // Obter configuração do Mercado Pago
        $mp_config = get_mercadopago_config();
        
        // Localizar o script com dados necessários
        wp_localize_script('payment-settings-js', 'payment_settings', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('payment_settings_nonce'),
            'home_url' => home_url(),
            'is_test_environment' => true,
            'public_key' => $mp_config['public_key'],
            'user_id' => get_current_user_id()
        ));
        
        wp_enqueue_script('payment-settings-js');
    }
}

/**
 * Processa a adição manual de cartão
 */
function add_manual_card() {
    // Verificar nonce
    check_ajax_referer('payment_settings_nonce', 'nonce');
    
    // Verificar autenticação
    if (!is_user_logged_in()) {
        wp_send_json_error('Usuário não autenticado');
        return;
    }
    
    // Obter e validar dados
    $user_id = get_current_user_id();
    $card_name = isset($_POST['card_name']) ? sanitize_text_field($_POST['card_name']) : '';
    $card_number = isset($_POST['card_number']) ? preg_replace('/\D/', '', $_POST['card_number']) : '';
    $expiry_month = isset($_POST['expiry_month']) ? sanitize_text_field($_POST['expiry_month']) : '';
    $expiry_year = isset($_POST['expiry_year']) ? sanitize_text_field($_POST['expiry_year']) : '';
    $security_code = isset($_POST['security_code']) ? preg_replace('/\D/', '', $_POST['security_code']) : '';
    $identification_type = isset($_POST['identification_type']) ? sanitize_text_field($_POST['identification_type']) : '';
    $identification_number = isset($_POST['identification_number']) ? preg_replace('/\D/', '', $_POST['identification_number']) : '';
    
    // Validar campos obrigatórios
    if (empty($card_name) || empty($card_number) || empty($expiry_month) || empty($expiry_year) || empty($security_code)) {
        wp_send_json_error('Todos os campos são obrigatórios');
        return;
    }
    
    // Validar número do cartão
    if (strlen($card_number) < 13 || strlen($card_number) > 19) {
        wp_send_json_error('Número de cartão inválido');
        return;
    }
    
    // Validar código de segurança
    if (strlen($security_code) < 3 || strlen($security_code) > 4) {
        wp_send_json_error('Código de segurança inválido');
        return;
    }
    
    // Validar documento
    if ($identification_type === 'CPF' && strlen($identification_number) !== 11) {
        wp_send_json_error('CPF inválido');
        return;
    } else if ($identification_type === 'CNPJ' && strlen($identification_number) !== 14) {
        wp_send_json_error('CNPJ inválido');
        return;
    }
    
    // Detectar bandeira do cartão com base nos primeiros dígitos
    $brand = get_card_brand_from_number($card_number);
    
    // Obter os últimos 4 dígitos
    $last_four = substr($card_number, -4);
    
    // Gerar ID único para o cartão
    $card_id = 'card_' . md5($card_number . time() . rand(1000, 9999));
    
    // Preparar dados do cartão
    $new_card = array(
        'id' => $card_id,
        'token' => $card_id, // Usamos o mesmo ID como token
        'brand' => $brand,
        'last_four' => $last_four,
        'expiry_month' => $expiry_month,
        'expiry_year' => $expiry_year,
        'cardholder_name' => $card_name,
        'identification_type' => $identification_type,
        'identification_number' => $identification_number
    );
    
    // Salvar o cartão no usuário
    $saved_cards = get_user_meta($user_id, 'mercadopago_cards', true);
    if (empty($saved_cards) || !is_array($saved_cards)) {
        $saved_cards = array();
    }
    
    $saved_cards[$card_id] = $new_card;
    update_user_meta($user_id, 'mercadopago_cards', $saved_cards);
    
    // Se for o primeiro cartão, definir como padrão
    if (count($saved_cards) === 1) {
        update_user_meta($user_id, 'default_payment_card', $card_id);
    }
    
    // Enviar resposta de sucesso
    wp_send_json_success(array(
        'message' => 'Cartão adicionado com sucesso!',
        'card_id' => $card_id
    ));
}
add_action('wp_ajax_add_manual_card', 'add_manual_card');

/**
 * Determina a bandeira do cartão com base nos primeiros dígitos
 */
function get_card_brand_from_number($card_number) {
    // Limpar o número do cartão
    $number = preg_replace('/\D/', '', $card_number);
    
    // Visa: começa com 4
    if (preg_match('/^4/', $number)) {
        return 'visa';
    }
    
    // Mastercard: começa com 5 seguido de 1-5, ou começa com 2 seguido de 2-7
    if (preg_match('/^5[1-5]/', $number) || preg_match('/^2[2-7]/', $number)) {
        return 'mastercard';
    }
    
    // American Express: começa com 34 ou 37
    if (preg_match('/^3[47]/', $number)) {
        return 'amex';
    }
    
    // Discover: começa com 6011, 622126-622925, 644-649, 65
    if (preg_match('/^6011/', $number) || 
        preg_match('/^622(12[6-9]|1[3-9]|[2-8]|9[0-1][0-9]|92[0-5])/', $number) ||
        preg_match('/^6[4-5]/', $number)) {
        return 'discover';
    }
    
    // Elo: começa com 4011, 438935, 451416, 457631, 504175, etc.
    if (preg_match('/^(4011|438935|451416|457631|504175|627780|636297)/', $number)) {
        return 'elo';
    }
    
    // Hipercard: começa com 606282
    if (preg_match('/^606282/', $number)) {
        return 'hipercard';
    }
    
    // Padrão para outros cartões
    return 'generic';
}

/**
 * Ajax handler para obter os cartões salvos do usuário
 */
function get_user_saved_cards_ajax() {
    // Verificar autenticação
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Usuário não autenticado'));
        return;
    }
    
    // Verificar nonce (opcional)
    // if (isset($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'payment_nonce')) {
    //     wp_send_json_error(array('message' => 'Erro de segurança'));
    //     return;
    // }
    
    $user_id = get_current_user_id();
    $cards = get_user_mercadopago_cards($user_id);
    $default_card_id = get_user_meta($user_id, 'default_payment_card', true);
    
    // Preparar dados para resposta JSON
    $cards_data = array();
    foreach ($cards as $id => $card) {
        $cards_data[] = array(
            'id' => $id,
            'brand' => isset($card['brand']) ? $card['brand'] : 'unknown',
            'last_four' => isset($card['last_four']) ? $card['last_four'] : '****',
            'expiry_month' => isset($card['expiry_month']) ? $card['expiry_month'] : '**',
            'expiry_year' => isset($card['expiry_year']) ? $card['expiry_year'] : '****',
            'is_default' => ($id === $default_card_id)
        );
    }
    
    wp_send_json_success(array(
        'cards' => $cards_data,
        'default_card' => $default_card_id
    ));
}
add_action('wp_ajax_get_user_saved_cards', 'get_user_saved_cards_ajax');

/**
 * Registra um widget no dashboard para verificar o status da integração do Mercado Pago
 */
function register_mercadopago_status_dashboard_widget() {
    wp_add_dashboard_widget(
        'mercadopago_status_widget',
        'Status da Integração do Mercado Pago',
        'render_mercadopago_status_widget'
    );
}
add_action('wp_dashboard_setup', 'register_mercadopago_status_dashboard_widget');

/**
 * Renderiza o widget de status da integração do Mercado Pago
 */
function render_mercadopago_status_widget() {
    // Obter configuração
    $mp_config = get_mercadopago_config();
    $sandbox = $mp_config['sandbox'];
    $public_key = $mp_config['public_key'];
    $access_token = $mp_config['access_token'];
    
    // Verificar status
    $has_public_key = !empty($public_key);
    $has_access_token = !empty($access_token);
    $is_test_key = strpos($public_key, 'TEST-') === 0;
    $is_test_token = strpos($access_token, 'TEST-') === 0;
    
    // Verificar consistência
    $is_consistent = ($sandbox && $is_test_key && $is_test_token) || 
                     (!$sandbox && strpos($public_key, 'APP_USR-') === 0 && strpos($access_token, 'APP_USR-') === 0);
    
    // Status geral
    $status = ($has_public_key && $has_access_token && $is_consistent) ? 'ok' : 'error';
    
    // Exibir status
    ?>
    <div class="mp-status-widget">
        <div class="mp-status-overview">
            <div class="mp-status-icon <?php echo $status; ?>">
                <?php if ($status === 'ok'): ?>
                <span class="dashicons dashicons-yes-alt"></span>
                <?php else: ?>
                <span class="dashicons dashicons-warning"></span>
                <?php endif; ?>
            </div>
            <div class="mp-status-text">
                <h3>Status: <?php echo $status === 'ok' ? 'Configurado corretamente' : 'Configuração incompleta'; ?></h3>
                <p>Modo: <strong><?php echo $sandbox ? 'Teste (Sandbox)' : 'Produção'; ?></strong></p>
            </div>
        </div>
        
        <table class="widefat mp-status-table">
            <tbody>
                <tr>
                    <td>Chave Pública</td>
                    <td class="<?php echo $has_public_key ? 'ok' : 'error'; ?>">
                        <?php 
                        if ($has_public_key) {
                            echo '<span class="dashicons dashicons-yes"></span> ';
                            echo substr($public_key, 0, 8) . '...' . substr($public_key, -5);
                        } else {
                            echo '<span class="dashicons dashicons-no"></span> Não configurada';
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td>Token de Acesso</td>
                    <td class="<?php echo $has_access_token ? 'ok' : 'error'; ?>">
                        <?php 
                        if ($has_access_token) {
                            echo '<span class="dashicons dashicons-yes"></span> ';
                            echo substr($access_token, 0, 8) . '...' . substr($access_token, -5);
                        } else {
                            echo '<span class="dashicons dashicons-no"></span> Não configurado';
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td>Consistência das Chaves</td>
                    <td class="<?php echo $is_consistent ? 'ok' : 'error'; ?>">
                        <?php 
                        if ($is_consistent) {
                            echo '<span class="dashicons dashicons-yes"></span> Chaves consistentes com o modo';
                        } else {
                            echo '<span class="dashicons dashicons-no"></span> Chaves incompatíveis com o modo selecionado';
                        }
                        ?>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <div class="mp-status-actions">
            <a href="<?php echo admin_url('options-general.php?page=mercadopago_settings'); ?>" class="button button-primary">Configurar Mercado Pago</a>
            <a href="<?php echo admin_url('admin.php?page=payment_test'); ?>" class="button">Testar Integração</a>
        </div>
    </div>
    
    <style>
    .mp-status-widget {
        margin: -11px -12px;
    }
    .mp-status-overview {
        display: flex;
        align-items: center;
        padding: 15px;
        background: #f9f9f9;
        border-bottom: 1px solid #e1e1e1;
    }
    .mp-status-icon {
        font-size: 2em;
        margin-right: 10px;
    }
    .mp-status-icon.ok {
        color: #46b450;
    }
    .mp-status-icon.error {
        color: #dc3232;
    }
    .mp-status-text h3 {
        margin: 0 0 5px 0;
    }
    .mp-status-text p {
        margin: 0;
    }
    .mp-status-table {
        margin-top: 15px;
    }
    .mp-status-table td {
        padding: 8px 15px;
    }
    .mp-status-table td.ok {
        color: #46b450;
    }
    .mp-status-table td.error {
        color: #dc3232;
    }
    .mp-status-actions {
        margin: 15px;
        display: flex;
        gap: 10px;
    }
    </style>
    <?php
}