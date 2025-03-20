<?php
/**
 * Módulo de pagamento para destacar imóveis
 * 
 * Implementa as funcionalidades relacionadas ao pagamento para destacar imóveis.
 */

// Impedir acesso direto ao arquivo
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Renderiza a página de configurações para destacar imóveis
 *
 * @return string HTML renderizado
 */
function render_highlight_payment_page() {
    // Obter configurações salvas ou usar padrões
    $highlight_days = get_option('highlight_payment_days', 30);
    $highlight_price = get_option('highlight_payment_price', 99.90);
    
    ob_start();
    ?>
    <div class="highlight-payment-container">
        <h2>Destaque seu imóvel</h2>
        <p>Destaque seu imóvel e aumente suas chances de venda!</p>
        
        <div class="highlight-benefits">
            <h3>Benefícios do destaque:</h3>
            <ul>
                <li>Maior visibilidade na plataforma</li>
                <li>Aparecimento prioritário nas buscas</li>
                <li>Selo de "Destaque" no seu anúncio</li>
                <li>Inclusão no carrossel de imóveis em destaque</li>
            </ul>
        </div>
        
        <div class="highlight-pricing">
            <h3>Preço do destaque:</h3>
            <p class="price">R$ <?php echo number_format($highlight_price, 2, ',', '.'); ?></p>
            <p class="period">Duração: <?php echo esc_html($highlight_days); ?> dias</p>
        </div>
        
        <div class="highlight-action">
            <a href="#" class="button highlight-button" data-action="highlight-property">Destacar Imóvel Agora</a>
        </div>
    </div>
    
    <style>
        .highlight-payment-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .highlight-payment-container h2 {
            color: #333;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .highlight-benefits {
            margin: 20px 0;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 6px;
        }
        
        .highlight-benefits h3 {
            margin-top: 0;
            color: #333;
        }
        
        .highlight-benefits ul {
            padding-left: 20px;
        }
        
        .highlight-benefits li {
            margin-bottom: 8px;
        }
        
        .highlight-pricing {
            margin: 20px 0;
            text-align: center;
        }
        
        .highlight-pricing .price {
            font-size: 24px;
            font-weight: bold;
            color: #4CAF50;
        }
        
        .highlight-pricing .period {
            color: #666;
        }
        
        .highlight-action {
            text-align: center;
            margin: 25px 0 10px;
        }
        
        .highlight-button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #4CAF50;
            color: white;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        
        .highlight-button:hover {
            background-color: #45a049;
        }
    </style>
    <?php
    return ob_get_clean();
}

/**
 * Função para processar o pagamento de destaque
 */
function highlight_payment_process($property_id) {
    // Verificar se o property_id é válido
    if (empty($property_id) || !is_numeric($property_id)) {
        return new WP_Error('invalid_property', 'ID do imóvel inválido.');
    }
    
    // Verificar se existe o post
    $property = get_post($property_id);
    if (!$property || $property->post_type !== 'immobile') {
        return new WP_Error('property_not_found', 'Imóvel não encontrado.');
    }
    
    // Verificar se o imóvel já está destacado
    $is_sponsored = get_post_meta($property_id, 'is_sponsored', true);
    if ($is_sponsored === 'yes') {
        return new WP_Error('already_sponsored', 'Este imóvel já está destacado.');
    }
    
    // Definir como destacado
    update_post_meta($property_id, 'is_sponsored', 'yes');
    
    // Definir a data de expiração (30 dias a partir de hoje)
    $expiration_date = date('Y-m-d H:i:s', strtotime('+30 days'));
    update_post_meta($property_id, 'sponsored_expiration_date', $expiration_date);
    
    // Registrar o histórico de pagamento
    $payment_history = get_post_meta($property_id, 'payment_history', true);
    if (!is_array($payment_history)) {
        $payment_history = array();
    }
    
    $payment_history[] = array(
        'type' => 'highlight',
        'date' => date('Y-m-d H:i:s'),
        'expiration' => $expiration_date,
        'amount' => get_option('highlight_payment_price', 99.90)
    );
    
    update_post_meta($property_id, 'payment_history', $payment_history);
    
    return true;
}

/**
 * Verifica se um imóvel está destacado e se o destaque ainda é válido
 *
 * @param int $property_id ID do imóvel
 * @return bool Verdadeiro se o imóvel estiver destacado e válido
 */
