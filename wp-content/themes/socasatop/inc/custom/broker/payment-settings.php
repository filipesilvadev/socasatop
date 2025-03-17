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
    // Verificar se o usuário está logado
    if (!is_user_logged_in()) {
        return '<div class="notice notice-error">É necessário estar logado para acessar as configurações de pagamento.</div>';
    }
    
    $user_id = get_current_user_id();
    $cards = get_user_mercadopago_cards($user_id);
    $subscriptions = get_user_mercadopago_subscriptions($user_id);
    $default_card_id = get_user_meta($user_id, 'default_payment_card', true);
    
    // Carregar o SDK do Mercado Pago
    $mp_config = get_mercadopago_config();
    
    // Enqueue scripts
    wp_enqueue_script('mercadopago-sdk', 'https://sdk.mercadopago.com/js/v2', array(), null, true);
    wp_enqueue_script('payment-settings-js', get_template_directory_uri() . '/inc/custom/broker/assets/js/payment-settings.js', array('jquery', 'mercadopago-sdk'), time(), true);
    
    // Passar variáveis para o JavaScript
    wp_localize_script('payment-settings-js', 'payment_settings', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('payment_settings_nonce'),
        'public_key' => $mp_config['public_key'],
        'user_id' => $user_id
    ));
    
    // Iniciar buffer de saída
    ob_start();
    ?>
    <div class="payment-settings-container">
        <h2>Configurações de Pagamento</h2>
        
        <div class="payment-settings-section">
            <h3>Métodos de Pagamento</h3>
            <p>Configure seus métodos de pagamento preferidos.</p>
            
            <div class="card-management">
                <?php if (!empty($cards)) : ?>
                    <div class="saved-cards">
                        <h4>Cartões Salvos</h4>
                        <div class="cards-grid">
                            <?php foreach ($cards as $card) : ?>
                                <div class="card-item <?php echo ($card['id'] === $default_card_id) ? 'default-card' : ''; ?>">
                                    <div class="card-header">
                                        <div class="card-brand">
                                            <img src="<?php echo get_card_brand_logo($card['brand']); ?>" alt="<?php echo esc_attr($card['brand']); ?>">
                                        </div>
                                        <?php if ($card['id'] === $default_card_id) : ?>
                                            <span class="default-badge">Padrão</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-body">
                                        <div class="card-number">•••• •••• •••• <?php echo esc_html($card['last_four']); ?></div>
                                        <div class="card-expiry">Validade: <?php echo esc_html($card['expiry_month']); ?>/<?php echo esc_html($card['expiry_year']); ?></div>
                                    </div>
                                    <div class="card-actions">
                                        <?php if ($card['id'] !== $default_card_id) : ?>
                                            <button class="set-default-card" data-card-id="<?php echo esc_attr($card['id']); ?>">Definir como padrão</button>
                                        <?php endif; ?>
                                        <button class="delete-card" data-card-id="<?php echo esc_attr($card['id']); ?>">Remover</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="add-card-section">
                    <button id="add-new-card" class="button button-primary">Adicionar novo cartão</button>
                    
                    <div id="card-form-container" style="display: none;">
                        <h4>Novo Cartão</h4>
                        <form id="card-form" class="mp-form">
                            <div class="mp-form-row">
                                <div class="mp-col-12">
                                    <label for="cardholderName">Nome no cartão</label>
                                    <input type="text" id="cardholderName" data-checkout="cardholderName" />
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
                                <div class="mp-col-6">
                                    <label for="identificationType">Tipo de documento</label>
                                    <select id="identificationType" data-checkout="identificationType"></select>
                                </div>
                                <div class="mp-col-6">
                                    <label for="identificationNumber">Número do documento</label>
                                    <input type="text" id="identificationNumber" data-checkout="identificationNumber" />
                                </div>
                            </div>
                            
                            <div id="result-message"></div>
                            
                            <div class="mp-form-actions">
                                <button type="button" id="save-card" class="button button-primary">Salvar cartão</button>
                                <button type="button" id="cancel-card-form" class="button">Cancelar</button>
                            </div>
                        </form>
                    </div>
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
        padding: 20px;
        background: #fff;
        border-radius: 5px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }
    
    .payment-settings-section {
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 1px solid #eee;
    }
    
    .payment-settings-section:last-child {
        border-bottom: none;
        margin-bottom: 0;
    }
    
    .notice {
        padding: 10px 15px;
        margin: 15px 0;
        border-radius: 3px;
    }
    
    .notice-info {
        background-color: #e5f5fa;
        border-left: 4px solid #00a0d2;
    }
    
    .notice-error {
        background-color: #fde8e8;
        border-left: 4px solid #dc3232;
    }
    
    /* Estilos para cartões */
    .cards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .card-item {
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 15px;
        background: #f9f9f9;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .card-item.default-card {
        border-color: #2271b1;
        background: #f0f6fc;
    }
    
    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }
    
    .card-brand img {
        height: 30px;
        width: auto;
    }
    
    .default-badge {
        background: #2271b1;
        color: white;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 12px;
    }
    
    .card-body {
        margin-bottom: 15px;
    }
    
    .card-number {
        font-size: 16px;
        margin-bottom: 5px;
    }
    
    .card-expiry {
        font-size: 14px;
        color: #666;
    }
    
    .card-actions {
        display: flex;
        justify-content: space-between;
    }
    
    .card-actions button {
        padding: 5px 10px;
        font-size: 13px;
        cursor: pointer;
        border-radius: 4px;
        border: 1px solid #ddd;
        background: white;
    }
    
    .set-default-card {
        color: #2271b1;
    }
    
    .delete-card {
        color: #d63638;
    }
    
    /* Estilos para o formulário do cartão */
    .mp-form {
        max-width: 600px;
        margin-top: 20px;
    }
    
    .mp-form-row {
        display: flex;
        flex-wrap: wrap;
        margin-bottom: 15px;
    }
    
    .mp-col-12 {
        flex: 0 0 100%;
        max-width: 100%;
    }
    
    .mp-col-6 {
        flex: 0 0 calc(50% - 10px);
        max-width: calc(50% - 10px);
    }
    
    .mp-col-6:first-child {
        margin-right: 20px;
    }
    
    .mp-form label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
    }
    
    .mp-form input,
    .mp-form select,
    .mp-input-container {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-sizing: border-box;
    }
    
    .mp-form-actions {
        margin-top: 20px;
        display: flex;
        gap: 10px;
    }
    
    /* Estilos para tabelas */
    .transactions-table-wrapper,
    .subscriptions-table-wrapper {
        margin-top: 15px;
        overflow-x: auto;
    }
    
    .transactions-table,
    .subscriptions-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }
    
    .transactions-table th,
    .transactions-table td,
    .subscriptions-table th,
    .subscriptions-table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }
    
    .transactions-table th,
    .subscriptions-table th {
        background-color: #f8f8f8;
        font-weight: 600;
    }
    
    .transaction-status,
    .subscription-status {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 12px;
    }
    
    .status-approved,
    .status-active {
        background-color: #d1e7dd;
        color: #0f5132;
    }
    
    .status-pending,
    .status-paused {
        background-color: #fff3cd;
        color: #856404;
    }
    
    .status-rejected,
    .status-cancelled {
        background-color: #f8d7da;
        color: #842029;
    }
    
    .pause-subscription {
        padding: 5px 10px;
        background-color: #f8d7da;
        color: #842029;
        border: 1px solid #f5c2c7;
        border-radius: 4px;
        cursor: pointer;
    }
    
    /* Mensagens de resultado */
    #result-message {
        margin: 15px 0;
    }
    
    .error-message {
        padding: 10px;
        background-color: #f8d7da;
        border-left: 4px solid #842029;
        color: #842029;
    }
    
    .success-message {
        padding: 10px;
        background-color: #d1e7dd;
        border-left: 4px solid #0f5132;
        color: #0f5132;
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
 * Obtém cartões do usuário
 * 
 * @param int $user_id ID do usuário
 * @return array Lista de cartões
 */
function get_user_mercadopago_cards($user_id) {
    $cards = get_user_meta($user_id, 'mercadopago_cards', true);
    
    if (empty($cards) || !is_array($cards)) {
        return array();
    }
    
    // Processar os dados dos cartões para garantir consistência
    $processed_cards = array();
    
    foreach ($cards as $card) {
        // Verificar se o cartão possui todos os campos necessários
        if (!isset($card['id']) || !isset($card['last_four'])) {
            continue;
        }
        
        // Normalizar os dados do cartão
        $processed_card = array(
            'id' => $card['id'],
            'last_four' => $card['last_four'],
            'brand' => isset($card['brand']) ? $card['brand'] : 'outro',
            'brand_name' => isset($card['brand_name']) ? $card['brand_name'] : 'Outro',
            'expiry_month' => isset($card['expiry_month']) ? $card['expiry_month'] : '12',
            'expiry_year' => isset($card['expiry_year']) ? $card['expiry_year'] : date('Y'),
            'cardholder_name' => isset($card['cardholder_name']) ? $card['cardholder_name'] : 'Titular',
            'created_at' => isset($card['created_at']) ? $card['created_at'] : current_time('mysql')
        );
        
        $processed_cards[] = $processed_card;
    }
    
    return $processed_cards;
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
 */
function get_mercadopago_config() {
    // Verificar se podemos obter da classe Immobile_Payment
    if (class_exists('Immobile_Payment')) {
        $mp_payment = new Immobile_Payment();
        $reflection = new ReflectionClass($mp_payment);
        $property = $reflection->getProperty('config');
        $property->setAccessible(true);
        return $property->getValue($mp_payment);
    }
    
    // Configuração padrão como fallback
    return [
        'sandbox' => true,
        'public_key' => 'TEST-70b46d06-add9-499a-942e-0f5c01b8769a',
        'access_token' => 'TEST-1105123470040162-010319-784660b8cba90a127251b50a9e066db6-242756635'
    ];
}

/**
 * Ajax handler para salvar cartão
 */
function save_card_ajax() {
    check_ajax_referer('payment_settings_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Usuário não autenticado');
        return;
    }
    
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : get_current_user_id();
    $token = isset($_POST['token']) ? sanitize_text_field($_POST['token']) : '';
    
    if (empty($token)) {
        wp_send_json_error('Token do cartão não fornecido');
        return;
    }
    
    // Verificar se o arquivo mercadopago.php existe
    $mercadopago_file = get_stylesheet_directory() . '/inc/custom/immobile/mercadopago.php';
    if (!file_exists($mercadopago_file)) {
        error_log("Arquivo mercadopago.php não encontrado em: " . $mercadopago_file);
        wp_send_json_error('Configuração do módulo de pagamento não encontrada');
        return;
    }
    
    require_once $mercadopago_file;
    
    try {
        // Verificar se a classe existe
        if (!class_exists('Immobile_Payment')) {
            throw new Exception('Classe de pagamento não encontrada');
        }
        
        // Obter informações do cartão a partir do token
        $mp_payment = new Immobile_Payment();
        $mp_config = get_mercadopago_config();
        
        if (empty($mp_config['access_token'])) {
            throw new Exception('Token de acesso do Mercado Pago não configurado');
        }
        
        // Fazer uma requisição para a API do Mercado Pago para obter os detalhes do cartão
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
            error_log("Error getting card info: " . $err);
            throw new Exception('Erro ao obter informações do cartão: ' . $err);
        }
        
        if ($http_status != 200) {
            error_log("API error: HTTP status " . $http_status . ", Response: " . $response);
            throw new Exception('Erro na API do Mercado Pago: ' . $http_status);
        }
        
        $card_data = json_decode($response, true);
        
        if (empty($card_data)) {
            throw new Exception('Resposta vazia da API do Mercado Pago');
        }
        
        if (isset($card_data['error'])) {
            error_log("API error response: " . json_encode($card_data));
            throw new Exception('Erro retornado pela API: ' . $card_data['error']);
        }
        
        // Verificar se temos os dados necessários do cartão
        if (!isset($card_data['payment_method']) || !isset($card_data['last_four_digits'])) {
            error_log("Missing card data in response: " . json_encode($card_data));
            throw new Exception('Dados do cartão incompletos na resposta da API');
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
        );
        
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
        wp_send_json_error('Erro ao processar cartão: ' . $e->getMessage());
    }
}
add_action('wp_ajax_save_card', 'save_card_ajax');

