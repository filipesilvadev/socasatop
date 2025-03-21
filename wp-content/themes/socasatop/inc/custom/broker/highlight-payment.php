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
 * 
 * @param int $immobile_id ID do imóvel a ser destacado (opcional)
 */
function render_highlight_payment_form($immobile_id = 0) {
    // Verificar se o usuário está logado e é um corretor
    if (!is_user_logged_in() || (!current_user_can('author') && !current_user_can('administrator'))) {
        echo '<div class="error-message">Você precisa estar logado como um corretor para destacar imóveis.</div>';
        return;
    }
    
    // Verificar se o ID do imóvel foi passado como parâmetro ou via URL
    if ($immobile_id <= 0 && isset($_GET['immobile_id'])) {
        $immobile_id = intval($_GET['immobile_id']);
    }
    
    // Verificar se temos um ID válido
    if ($immobile_id <= 0) {
        echo '<div class="error-message">Nenhum imóvel selecionado para destacar.</div>';
        return;
    }
    
    $current_user_id = get_current_user_id();
    
    // Verificar se o imóvel pertence ao usuário atual ou se é administrador
    $author_id = get_post_field('post_author', $immobile_id);
    $broker_id = get_post_meta($immobile_id, 'broker', true);
    
    // Verificar várias condições: se o usuário é autor, corretor ou administrador do imóvel
    $is_author = intval($author_id) === $current_user_id;
    $is_broker = !empty($broker_id) && intval($broker_id) === $current_user_id;
    $is_admin = current_user_can('administrator');
    
    if (!$is_author && !$is_broker && !$is_admin) {
        echo '<div class="error-message">Você não tem permissão para destacar este imóvel.</div>';
        
        // Debug
        echo '<!-- Debug: user_id=' . $current_user_id . ', author_id=' . $author_id . ', broker_id=' . $broker_id . ' -->';
        
        return;
    }
    
    // Verificar se o imóvel já está em destaque
    $is_highlighted = get_post_meta($immobile_id, '_is_highlighted', true);
    $is_sponsored = get_post_meta($immobile_id, 'is_sponsored', true);
    
    if ($is_highlighted || $is_sponsored === 'yes') {
        echo '<div class="error-message">Este imóvel já está em destaque.</div>';
        return;
    }
    
    // Obter detalhes do imóvel
    $immobile = get_post($immobile_id);
    
    if (!$immobile) {
        echo '<div class="error-message">Imóvel não encontrado.</div>';
        return;
    }
    
    // Obter informações do imóvel
    $title = get_the_title($immobile_id);
    $price = floatval(get_post_meta($immobile_id, 'price', true));
    
    // Melhorar a obtenção da imagem de destaque
    $image_url = '';
    $featured_image_id = get_post_thumbnail_id($immobile_id);
    
    if ($featured_image_id) {
        $featured_image_url = wp_get_attachment_image_src($featured_image_id, 'medium');
        if ($featured_image_url && isset($featured_image_url[0])) {
            $image_url = $featured_image_url[0];
            // Garantir que a URL da imagem use HTTPS
            $image_url = str_replace('http://', 'https://', $image_url);
        }
    } 
    
    // Se não encontrou imagem ou não existe imagem destacada
    if (empty($image_url)) {
        // Caminho para uma imagem padrão
        $placeholder_path = '/inc/custom/broker/assets/images/no-image.jpg';
        $placeholder_file = get_stylesheet_directory() . $placeholder_path;
        
        if (file_exists($placeholder_file)) {
            $image_url = get_stylesheet_directory_uri() . $placeholder_path;
        } else {
            // Fallback para placeholder online
            $image_url = 'https://via.placeholder.com/400x300?text=Sem+Imagem';
        }
    }
    
    // Obter o preço do destaque - usa o nome correto da opção
    $highlight_price = floatval(get_option('highlight_payment_price', 99.90));
    
    // Verificar e exibir informações de debug se necessário
    if (WP_DEBUG) {
        error_log('Highlight Payment Form - Imóvel ID: ' . $immobile_id);
        error_log('Highlight Payment Form - Imagem URL: ' . $image_url);
        error_log('Highlight Payment Form - Preço: ' . $highlight_price);
    }
    
    // Criar nonce para segurança
    $nonce = wp_create_nonce('highlight_payment_nonce');
    
    // Carregar o SDK do Mercado Pago
    wp_enqueue_script('mercadopago-js', 'https://sdk.mercadopago.com/js/v2', array(), null, true);
    
    // Carregar os estilos CSS
    wp_enqueue_style('highlight-css', get_stylesheet_directory_uri() . '/inc/custom/broker/assets/css/highlight.css', array(), '1.0.3');
    
    // Carregar o script de pagamento
    wp_enqueue_script('highlight-payment-js', get_stylesheet_directory_uri() . '/inc/custom/broker/assets/js/highlight-payment.js', array('jquery'), '1.0.3', true);
    
    // Passar variáveis para o script
    wp_localize_script('highlight-payment-js', 'highlight_payment', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => $nonce,
        'immobile_id' => $immobile_id,
        'price' => $highlight_price,
        'public_key' => get_option('mercadopago_public_key', '')
    ));
    
    // Obter cartões salvos do usuário
    $saved_cards = get_user_meta($current_user_id, '_saved_payment_cards', true);
    
    // Início do HTML do formulário
    ?>
    <div class="highlight-container">
        <h2 class="highlight-title">Destaque seu Imóvel</h2>
        <p class="highlight-description">Destaque seu imóvel e aumente suas chances de venda!</p>
        
        <!-- Imóvel a ser destacado -->
        <div class="highlight-property-info">
            <div class="highlight-property-image">
                <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($title); ?>">
            </div>
            <div class="highlight-property-details">
                <h3 class="highlight-property-title"><?php echo esc_html($title); ?></h3>
                <p class="highlight-property-address">
                    <?php 
                    $address = get_post_meta($immobile_id, 'address', true);
                    $neighborhood = get_post_meta($immobile_id, 'neighborhood', true);
                    $city = get_post_meta($immobile_id, 'city', true);
                    
                    $location = array();
                    if (!empty($address)) $location[] = $address;
                    if (!empty($neighborhood)) $location[] = $neighborhood;
                    if (!empty($city)) $location[] = $city;
                    
                    echo esc_html(implode(', ', $location));
                    ?>
                </p>
                <p class="highlight-property-price">R$ <?php echo number_format($price, 2, ',', '.'); ?></p>
            </div>
        </div>
        
        <!-- Informações do destaque -->
        <div class="highlight-payment-info">
            <span class="highlight-price">R$ <?php echo number_format($highlight_price, 2, ',', '.'); ?></span>
            <p>Seu imóvel ficará em destaque por 30 dias, aparecendo no topo das buscas e com selo especial.</p>
        </div>
        
        <!-- Opções de pagamento -->
        <div class="payment-options">
            <h3>Selecione a forma de pagamento</h3>
            
            <?php if (!empty($saved_cards) && is_array($saved_cards)) : ?>
            <div class="payment-option">
                <label>
                    <input type="radio" name="payment_method" value="saved" checked>
                    Usar cartão salvo
                </label>
                
                <div id="saved-card-selection">
                    <?php foreach ($saved_cards as $card) : ?>
                    <div class="saved-card">
                        <label>
                            <input type="radio" name="card_id" value="<?php echo esc_attr($card['id']); ?>" checked>
                            <?php echo sprintf('%s terminado em %s', 
                                esc_html($card['payment_method']), 
                                esc_html($card['last_four'])
                            ); ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="payment-option">
                <label>
                    <input type="radio" name="payment_method" value="new">
                    Usar novo cartão
                </label>
            </div>
            <?php else : ?>
            <div class="payment-option">
                <label>
                    <input type="radio" name="payment_method" value="new" checked>
                    Cartão de crédito
                </label>
            </div>
            <?php endif; ?>
            
            <!-- Formulário para novo cartão -->
            <div id="new-card-form" style="<?php echo (!empty($saved_cards)) ? 'display: none;' : ''; ?>">
                <div class="mp-form">
                    <div class="form-row">
                        <label for="cardNumberContainer">Número do cartão</label>
                        <div id="cardNumberContainer" class="mp-card-input"></div>
                    </div>
                    
                    <div class="form-row card-details">
                        <div class="card-exp">
                            <label for="expirationDateContainer">Validade</label>
                            <div id="expirationDateContainer" class="mp-card-input"></div>
                        </div>
                        
                        <div class="card-cvc">
                            <label for="securityCodeContainer">CVV</label>
                            <div id="securityCodeContainer" class="mp-card-input"></div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <label for="cardholderName">Nome como está no cartão</label>
                        <input type="text" id="cardholderName" name="cardholderName" placeholder="Nome como está no cartão">
                    </div>
                    
                    <div class="form-row">
                        <label for="identificationNumber">CPF do titular</label>
                        <input type="text" id="identificationNumber" name="identificationNumber" placeholder="Apenas números">
                    </div>
                </div>
                
                <div class="form-row">
                    <label class="checkbox-label">
                        <input type="checkbox" id="save_card" name="save_card">
                        Salvar este cartão para futuras transações
                    </label>
                </div>
            </div>
            
            <div class="terms-container">
                <label class="checkbox-label">
                    <input type="checkbox" id="accept-terms" name="accept-terms" required>
                    Concordo com os <a href="<?php echo esc_url(get_privacy_policy_url()); ?>" target="_blank">termos de uso e política de privacidade</a>
                </label>
            </div>
        </div>
        
        <!-- Resultado do pagamento -->
        <div id="payment-result" style="display: none;">
            <div class="success-message" style="display: none;">
                <h3>Pagamento realizado com sucesso!</h3>
                <p></p>
            </div>
            <div class="error-message" style="display: none;"></div>
        </div>
        
        <!-- Ação de destacar -->
        <div class="highlight-action">
            <button class="highlight-button" data-action="highlight-property">Destacar Imóvel Agora</button>
        </div>
        
        <!-- Loading overlay -->
        <div class="loading-overlay">
            <div class="loading-spinner"></div>
        </div>
    </div>
    <?php
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
    
    ob_start();
    echo '<div style="font-family: Arial, sans-serif; line-height: 1.6;">';
    render_highlight_payment_form($immobile_id);
    echo '</div>';
    return ob_get_clean();
}
add_shortcode('highlight_payment', 'highlight_payment_shortcode');