function is_property_highlighted($property_id) {
    $is_highlighted = get_post_meta($property_id, 'is_highlighted', true);
    
    if ($is_highlighted !== 'yes') {
        return false;
    }
    
    $expiry_date = get_post_meta($property_id, 'highlight_expiry', true);
    
    if (empty($expiry_date)) {
        return false;
    }
    
    // Verificar se a data de expiração já passou
    $now = current_time('mysql');
    return strtotime($expiry_date) > strtotime($now);
}

/**
 * Renderiza o formulário de pagamento para destacar um imóvel
 */
function render_highlight_payment_form($immobile_id) {
    if (!is_user_logged_in()) {
        return 'Você precisa estar logado para destacar um imóvel.';
    }

    $user = wp_get_current_user();
    if (!in_array('author', (array) $user->roles)) {
        return 'Acesso restrito a corretores.';
    }
    
    $user_id = get_current_user_id();
    $broker_id = get_post_meta($immobile_id, 'broker', true);
    
    // Verificar se o imóvel pertence ao corretor
    if ($broker_id != $user_id) {
        return 'Você não tem permissão para destacar este imóvel.';
    }
    
    // Verificar se o imóvel já está destacado
    $is_sponsored = get_post_meta($immobile_id, 'is_sponsored', true) === 'yes';
    if ($is_sponsored) {
        return 'Este imóvel já está destacado. Acesse suas <a href="/corretores/configuracoes-pagamento/">configurações de pagamento</a> para gerenciar suas assinaturas.';
    }
    
    // Obter os cartões do usuário
    $cards = get_user_mercadopago_cards($user_id);
    $default_card_id = get_user_meta($user_id, 'default_payment_card', true);
    
    // Preço mensal do destaque
    $monthly_price = 99.00;
    
    // Enfileirar scripts necessários
    wp_enqueue_script('mercadopago-sdk', 'https://sdk.mercadopago.com/js/v2', array(), null, true);
    wp_enqueue_script(
        'highlight-payment', 
        get_stylesheet_directory_uri() . '/inc/custom/broker/assets/js/highlight-payment.js',
        array('jquery', 'mercadopago-sdk'),
        wp_rand(),
        true
    );
    
    // Carregar a classe Mercado Pago
    require_once(ABSPATH . 'wp-content/themes/socasatop/inc/custom/immobile/mercadopago.php');
    
    // Obter a configuração do Mercado Pago
    if (function_exists('get_mercadopago_config')) {
        $mp_config = highlight_get_mercadopago_config();
        $public_key = $mp_config['public_key'];
    } else {
        $mp_config = highlight_get_mercadopago_config();
        $public_key = $mp_config['public_key'];
    }
    
    wp_localize_script('highlight-payment', 'highlight_payment', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('highlight_payment_nonce'),
        'public_key' => $public_key,
        'user_id' => $user_id,
        'immobile_id' => $immobile_id,
        'immobile_title' => get_the_title($immobile_id),
        'price' => $monthly_price
    ));
    
    ob_start();
    ?>
    <div class="highlight-payment-container">
        <h2>Destaque seu Imóvel</h2>
        <div class="payment-wrapper" style="display: flex; flex-wrap: wrap; gap: 20px;">
            <?php
            // Adicionar imagem do imóvel selecionado
            $thumbnail_id = get_post_thumbnail_id($immobile_id);
            $thumbnail_url = wp_get_attachment_image_url($thumbnail_id, 'medium');
            if (!$thumbnail_url) {
                $thumbnail_url = get_template_directory_uri() . '/inc/custom/broker/assets/images/placeholder-property.jpg';
            }
            $immobile_title = get_the_title($immobile_id);
            ?>
            
            <div class="property-image-preview" style="flex: 0 0 300px;">
                <h3>Imóvel Selecionado</h3>
                <div class="property-image">
                    <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr($immobile_title); ?>" style="max-width: 100%; height: auto; border-radius: 8px; border: 1px solid #ddd;">
                </div>
                <div class="property-title" style="margin-top: 10px; font-weight: bold;">
                    <?php echo esc_html($immobile_title); ?>
                </div>
            </div>
            
            <div class="payment-info" style="flex: 1 1 400px;">
                <p>Ao destacar seu imóvel, ele aparecerá no topo das buscas e terá destaque especial no site, aumentando sua visibilidade e chances de negócio.</p>
                
                <div class="price-info">
                    <h3>Plano de Destaque</h3>
                    <div class="price">R$ <?php echo number_format($monthly_price, 2, ',', '.'); ?>/mês</div>
                    <ul class="benefits">
                        <li>Posicionamento no topo das buscas</li>
                        <li>Etiqueta de "Destaque" nas listagens</li>
                        <li>Maior visibilidade para potenciais clientes</li>
                        <li>Assinatura mensal, cancele quando quiser</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="payment-form">
            <h3>Selecione a forma de pagamento</h3>
            
            <?php if (!empty($cards)) : ?>
                <div class="section-title">Seus cartões salvos</div>
                <div class="cards-list">
                    <?php foreach ($cards as $card) : ?>
                        <div class="card-item">
                            <label class="card-select">
                                <input type="radio" name="payment_method" value="<?php echo $card['id']; ?>" <?php echo $card['id'] === $default_card_id ? 'checked' : ''; ?>>
                                <div class="card-info">
                                    <div class="card-type">
                                        <img src="<?php echo get_card_brand_logo($card['brand']); ?>" alt="<?php echo $card['brand']; ?>">
                                    </div>
                                    <div class="card-details">
                                        <div class="card-number">•••• •••• •••• <?php echo $card['last_four']; ?></div>
                                        <div class="card-expiry">Expira: <?php echo $card['expiry_month']; ?>/<?php echo $card['expiry_year']; ?></div>
                                    </div>
                                </div>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="separator">
                    <span>ou</span>
                </div>
            <?php endif; ?>
            
            <div class="section-title">Adicionar novo cartão</div>
            <div id="card-form-container">
                <form id="card-form">
                    <div class="form-row">
                        <label for="cardholderName">Nome no Cartão</label>
                        <input type="text" id="cardholderName" name="cardholderName" required>
                    </div>
                    
                    <div class="form-row" id="cardNumberContainer"></div>
                    
                    <div class="form-row form-row-double">
                        <div class="form-col">
                            <div id="expirationDateContainer"></div>
                        </div>
                        <div class="form-col">
                            <div id="securityCodeContainer"></div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <label for="identificationNumber">CPF</label>
                        <input type="text" id="identificationNumber" name="identificationNumber" required>
                    </div>
                </form>
            </div>
            
            <div id="payment-result" style="display: none;">
                <div class="success-message" style="display: none;">
                    Pagamento processado com sucesso!
                </div>
                <div class="error-message" style="display: none;"></div>
            </div>
            
            <div class="terms-conditions">
                <label>
                    <input type="checkbox" id="accept-terms" required>
                    Eu aceito os <a href="/termos-e-condicoes/" target="_blank">Termos e Condições</a> e autorizo a cobrança mensal de R$ <?php echo number_format($monthly_price, 2, ',', '.'); ?> em meu cartão de crédito.
                </label>
            </div>
            
            <div class="payment-actions">
                <button id="process-payment" class="primary-button">Destacar Imóvel</button>
                <a href="<?php echo get_permalink($immobile_id); ?>" class="secondary-button">Cancelar</a>
            </div>
        </div>
    </div>
    
    <style>
        .highlight-payment-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        h2 {
            font-size: 28px;
            margin-bottom: 20px;
            color: #333;
        }
        
        h3 {
            font-size: 20px;
            margin-bottom: 16px;
            color: #444;
        }
        
        .payment-info, .payment-form {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 24px;
            margin-bottom: 24px;
        }
        
        .price-info {
            background-color: #f5f9ff;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .price {
            font-size: 28px;
            font-weight: 700;
            color: #1e56b3;
            margin-bottom: 12px;
        }
        
        .benefits {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }
        
        .benefits li {
            padding: 6px 0;
            position: relative;
            padding-left: 24px;
        }
        
        .benefits li:before {
            content: "✓";
            color: #4CAF50;
            position: absolute;
            left: 0;
            top: 6px;
            font-weight: bold;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 500;
            margin-bottom: 12px;
            color: #333;
        }
        
        .cards-list {
            margin-bottom: 20px;
        }
        
        .card-item {
            margin-bottom: 10px;
        }
        
        .card-select {
            display: flex;
            align-items: center;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .card-select:hover {
            border-color: #1e56b3;
        }
        
        .card-select input[type="radio"] {
            margin-right: 12px;
        }
        
        .card-info {
            display: flex;
            align-items: center;
            flex: 1;
        }
        
        .card-type {
            margin-right: 16px;
        }
        
        .card-type img {
            width: 40px;
            height: auto;
        }
        
        .card-number {
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .card-expiry {
            color: #666;
            font-size: 14px;
        }
        
        .separator {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 20px 0;
        }
        
        .separator::before, .separator::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #ddd;
        }
        
        .separator span {
            padding: 0 10px;
            color: #777;
            font-size: 14px;
        }
        
        #card-form {
            margin-top: 16px;
        }
        
        .form-row {
            margin-bottom: 16px;
        }
        
        .form-row-double {
            display: flex;
            gap: 16px;
        }
        
        .form-col {
            flex: 1;
        }
        
        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: #444;
        }
        
        input[type="text"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .terms-conditions {
            margin: 20px 0;
        }
        
        .terms-conditions label {
            display: flex;
            align-items: flex-start;
            font-weight: normal;
        }
        
        .terms-conditions input[type="checkbox"] {
            margin-right: 10px;
            margin-top: 3px;
        }
        
        .terms-conditions a {
            color: #1e56b3;
            text-decoration: none;
        }
        
        .terms-conditions a:hover {
            text-decoration: underline;
        }
        
        .payment-actions {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }
        
        button {
            padding: 10px 18px;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
        }
        
        .primary-button {
            background-color: #1e56b3;
            color: white;
            font-weight: 500;
        }
        
        .primary-button:hover {
            background-color: #174291;
        }
        
        .secondary-button {
            background-color: #f0f0f0;
            color: #333;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 18px;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .secondary-button:hover {
            background-color: #e0e0e0;
            text-decoration: none;
        }
        
        .success-message {
            background-color: #dff2bf;
            color: #4f8a10;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 16px;
        }
        
        .error-message {
            background-color: #ffdddd;
            color: #d8000c;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 16px;
        }
    </style>
    <?php
    return ob_get_clean();
}