/**
 * Obtém as transações recentes do usuário
 */
function get_user_recent_transactions($user_id) {
    global $wpdb;
    
    // Na implementação real, você buscaria do banco de dados
    // Aqui vamos usar dados de exemplo para demonstração
    
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
    
    // Se não encontramos transações, criar algumas de exemplo
    if (empty($transactions)) {
        // Adicionar alguns exemplos de transações
        $dates = array(
            date('Y-m-d H:i:s', strtotime('-2 days')),
            date('Y-m-d H:i:s', strtotime('-7 days')),
            date('Y-m-d H:i:s', strtotime('-14 days')),
            date('Y-m-d H:i:s', strtotime('-30 days'))
        );
        
        $descriptions = array(
            'Destaque de Imóvel - Apartamento Centro',
            'Publicação Premium - Casa Lago Norte',
            'Renovação de Assinatura - Destaque',
            'Publicação Básica - Sala Comercial'
        );
        
        $amounts = array(49.90, 29.90, 49.90, 19.90);
        $statuses = array('approved', 'approved', 'approved', 'approved');
        
        for ($i = 0; $i < 4; $i++) {
            $transactions[] = array(
                'id' => 'payment_' . uniqid(),
                'date' => $dates[$i],
                'description' => $descriptions[$i],
                'amount' => $amounts[$i],
                'status' => $statuses[$i]
            );
        }
    }
    
    // Ordenar por data (mais recente primeiro)
    usort($transactions, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    
    return $transactions;
}

/**
 * Adiciona dados simulados de cartão para ambiente de teste
 */
function add_simulated_card_data() {
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
    
    $card_name = sanitize_text_field($_POST['card_name']);
    $card_number = sanitize_text_field($_POST['card_number']);
    $expiry_month = sanitize_text_field($_POST['expiry_month']);
    $expiry_year = sanitize_text_field($_POST['expiry_year']);
    
    // Validar número do cartão (verificação simples)
    if (strlen($card_number) < 13 || strlen($card_number) > 19) {
        error_log('Número de cartão inválido: ' . substr($card_number, 0, 4) . '...');
        wp_send_json_error('Número de cartão inválido.');
        return;
    }
    
    // Gerar ID único para o cartão
    $card_id = uniqid('card_');
    
    // Determinar a bandeira do cartão com base no primeiro dígito
    $first_digit = substr($card_number, 0, 1);
    $card_brand = '';
    
    switch ($first_digit) {
        case '4':
            $card_brand = 'visa';
            break;
        case '5':
            $card_brand = 'mastercard';
            break;
        case '3':
            $card_brand = 'amex';
            break;
        case '6':
            $card_brand = 'elo';
            break;
        default:
            $card_brand = 'unknown';
    }
    
    // Obter os últimos 4 dígitos
    $last_digits = substr($card_number, -4);
    
    // Dados do cartão a serem salvos
    $card_data = array(
        'id' => $card_id,
        'last_four_digits' => $last_digits,
        'cardholder_name' => $card_name,
        'expiration_month' => $expiry_month,
        'expiration_year' => $expiry_year,
        'payment_method_id' => $card_brand,
        'is_default' => false,
        'created_at' => date('Y-m-d H:i:s')
    );
    
    // Obter o ID do usuário atual
    $user_id = get_current_user_id();
    
    // Obter os cartões existentes
    $saved_cards = get_user_meta($user_id, 'mercadopago_cards', true);
    
    if (!is_array($saved_cards)) {
        $saved_cards = array();
    }
    
    // Se não houver cartões, definir este como padrão
    if (empty($saved_cards)) {
        $card_data['is_default'] = true;
    }
    
    // Adicionar o novo cartão à lista
    $saved_cards[$card_id] = $card_data;
    
    // Salvar a lista atualizada
    update_user_meta($user_id, 'mercadopago_cards', $saved_cards);
    
    error_log('Cartão adicionado com sucesso: ' . $card_id);
    
    // Retornar sucesso
    wp_send_json_success(array(
        'message' => 'Cartão adicionado com sucesso!',
        'card_id' => $card_id
    ));
}
add_action('wp_ajax_add_simulated_card', 'add_simulated_card_data');

/**
 * Adiciona estilos CSS para o formulário de pagamento
 */
function payment_settings_styles() {
    if (!is_page('configuracoes-pagamento')) {
        return;
    }
    
    ?>
    <style>
        .payment-settings-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .payment-settings-section {
            margin-bottom: 40px;
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .card-item {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            position: relative;
            transition: all 0.2s;
        }
        
        .card-item.default-card {
            border-color: #4CAF50;
            background-color: rgba(76, 175, 80, 0.05);
        }
        
        .default-badge {
            background: #4CAF50;
            color: white;
            font-size: 12px;
            padding: 3px 8px;
            border-radius: 12px;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .card-brand img {
            height: 30px;
            width: auto;
        }
        
        .card-body {
            margin-bottom: 15px;
        }
        
        .card-number {
            font-size: 16px;
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .card-expiry {
            font-size: 14px;
            color: #666;
        }
        
        .card-actions {
            display: flex;
            gap: 10px;
        }
        
        .button {
            padding: 8px 16px;
            border-radius: 4px;
            border: 1px solid #ddd;
            background: #f5f5f5;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .button:hover {
            background: #e5e5e5;
        }
        
        .button-primary {
            background: #2271b1;
            border-color: #2271b1;
            color: white;
        }
        
        .button-primary:hover {
            background: #135e96;
        }
        
        .mp-form {
            margin-top: 20px;
            max-width: 600px;
        }
        
        .mp-form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .mp-col-12 {
            flex: 0 0 100%;
        }
        
        .mp-col-6 {
            flex: 0 0 calc(50% - 8px);
        }
        
        .mp-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .mp-form input,
        .mp-form select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 15px;
        }
        
        .mp-form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
            border-left: 4px solid #c62828;
        }
        
        .success-message {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
            border-left: 4px solid #2e7d32;
        }
        
        /* Estilos para campos do Mercado Pago */
        .mp-input-container {
            height: 42px;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 2px;
        }
        
        /* Estilos para o formulário simulado */
        #simulated-card-form-container {
            margin-top: 20px;
        }
        
        #simulated-card-number {
            letter-spacing: 1px;
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
    add_action('wp_ajax_add_simulated_card', 'add_simulated_card_data');
    add_action('wp_ajax_set_default_card', 'set_default_card');
    add_action('wp_ajax_delete_card', 'delete_card');
    
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
        wp_register_style('payment-settings-css', get_template_directory_uri() . '/inc/custom/broker/assets/css/payment-settings.css', array(), filemtime(get_template_directory() . '/inc/custom/broker/assets/css/payment-settings.css'));
        wp_enqueue_style('payment-settings-css');
        
        // Registrar e enfileirar o JavaScript
        wp_register_script('payment-settings-js', get_template_directory_uri() . '/inc/custom/broker/assets/js/payment-settings.js', array('jquery'), filemtime(get_template_directory() . '/inc/custom/broker/assets/js/payment-settings.js'), true);
        
        // Localizar o script com dados necessários
        wp_localize_script('payment-settings-js', 'payment_settings', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('payment_settings_nonce'),
            'home_url' => home_url(),
            'is_test_environment' => true
        ));
        
        wp_enqueue_script('payment-settings-js');
    }
} 