/**
 * Processa o pagamento para destacar um imóvel via AJAX
 */
function highlight_payment_process_ajax() {
    // Verificar o nonce de segurança
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'highlight_payment_nonce')) {
        wp_send_json_error(['message' => 'Erro de segurança. Recarregue a página e tente novamente.']);
        return;
    }
    
    // Log de início
    error_log('Highlight Payment - Início do processamento de pagamento');
    error_log('Highlight Payment - Dados recebidos: ' . print_r($_POST, true));
    
    // Verificar ID do imóvel
    if (!isset($_POST['immobile_id']) || empty($_POST['immobile_id'])) {
        error_log('Highlight Payment - Erro: ID do imóvel não fornecido');
        wp_send_json_error(['message' => 'ID do imóvel não fornecido']);
        return;
    }
    
    $immobile_id = intval($_POST['immobile_id']);
    $user_id = get_current_user_id();
    
    // Verificar se o usuário está logado
    if (!$user_id) {
        error_log('Highlight Payment - Erro: Usuário não logado');
        wp_send_json_error(['message' => 'Você precisa estar logado para realizar esta operação']);
        return;
    }
    
    // Verificar permissão para destacar o imóvel
    $author_id = get_post_field('post_author', $immobile_id);
    $broker_id = get_post_meta($immobile_id, 'broker', true);
    
    $is_author = intval($author_id) === $user_id;
    $is_broker = !empty($broker_id) && intval($broker_id) === $user_id;
    $is_admin = current_user_can('administrator');
    
    if (!$is_author && !$is_broker && !$is_admin) {
        error_log('Highlight Payment - Erro: Usuário sem permissão. user_id=' . $user_id . ', author_id=' . $author_id . ', broker_id=' . $broker_id);
        wp_send_json_error(['message' => 'Você não tem permissão para destacar este imóvel']);
        return;
    }
    
    // Verificar método de pagamento
    $payment_method = isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : 'direct';
    
    // Processar pagamento baseado no método
    try {
        error_log('Highlight Payment - Processando pagamento com método: ' . $payment_method);
        
        if ($payment_method === 'direct') {
            // Método direto (sem pagamento real, apenas para testes ou admin)
            if (!$is_admin) {
                error_log('Highlight Payment - Erro: Tentativa de usar método direto sem ser administrador');
                wp_send_json_error(['message' => 'Método de pagamento não autorizado']);
                return;
            }
            
            // Destacar o imóvel diretamente
            error_log('Highlight Payment - Destaque direto como admin');
            $result = highlight_payment_process($immobile_id);
            
            if (is_wp_error($result)) {
                error_log('Highlight Payment - Erro ao processar destaque: ' . $result->get_error_message());
                wp_send_json_error(['message' => $result->get_error_message()]);
                return;
            }
            
            wp_send_json_success([
                'message' => 'Imóvel destacado com sucesso!',
                'redirect_url' => get_permalink($immobile_id)
            ]);
            
        } elseif ($payment_method === 'saved') {
            // Pagamento com cartão salvo
            if (!isset($_POST['card_id']) || empty($_POST['card_id'])) {
                error_log('Highlight Payment - Erro: ID do cartão não fornecido');
                wp_send_json_error(['message' => 'ID do cartão não fornecido']);
                return;
            }
            
            $card_id = sanitize_text_field($_POST['card_id']);
            error_log('Highlight Payment - Processando com cartão salvo: ' . $card_id);
            
            // Verificar se o cartão pertence ao usuário
            $user_cards = get_user_meta($user_id, 'mercadopago_cards', true);
            
            if (!is_array($user_cards) || !isset($user_cards[$card_id])) {
                error_log('Highlight Payment - Erro: Cartão não encontrado para o usuário');
                wp_send_json_error(['message' => 'Cartão não encontrado']);
                return;
            }
            
            // Criar assinatura
            $subscription = create_mercadopago_subscription($immobile_id, $user_id, $card_id);
            
            if (is_wp_error($subscription)) {
                error_log('Highlight Payment - Erro ao criar assinatura: ' . $subscription->get_error_message());
                wp_send_json_error(['message' => $subscription->get_error_message()]);
                return;
            }
            
            // Salvar ID da assinatura
            update_post_meta($immobile_id, 'highlight_subscription_id', $subscription['id']);
            
            // Destacar o imóvel
            $result = highlight_payment_process($immobile_id);
            
            if (is_wp_error($result)) {
                error_log('Highlight Payment - Erro ao processar destaque (cartão salvo): ' . $result->get_error_message());
                
                // Tentar cancelar a assinatura se o destaque falhar
                if (isset($subscription['id'])) {
                    highlight_cancel_mercadopago_subscription($subscription['id']);
                }
                
                wp_send_json_error(['message' => $result->get_error_message()]);
                return;
            }
            
            wp_send_json_success([
                'message' => 'Pagamento processado e imóvel destacado com sucesso!',
                'redirect_url' => get_permalink($immobile_id)
            ]);
            
        } elseif ($payment_method === 'new') {
            // Pagamento com novo cartão
            if (!isset($_POST['token']) || empty($_POST['token'])) {
                error_log('Highlight Payment - Erro: Token do cartão não fornecido');
                wp_send_json_error(['message' => 'Token do cartão não fornecido']);
                return;
            }
            
            $token = sanitize_text_field($_POST['token']);
            $payment_method_id = isset($_POST['payment_method_id']) ? sanitize_text_field($_POST['payment_method_id']) : '';
            $issuer_id = isset($_POST['issuer_id']) ? sanitize_text_field($_POST['issuer_id']) : '';
            $identification_number = isset($_POST['identification_number']) ? sanitize_text_field($_POST['identification_number']) : '';
            
            error_log('Highlight Payment - Processando com novo cartão. Token: ' . substr($token, 0, 10) . '...');
            
            // Obter preço do destaque
            $highlight_price = get_option('highlight_payment_price', 99.90);
            
            // Obter as configurações do Mercado Pago
            $mp_config = highlight_get_mercadopago_config();
            
            if (empty($mp_config['access_token'])) {
                error_log('Highlight Payment - Erro: Token de acesso do Mercado Pago não configurado');
                wp_send_json_error(['message' => 'Erro de configuração do gateway de pagamento']);
                return;
            }
            
            // Preparar dados para a API
            $api_data = [
                'transaction_amount' => floatval($highlight_price),
                'token' => $token,
                'description' => 'Destaque de imóvel em SoCasaTop',
                'installments' => 1,
                'payment_method_id' => $payment_method_id,
                'payer' => [
                    'email' => wp_get_current_user()->user_email
                ]
            ];
            
            // Adicionar identificação se fornecida
            if (!empty($identification_number)) {
                $api_data['payer']['identification'] = [
                    'type' => 'CPF',
                    'number' => $identification_number
                ];
            }
            
            // Adicionar issuer_id se fornecido
            if (!empty($issuer_id)) {
                $api_data['issuer_id'] = $issuer_id;
            }
            
            error_log('Highlight Payment - Dados para API: ' . json_encode($api_data));
            
            // Fazer requisição para a API
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://api.mercadopago.com/v1/payments');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($api_data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $mp_config['access_token'],
                'Content-Type: application/json'
            ]);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_errno($ch)) {
                $curl_error = curl_error($ch);
                error_log('Highlight Payment - Erro cURL: ' . $curl_error);
                curl_close($ch);
                wp_send_json_error(['message' => 'Erro na comunicação com o gateway de pagamento: ' . $curl_error]);
                return;
            }
            
            curl_close($ch);
            
            error_log('Highlight Payment - Resposta API (HTTP ' . $http_code . '): ' . $response);
            
            $payment_data = json_decode($response, true);
            
            if ($http_code < 200 || $http_code >= 300 || !$payment_data) {
                $error_msg = isset($payment_data['message']) ? $payment_data['message'] : 'Erro desconhecido no processamento do pagamento';
                error_log('Highlight Payment - Erro na resposta da API: ' . $error_msg);
                wp_send_json_error(['message' => 'Erro no processamento do pagamento: ' . $error_msg]);
                return;
            }
            
            // Verificar status do pagamento
            $status = isset($payment_data['status']) ? $payment_data['status'] : '';
            
            if ($status === 'approved') {
                error_log('Highlight Payment - Pagamento aprovado! ID: ' . $payment_data['id']);
                
                // Salvar ID do pagamento
                update_post_meta($immobile_id, 'highlight_payment_id', $payment_data['id']);
                
                // Destacar o imóvel
                $result = highlight_payment_process($immobile_id);
                
                if (is_wp_error($result)) {
                    error_log('Highlight Payment - Erro ao processar destaque após pagamento aprovado: ' . $result->get_error_message());
                    wp_send_json_error(['message' => $result->get_error_message()]);
                    return;
                }
                
                wp_send_json_success([
                    'message' => 'Pagamento aprovado e imóvel destacado com sucesso!',
                    'redirect_url' => get_permalink($immobile_id)
                ]);
                
            } elseif ($status === 'in_process' || $status === 'pending') {
                error_log('Highlight Payment - Pagamento em processamento. ID: ' . $payment_data['id']);
                
                // Salvar ID do pagamento
                update_post_meta($immobile_id, 'highlight_payment_id', $payment_data['id']);
                update_post_meta($immobile_id, 'highlight_payment_status', $status);
                
                wp_send_json_success([
                    'message' => 'Pagamento em processamento. Você receberá uma confirmação em breve.',
                    'redirect_url' => get_permalink($immobile_id)
                ]);
                
            } else {
                // Pagamento rejeitado ou outro status
                $status_detail = isset($payment_data['status_detail']) ? $payment_data['status_detail'] : 'unknown';
                error_log('Highlight Payment - Pagamento não aprovado. Status: ' . $status . ', Detalhe: ' . $status_detail);
                
                $error_msg = 'Pagamento não aprovado.';
                
                // Obter mensagem amigável para o erro
                if ($status === 'rejected') {
                    $error_messages = [
                        'cc_rejected_bad_filled_card_number' => 'Verifique o número do cartão.',
                        'cc_rejected_bad_filled_date' => 'Verifique a data de validade.',
                        'cc_rejected_bad_filled_other' => 'Verifique os dados do cartão.',
                        'cc_rejected_bad_filled_security_code' => 'Verifique o código de segurança.',
                        'cc_rejected_blacklist' => 'Não foi possível processar seu pagamento.',
                        'cc_rejected_call_for_authorize' => 'Você deve autorizar o pagamento com seu banco.',
                        'cc_rejected_card_disabled' => 'Ative seu cartão ou use outro meio de pagamento.',
                        'cc_rejected_duplicated_payment' => 'Você já efetuou um pagamento com esse valor.',
                        'cc_rejected_high_risk' => 'Seu pagamento foi recusado.',
                        'cc_rejected_insufficient_amount' => 'Seu cartão possui saldo insuficiente.',
                        'cc_rejected_invalid_installments' => 'Seu cartão não aceita esse número de parcelas.',
                        'cc_rejected_max_attempts' => 'Você atingiu o limite de tentativas. Use outro cartão.',
                    ];
                    
                    if (isset($error_messages[$status_detail])) {
                        $error_msg .= ' ' . $error_messages[$status_detail];
                    } else {
                        $error_msg .= ' Motivo: ' . $status_detail;
                    }
                }
                
                wp_send_json_error(['message' => $error_msg]);
                return;
            }
        } else {
            // Método de pagamento desconhecido
            error_log('Highlight Payment - Erro: Método de pagamento desconhecido: ' . $payment_method);
            wp_send_json_error(['message' => 'Método de pagamento inválido']);
            return;
        }
    } catch (Exception $e) {
        error_log('Highlight Payment - Exceção: ' . $e->getMessage());
        wp_send_json_error(['message' => 'Erro ao processar pagamento: ' . $e->getMessage()]);
        return;
    }
}
add_action('wp_ajax_highlight_payment_process', 'highlight_payment_process_ajax');

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
        
        // Inicializar o Mercado Pago se disponível
        try {
            if (typeof MercadoPago !== 'undefined') {
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
            } else {
                console.warn('MercadoPago não está disponível');
            }
        } catch (e) {
            console.error('Erro ao inicializar MercadoPago:', e);
        }
        
        // Manipular cliques no botão de pagamento
        $('#process-payment').on('click', function(e) {
            e.preventDefault();
            
            if (!$('#accept-terms').is(':checked')) {
                showError('Você precisa aceitar os termos e condições para continuar.');
                return;
            }
            
            // Opção simplificada: processamento direto
            processPayment('direct');
        });
        
        function processPayment(cardId, token = null) {
            // Mostrar loader
            $('#payment-result').show();
            $('.success-message, .error-message').hide();
            
            // Desabilitar botão de pagamento
            $('#process-payment').prop('disabled', true);
            
            $.ajax({
                url: highlight_payment.ajax_url,
                type: 'POST',
                data: {
                    action: 'highlight_payment_process',
                    nonce: highlight_payment.nonce,
                    immobile_id: highlight_payment.immobile_id,
                    payment_method: cardId,
                    token: token,
                    save_card: $('#save_card').is(':checked')
                },
                success: function(response) {
                    if (response.success) {
                        $('.success-message').show().html('<h3>Pagamento realizado com sucesso!</h3><p>' + response.data.message + '</p>');
                        
                        // Redirecionar para a página do imóvel após alguns segundos
                        setTimeout(function() {
                            window.location.href = response.data.redirect_url || '/corretores/painel/';
                        }, 3000);
                    } else {
                        $('.error-message').show().text(response.data.message || 'Erro ao processar o pagamento');
                        $('#process-payment').prop('disabled', false);
                    }
                },
                error: function() {
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
 * AJAX handler para processar pausa/despausa de um destaque
 */
function toggle_highlight_pause() {
    // Verificar nonce para segurança
    $nonce_valid = false;
    
    if (isset($_POST['nonce'])) {
        // Tentativa com vários nonces possíveis
        $nonce_valid = wp_verify_nonce($_POST['nonce'], 'highlight_payment_nonce');
        
        if (!$nonce_valid) {
            $nonce_valid = wp_verify_nonce($_POST['nonce'], 'broker_dashboard_nonce');
        }
        
        if (!$nonce_valid && isset($_POST['property_id'])) {
            $nonce_valid = wp_verify_nonce($_POST['nonce'], 'broker_dashboard_nonce');
        }
    }
    
    if (!$nonce_valid) {
        wp_send_json_error(array('message' => 'Erro de segurança. Recarregue a página e tente novamente.'));
        return;
    }
    
    // Verificar se temos o ID do imóvel
    $immobile_id = 0;
    if (isset($_POST['immobile_id']) && !empty($_POST['immobile_id'])) {
        $immobile_id = intval($_POST['immobile_id']);
    } elseif (isset($_POST['property_id']) && !empty($_POST['property_id'])) {
        $immobile_id = intval($_POST['property_id']);
    }
    
    if ($immobile_id === 0) {
        wp_send_json_error(array('message' => 'ID do imóvel não fornecido.'));
        return;
    }
    
    // Obter variáveis
    $current_user_id = get_current_user_id();
    
    // Verificar se o imóvel pertence ao usuário atual
    $broker_id = get_post_meta($immobile_id, 'broker', true);
    $author_id = get_post_field('post_author', $immobile_id);
    
    if (intval($broker_id) !== $current_user_id && intval($author_id) !== $current_user_id && !current_user_can('administrator')) {
        wp_send_json_error(array('message' => 'Você não tem permissão para modificar este imóvel.'));
        return;
    }
    
    // Verificar se o imóvel está em destaque
    $is_highlighted = get_post_meta($immobile_id, '_is_highlighted', true);
    $is_sponsored = get_post_meta($immobile_id, 'is_sponsored', true) === 'yes';
    
    if (!$is_highlighted && !$is_sponsored) {
        wp_send_json_error(array('message' => 'Este imóvel não está em destaque.'));
        return;
    }
    
    // Obter status atual de pausa
    $is_paused = get_post_meta($immobile_id, '_highlight_paused', true);
    $highlight_paused = get_post_meta($immobile_id, 'highlight_paused', true);
    
    if (empty($is_paused) && !empty($highlight_paused)) {
        $is_paused = $highlight_paused === 'yes';
    }
    
    $new_status = $is_paused ? false : true;
    
    // Atualizar o status de pausa em ambos os meta campos
    update_post_meta($immobile_id, '_highlight_paused', $new_status);
    update_post_meta($immobile_id, 'highlight_paused', $new_status ? 'yes' : 'no');
    
    // Preparar mensagem de retorno
    $status_text = $new_status ? 'pausado' : 'reativado';
    $message = sprintf('Destaque do imóvel %s com sucesso!', $status_text);
    
    // Enviar resposta de sucesso
    wp_send_json_success(array(
        'message' => $message,
        'is_paused' => $new_status,
        'immobile_id' => $immobile_id
    ));
}
add_action('wp_ajax_toggle_highlight_pause', 'toggle_highlight_pause');

/**
 * Carrega os estilos e scripts para a página de pagamento de destaque
 */
function enqueue_highlight_styles() {
    wp_register_style('highlight-payment', get_stylesheet_directory_uri() . '/inc/custom/broker/assets/css/highlight.css', array(), '1.0.2');
    wp_enqueue_style('highlight-payment');
    
    // Verificar se o SDK do MercadoPago já está registrado
    if (!wp_script_is('mercadopago-sdk', 'registered')) {
        wp_register_script('mercadopago-sdk', 'https://sdk.mercadopago.com/js/v2', array(), null, true);
    }
    
    // Carregar o SDK
    wp_enqueue_script('mercadopago-sdk');
    
    // Verificar se estamos numa página com o shortcode de destaque
    global $post;
    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'highlight_payment_form')) {
        $mp_config = highlight_get_mercadopago_config();
        
        // Debug
        error_log('Highlight Payment - Carregando scripts com configuração: ' . json_encode($mp_config));
        
        // Obter imóvel pelo parâmetro na URL
        $immobile_id = isset($_GET['immobile_id']) ? intval($_GET['immobile_id']) : 0;
        
        // Obter preço do destaque
        $price = get_option('highlight_payment_price', 99.90);
        
        // Parâmetros para o script JS
        $params = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('highlight_payment_nonce'),
            'immobile_id' => $immobile_id,
            'price' => $price,
            'public_key' => $mp_config['public_key'],
            'sandbox' => $mp_config['sandbox'] ? 'true' : 'false',
            'debug' => true
        );
        
        // Informações de depuração
        error_log('Highlight Payment - Inicializando JS com parâmetros: ' . json_encode($params));
        
        // Registrar e carregar o script principal
        wp_register_script('highlight-payment', get_stylesheet_directory_uri() . '/inc/custom/broker/assets/js/highlight-payment.js', array('jquery', 'mercadopago-sdk'), '1.0.2', true);
        wp_localize_script('highlight-payment', 'highlight_payment', $params);
        wp_enqueue_script('highlight-payment');
    }
}
add_action('wp_enqueue_scripts', 'enqueue_highlight_styles');

/**
 * Verifica se as configurações do MercadoPago estão corretas
 * e tenta detectar problemas comuns
 */
function check_mercadopago_configuration() {
    // Verificar se é uma página de admin
    if (!is_admin()) {
        return;
    }
    
    // Verificar se é a página de configurações do tema
    $screen = get_current_screen();
    if (!$screen || $screen->id !== 'appearance_page_theme-options') {
        return;
    }
    
    // Obter configurações
    $mp_config = highlight_get_mercadopago_config();
    
    // Verificar chave pública
    if (empty($mp_config['public_key'])) {
        add_settings_error(
            'mercadopago_messages',
            'mercadopago_public_key_missing',
            'A chave pública do MercadoPago não está configurada.',
            'error'
        );
    } elseif (strpos($mp_config['public_key'], 'TEST-') === 0 && !$mp_config['sandbox']) {
        add_settings_error(
            'mercadopago_messages',
            'mercadopago_public_key_test',
            'Você está usando uma chave pública de TESTE, mas o modo sandbox não está ativado.',
            'warning'
        );
    } elseif (strpos($mp_config['public_key'], 'TEST-') !== 0 && $mp_config['sandbox']) {
        add_settings_error(
            'mercadopago_messages',
            'mercadopago_public_key_production',
            'Você está usando uma chave pública de PRODUÇÃO, mas o modo sandbox está ativado.',
            'warning'
        );
    }
    
    // Verificar token de acesso
    if (empty($mp_config['access_token'])) {
        add_settings_error(
            'mercadopago_messages',
            'mercadopago_access_token_missing',
            'O token de acesso do MercadoPago não está configurado.',
            'error'
        );
    } elseif (strpos($mp_config['access_token'], 'TEST-') === 0 && !$mp_config['sandbox']) {
        add_settings_error(
            'mercadopago_messages',
            'mercadopago_access_token_test',
            'Você está usando um token de acesso de TESTE, mas o modo sandbox não está ativado.',
            'warning'
        );
    } elseif (strpos($mp_config['access_token'], 'TEST-') !== 0 && $mp_config['sandbox']) {
        add_settings_error(
            'mercadopago_messages',
            'mercadopago_access_token_production',
            'Você está usando um token de acesso de PRODUÇÃO, mas o modo sandbox está ativado.',
            'warning'
        );
    }
    
    // Verificar preço do destaque
    $price = get_option('highlight_payment_price', 0);
    if ($price <= 0) {
        add_settings_error(
            'mercadopago_messages',
            'highlight_price_invalid',
            'O preço para destacar imóveis não está configurado corretamente.',
            'error'
        );
    }
    
    // Exibir mensagens
    settings_errors('mercadopago_messages');
}
add_action('admin_notices', 'check_mercadopago_configuration');

/**
 * Adiciona um endpoint para teste de carregamento do SDK do MercadoPago
 */
function highlight_mercadopago_test_endpoint() {
    add_rewrite_rule(
        'mercadopago-test$',
        'index.php?mercadopago_test=true',
        'top'
    );
}
add_action('init', 'highlight_mercadopago_test_endpoint');

/**
 * Adiciona a query var para o teste do MercadoPago
 */
function highlight_mercadopago_query_vars($vars) {
    $vars[] = 'mercadopago_test';
    return $vars;
}
add_filter('query_vars', 'highlight_mercadopago_query_vars');

/**
 * Renderiza a página de teste do MercadoPago
 */
function highlight_mercadopago_test_template() {
    if (get_query_var('mercadopago_test') === 'true') {
        // Carregar o SDK do MercadoPago
        wp_enqueue_script('mercadopago-sdk', 'https://sdk.mercadopago.com/js/v2', array(), null, true);
        
        // Obter configuração
        $mp_config = highlight_get_mercadopago_config();
        
        // Parâmetros para o script de teste
        $params = array(
            'public_key' => $mp_config['public_key'],
            'sandbox' => $mp_config['sandbox'] ? 'true' : 'false'
        );
        
        // Script inline para teste
        $test_script = "
            console.log('Teste de carregamento do MercadoPago SDK');
            console.log('Configuração:', " . json_encode($params) . ");
            
            document.addEventListener('DOMContentLoaded', function() {
                var sdkStatus = document.getElementById('sdk-status');
                var sdkVersion = document.getElementById('sdk-version');
                var configInfo = document.getElementById('config-info');
                
                // Verificar se o SDK está carregado
                if (typeof MercadoPago !== 'undefined') {
                    sdkStatus.textContent = 'Carregado com sucesso';
                    sdkStatus.style.color = 'green';
                    
                    // Inicializar o SDK
                    try {
                        var mp = new MercadoPago('" . esc_js($mp_config['public_key']) . "');
                        sdkVersion.textContent = 'Inicializado com sucesso. SDK versão carregada.';
                        configInfo.textContent = 'Usando chave pública: " . esc_js($mp_config['public_key']) . "';
                    } catch(e) {
                        sdkVersion.textContent = 'Erro ao inicializar: ' + e.message;
                        sdkVersion.style.color = 'red';
                    }
                } else {
                    sdkStatus.textContent = 'Não carregado';
                    sdkStatus.style.color = 'red';
                    configInfo.textContent = 'O SDK do MercadoPago não foi carregado corretamente.';
                }
            });
        ";
        
        wp_add_inline_script('mercadopago-sdk', $test_script);
        
        // Exibir template de teste
        $test_html = '
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="utf-8">
                <title>Teste do MercadoPago SDK</title>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; margin: 20px; }
                    .container { max-width: 800px; margin: 0 auto; }
                    .card { border: 1px solid #ddd; border-radius: 4px; padding: 20px; margin-bottom: 20px; }
                    h1 { color: #333; }
                    .status-label { font-weight: bold; }
                    .test-item { margin-bottom: 15px; }
                </style>
                ' . wp_head() . '
            </head>
            <body>
                <div class="container">
                    <h1>Teste de integração do MercadoPago</h1>
                    <div class="card">
                        <div class="test-item">
                            <span class="status-label">Status do SDK:</span> 
                            <span id="sdk-status">Verificando...</span>
                        </div>
                        <div class="test-item">
                            <span class="status-label">Versão/Inicialização:</span> 
                            <span id="sdk-version">Verificando...</span>
                        </div>
                        <div class="test-item">
                            <span class="status-label">Configuração:</span> 
                            <span id="config-info">Verificando...</span>
                        </div>
                    </div>
                    <p>Esta página verifica se o SDK do MercadoPago está sendo carregado corretamente.</p>
                    <p><a href="' . home_url() . '">Voltar para a página inicial</a></p>
                </div>
                ' . wp_footer() . '
            </body>
            </html>
        ';
        
        echo $test_html;
        exit;
    }
}
add_action('template_redirect', 'highlight_mercadopago_test_template');