/**
 * Shortcode para renderizar o formulário de pagamento para destaque
 */
function highlight_payment_shortcode($atts) {
    $atts = shortcode_atts(
        array(
            'immobile_id' => 0,
        ),
        $atts,
        'highlight_payment'
    );
    
    // Verificar se o ID do imóvel foi passado pela URL
    if (isset($_GET['immobile_id'])) {
        $immobile_id = intval($_GET['immobile_id']);
    } else {
        $immobile_id = intval($atts['immobile_id']);
    }
    
    if ($immobile_id <= 0) {
        return '<div class="error-message">ID do imóvel não fornecido ou inválido.</div>';
    }
    
    // Verificar se o imóvel existe e é do tipo 'immobile'
    $immobile = get_post($immobile_id);
    if (!$immobile || $immobile->post_type !== 'immobile') {
        return '<div class="error-message">Imóvel não encontrado.</div>';
    }
    
    return render_highlight_payment_form($immobile_id);
}
add_shortcode('highlight_payment', 'highlight_payment_shortcode');

/**
 * Função AJAX para processar o pagamento e criar a assinatura
 */
function highlight_payment_ajax_handler() {
    check_ajax_referer('highlight_payment_nonce', 'nonce');
    
    // Verificar se o usuário está logado
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Você precisa estar logado para realizar esta ação.'));
        return;
    }
    
    // Verificar se o ID do imóvel foi enviado
    if (!isset($_POST['immobile_id']) || empty($_POST['immobile_id'])) {
        wp_send_json_error(array('message' => 'ID do imóvel não fornecido.'));
        return;
    }
    
    $immobile_id = intval($_POST['immobile_id']);
    
    // Verificar se o usuário é o corretor deste imóvel
    $user_id = get_current_user_id();
    $broker_id = get_post_meta($immobile_id, 'broker', true);
    
    if ($broker_id != $user_id) {
        wp_send_json_error(array('message' => 'Você não tem permissão para destacar este imóvel.'));
        return;
    }
    
    // Verificar se foi enviado um ID de cartão
    if (!isset($_POST['card_id']) || empty($_POST['card_id'])) {
        // Se não tiver cartão, encaminhar para o checkout normal
        $checkout_url = home_url('/corretores/checkout/?product=highlight&immobile_id=' . $immobile_id);
        wp_send_json_success(array('redirect' => $checkout_url));
        return;
    }
    
    $card_id = sanitize_text_field($_POST['card_id']);
    
    try {
        // Criar assinatura no Mercado Pago
        $subscription = create_mercadopago_subscription($immobile_id, $user_id, $card_id);
        
        if (isset($subscription['success']) && $subscription['success']) {
            // Salvar o ID da assinatura no imóvel
            update_post_meta($immobile_id, 'highlight_subscription_id', $subscription['id']);
            update_post_meta($immobile_id, 'highlight_subscription_data', $subscription);
            
            // Processar o destaque do imóvel
            highlight_payment_process($immobile_id);
            
            // Enviar resposta de sucesso
            wp_send_json_success(array(
                'message' => 'Seu imóvel foi destacado com sucesso!',
                'subscription_id' => $subscription['id'],
                'redirect_url' => get_permalink($immobile_id)
            ));
            return;
        } else {
            // Erro ao criar assinatura
            wp_send_json_error(array('message' => 'Erro ao processar pagamento: ' . ($subscription['message'] ?? 'Erro desconhecido')));
            return;
        }
    } catch (Exception $e) {
        wp_send_json_error(array('message' => 'Erro ao processar pagamento: ' . $e->getMessage()));
        return;
    }
}
add_action('wp_ajax_highlight_payment_ajax_handler', 'highlight_payment_ajax_handler');

/**
 * Criar assinatura no Mercado Pago
 * 
 * Esta é uma implementação simulada. Em produção, você usaria a API do Mercado Pago.
 */
function create_mercadopago_subscription($immobile_id, $user_id, $card_id) {
    // Carregar a classe Mercado Pago
    require_once(ABSPATH . 'wp-content/themes/socasatop/inc/custom/immobile/mercadopago.php');
    $mp = new Immobile_Payment();
    
    // Obter dados do usuário e imóvel
    $user = get_userdata($user_id);
    $immobile = get_post($immobile_id);
    
    try {
        // Usar a função existente process_saved_card_payment
        $result = $mp->process_saved_card_payment([
            'saved_card_id' => $card_id,
            'user_id' => $user_id,
            'amount' => 49.90,
            'description' => 'Destaque de Imóvel - ' . $immobile->post_title
        ]);
        
        // Registrar resposta para depuração
        error_log('Mercado Pago Subscription Response: ' . json_encode($result));
        
        if (isset($result['success']) && $result['success']) {
            return [
                'success' => true,
                'id' => $result['id'] ?? ('TEST_' . uniqid()),
                'message' => 'Assinatura criada com sucesso'
            ];
        } else {
            return [
                'success' => false,
                'message' => isset($result['message']) ? $result['message'] : 'Erro ao criar assinatura'
            ];
        }
    } catch (Exception $e) {
        error_log('Erro ao criar assinatura: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Erro ao criar assinatura: ' . $e->getMessage()
        ];
    }
}

/**
 * Cancelar assinatura no Mercado Pago
 */
function highlight_cancel_mercadopago_subscription($subscription_id) {
    require_once get_stylesheet_directory() . '/inc/custom/immobile/mercadopago.php';
    $mp_config = highlight_get_mercadopago_config();
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.mercadopago.com/preapproval/" . $subscription_id);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array("status" => "cancelled")));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Authorization: Bearer " . $mp_config['access_token'],
        "Content-Type: application/json"
    ));
    
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    
    if ($err) {
        error_log("Error cancelling subscription: " . $err);
        return array(
            'success' => false,
            'message' => 'Erro ao cancelar assinatura: ' . $err
        );
    }
    
    $result = json_decode($response, true);
    
    if (isset($result['error'])) {
        return array(
            'success' => false,
            'message' => 'Erro ao cancelar assinatura: ' . $result['message']
        );
    }
    
    return array(
        'success' => true,
        'message' => 'Assinatura cancelada com sucesso'
    );
}

/**
 * Obtém as configurações do Mercado Pago
 */
function highlight_get_mercadopago_config() {
    $config = array(
        'public_key' => get_option('mercadopago_public_key', ''),
        'access_token' => get_option('mercadopago_access_token', ''),
        'test_mode' => get_option('mercadopago_test_mode', 'yes') === 'yes'
    );
    
    return $config;
}

/**
 * Criar o JavaScript para a página de pagamento de destaque
 */
function create_highlight_payment_js() {
    $js_dir = get_stylesheet_directory() . '/inc/custom/broker/assets/js';
    
    // Criar o diretório se não existir
    if (!file_exists($js_dir)) {
        wp_mkdir_p($js_dir);
    }
    
    $js_file = $js_dir . '/highlight-payment.js';
    
    $js_content = <<<EOT
(function($) {
    $(document).ready(function() {
        console.log('Highlight Payment JS loaded');
        
        // Inicializar o Mercado Pago
        const mp = new MercadoPago(highlight_payment.public_key);
        
        // Configurar o formulário de cartão
        const cardForm = mp.cardForm({
            amount: highlight_payment.price,
            autoMount: true,
            form: {
                id: 'card-form',
                cardholderName: {
                    id: 'cardholderName'
                },
                cardholderEmail: {
                    id: 'cardholderEmail',
                    value: ''
                },
                cardNumber: {
                    id: 'cardNumberContainer',
                    placeholder: 'Número do cartão'
                },
                expirationDate: {
                    id: 'expirationDateContainer',
                    placeholder: 'MM/YY'
                },
                securityCode: {
                    id: 'securityCodeContainer',
                    placeholder: 'CVV'
                },
                installments: {
                    id: 'installments',
                    placeholder: 'Parcelas'
                },
                identificationType: {
                    id: 'docType',
                    placeholder: 'Tipo de documento'
                },
                identificationNumber: {
                    id: 'identificationNumber',
                    placeholder: 'Número do documento'
                },
                issuer: {
                    id: 'issuer',
                    placeholder: 'Banco emissor'
                }
            },
            callbacks: {
                onFormMounted: function(error) {
                    if (error) {
                        console.error('Form Mounted error: ', error);
                        return;
                    }
                    console.log('Form mounted');
                },
                onFormUnmounted: function(error) {
                    if (error) {
                        console.error('Form Unmounted error: ', error);
                        return;
                    }
                    console.log('Form unmounted');
                },
                onIdentificationTypesReceived: function(error, identificationTypes) {
                    if (error) {
                        console.error('IdentificationTypes error: ', error);
                        return;
                    }
                    console.log('Identification types available: ', identificationTypes);
                },
                onPaymentMethodsReceived: function(error, paymentMethods) {
                    if (error) {
                        console.error('Payment Methods error: ', error);
                        return;
                    }
                    console.log('Payment Methods available: ', paymentMethods);
                },
                onIssuersReceived: function(error, issuers) {
                    if (error) {
                        console.error('Issuers error: ', error);
                        return;
                    }
                    console.log('Issuers available: ', issuers);
                },
                onInstallmentsReceived: function(error, installments) {
                    if (error) {
                        console.error('Installments error: ', error);
                        return;
                    }
                    console.log('Installments available: ', installments);
                },
                onCardTokenReceived: function(error, token) {
                    if (error) {
                        console.error('Token error: ', error);
                        return;
                    }
                    console.log('Token available: ', token);
                },
                onSubmit: function(event) {
                    event.preventDefault();
                    
                    if (!$('#accept-terms').is(':checked')) {
                        showError('Você precisa aceitar os termos e condições para continuar.');
                        return;
                    }
                    
                    const cardData = cardForm.getCardFormData();
                    console.log('CardForm data available: ', cardData);
                    
                    if (cardData.token) {
                        processPayment('new', cardData.token);
                    } else {
                        showError('Erro ao processar o cartão. Verifique os dados e tente novamente.');
                    }
                }
            }
        });
        
        // Manipular cliques no botão de pagamento
        $('#process-payment').on('click', function(e) {
            e.preventDefault();
            
            if (!$('#accept-terms').is(':checked')) {
                showError('Você precisa aceitar os termos e condições para continuar.');
                return;
            }
            
            const paymentMethod = $('input[name="payment_method"]:checked').val();
            
            if (paymentMethod === 'new' || !paymentMethod) {
                // Se o método for cartão novo ou não selecionado, submeter o formulário do cartão
                $('#card-form').submit();
            } else {
                // Senão, processar com o cartão salvo
                processPayment(paymentMethod);
            }
        });
        
        function processPayment(cardId, token = null) {
            // Mostrar loader
            $('#payment-result').show();
            $('#payment-loader').show();
            $('.success-message, .error-message').hide();
            
            // Desabilitar botão de pagamento
            $('#process-payment').prop('disabled', true);
            
            $.ajax({
                url: highlight_payment.ajax_url,
                type: 'POST',
                data: {
                    action: 'highlight_payment_ajax_handler',
                    nonce: highlight_payment.nonce,
                    immobile_id: highlight_payment.immobile_id,
                    card_id: cardId,
                    token: token,
                    save_card: $('#save-card').is(':checked')
                },
                success: function(response) {
                    if (response.success) {
                        $('#payment-loader').hide();
                        $('.success-message').show().html(response.data.message);
                        
                        // Redirecionar para a página do imóvel após alguns segundos
                        setTimeout(function() {
                            window.location.href = response.data.redirect_url;
                        }, 3000);
                    } else {
                        $('#payment-loader').hide();
                        $('.error-message').show().text(response.data);
                        $('#process-payment').prop('disabled', false);
                    }
                },
                error: function() {
                    $('#payment-loader').hide();
                    $('.error-message').show().text('Erro ao processar a requisição. Tente novamente.');
                    $('#process-payment').prop('disabled', false);
                }
            });
        }
        
        // Mostrar mensagem de erro
        function showError(message) {
            $('#payment-result').show();
            $('.error-message').show().text(message);
            $('.success-message').hide();
        }
        
        // Alternar entre cartão salvo e novo cartão
        $('input[name="payment_method"]').on('change', function() {
            const value = $(this).val();
            
            if (value === 'new') {
                $('#new-card-form').show();
            } else {
                $('#new-card-form').hide();
            }
        });
    });
})(jQuery);
EOT;
    
    // Salvar o arquivo
    file_put_contents($js_file, $js_content);
}

// Verificar se o arquivo JS existe e criá-lo se necessário
function check_highlight_payment_js() {
    $js_file = get_stylesheet_directory() . '/inc/custom/broker/assets/js/highlight-payment.js';
    
    if (!file_exists($js_file)) {
        create_highlight_payment_js();
    }
}
add_action('init', 'check_highlight_payment_js');

/**
 * Adicionar link para destacar imóvel na página de edição/visualização do imóvel
 */
function add_highlight_button_to_property($content) {
    if (!is_singular('property')) {
        return $content;
    }
    
    if (!is_user_logged_in()) {
        return $content;
    }
    
    $user = wp_get_current_user();
    if (!in_array('author', (array) $user->roles)) {
        return $content;
    }
    
    $immobile_id = get_the_ID();
    $broker_id = get_post_meta($immobile_id, 'broker', true);
    $user_id = get_current_user_id();
    
    if ($broker_id != $user_id) {
        return $content;
    }
    
    $is_sponsored = get_post_meta($immobile_id, 'is_sponsored', true) === 'yes';
    
    if ($is_sponsored) {
        $button = '<div class="highlight-info">
            <div class="highlight-badge">Imóvel Destacado</div>
            <p>Este imóvel está destacado e aparecerá no topo das buscas.</p>
            <p>Gerenciar assinatura em <a href="/corretores/configuracoes-pagamento/">Configurações de Pagamento</a>.</p>
        </div>';
    } else {
        $highlight_page = '/corretores/destacar-imovel/?immobile_id=' . $immobile_id;
        $button = '<div class="highlight-info">
            <a href="' . esc_url($highlight_page) . '" class="highlight-button">Destacar este Imóvel</a>
            <p>Destaque seu imóvel para que ele apareça no topo das buscas.</p>
        </div>';
    }
    
    $button_style = '<style>
        .highlight-info {
            background-color: #f5f9ff;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        
        .highlight-badge {
            background-color: #4CAF50;
            color: white;
            display: inline-block;
            padding: 8px 16px;
            border-radius: 4px;
            font-weight: 500;
            margin-bottom: 12px;
        }
        
        .highlight-button {
            display: inline-block;
            background-color: #1e56b3;
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 12px;
            transition: background-color 0.2s ease;
        }
        
        .highlight-button:hover {
            background-color: #174291;
            text-decoration: none;
            color: white;
        }
        
        .highlight-info p {
            margin: 8px 0 0;
            color: #666;
        }
        
        .highlight-info a:not(.highlight-button) {
            color: #1e56b3;
            text-decoration: none;
        }
        
        .highlight-info a:not(.highlight-button):hover {
            text-decoration: underline;
        }
    </style>';
    
    return $content . $button . $button_style;
}
add_filter('the_content', 'add_highlight_button_to_property');

/**
 * Adicionar etiqueta "Destaque" aos imóveis destacados nas listagens
 */
function add_sponsored_tag_to_listings() {
    // Adicionar a etiqueta via JavaScript nas listagens de imóveis
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Etiqueta para imóveis destacados
        $('.property-item').each(function() {
            var $item = $(this);
            var propertyId = $item.data('property-id');
            
            if (propertyId) {
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'check_property_highlight',
                        property_id: propertyId
                    },
                    success: function(response) {
                        if (response.success && response.data.is_highlighted) {
                            if (!response.data.is_paused) {
                                $item.find('.property-features').prepend('<span class="highlight-tag">Destaque</span>');
                            }
                            
                            // Mover para o topo da lista se destacado e não pausado
                            if (response.data.is_highlighted && !response.data.is_paused) {
                                $item.parent().prepend($item);
                            }
                        }
                    }
                });
            }
        });
    });
    </script>
    <style>
    .highlight-tag {
        background-color: #4CAF50;
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        margin-right: 8px;
        font-size: 12px;
        font-weight: 500;
    }
    </style>
    <?php
}
add_action('wp_footer', 'add_sponsored_tag_to_listings');

/**
 * Verificar se um imóvel está destacado via AJAX
 */
function check_property_highlight() {
    $property_id = isset($_POST['property_id']) ? intval($_POST['property_id']) : 0;
    
    if (empty($property_id)) {
        wp_send_json_error();
    }
    
    $is_sponsored = get_post_meta($property_id, 'is_sponsored', true) === 'yes';
    $highlight_paused = get_post_meta($property_id, 'highlight_paused', true) === 'yes';
    
    wp_send_json_success(array(
        'is_highlighted' => $is_sponsored,
        'is_paused' => $highlight_paused
    ));
}
add_action('wp_ajax_check_property_highlight', 'check_property_highlight');
add_action('wp_ajax_nopriv_check_property_highlight', 'check_property_highlight');

/**
 * Função AJAX para pausar/retomar o destaque de um imóvel
 */
function toggle_highlight_pause() {
    check_ajax_referer('highlight_action_nonce', 'nonce');
    
    // Verificar se o usuário está logado
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Você precisa estar logado para realizar esta ação.'));
        return;
    }
    
    // Verificar se o ID do imóvel foi enviado
    if (!isset($_POST['immobile_id']) || empty($_POST['immobile_id'])) {
        wp_send_json_error(array('message' => 'ID do imóvel não fornecido.'));
        return;
    }
    
    $immobile_id = intval($_POST['immobile_id']);
    
    // Verificar se o usuário é o corretor deste imóvel
    $user_id = get_current_user_id();
    $broker_id = get_post_meta($immobile_id, 'broker', true);
    
    if ($broker_id != $user_id && !current_user_can('administrator')) {
        wp_send_json_error(array('message' => 'Você não tem permissão para modificar este imóvel.'));
        return;
    }
    
    // Obter o estado atual da pausa
    $highlight_paused = get_post_meta($immobile_id, 'highlight_paused', true) === 'yes';
    
    // Inverter o estado da pausa
    $new_state = $highlight_paused ? 'no' : 'yes';
    
    // Atualizar o meta
    update_post_meta($immobile_id, 'highlight_paused', $new_state);
    
    // Enviar resposta
    $action_text = $highlight_paused ? 'reativado' : 'pausado';
    wp_send_json_success(array(
        'message' => 'Destaque ' . $action_text . ' com sucesso!',
        'new_state' => $new_state,
        'paused' => $new_state === 'yes',
        'button_text' => $new_state === 'yes' ? 'Retomar Destaque' : 'Pausar Destaque'
    ));
}
add_action('wp_ajax_toggle_highlight_pause', 'toggle_highlight_pause'); 