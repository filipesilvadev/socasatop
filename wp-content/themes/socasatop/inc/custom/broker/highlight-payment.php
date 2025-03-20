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
    if (intval($author_id) !== $current_user_id && !current_user_can('administrator')) {
        echo '<div class="error-message">Você não tem permissão para destacar este imóvel.</div>';
        return;
    }
    
    // Verificar se o imóvel já está em destaque
    $is_highlighted = get_post_meta($immobile_id, '_is_highlighted', true);
    if ($is_highlighted) {
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
    $price = get_post_meta($immobile_id, 'price', true);
    $featured_image_id = get_post_thumbnail_id($immobile_id);
    $featured_image_url = wp_get_attachment_image_src($featured_image_id, 'medium');
    
    // Garantir que a URL da imagem use HTTPS
    if ($featured_image_url) {
        $image_url = $featured_image_url[0];
        $image_url = str_replace('http://', 'https://', $image_url);
    } else {
        $image_url = get_template_directory_uri() . '/assets/images/no-image.jpg';
    }
    
    // Obter o preço do destaque
    $highlight_price = get_option('highlight_price', 30);
    
    // Criar nonce para segurança
    $nonce = wp_create_nonce('highlight_payment_nonce');
    
    // Carregar o SDK do Mercado Pago
    wp_enqueue_script('mercadopago-js', 'https://sdk.mercadopago.com/js/v2', array(), null, true);
    
    // Carregar o script de pagamento
    wp_enqueue_script('highlight-payment-js', get_template_directory_uri() . '/inc/custom/broker/assets/js/highlight-payment.js', array('jquery'), '1.0', true);
    
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
    <div class="highlight-payment-container">
        <h2>Destaque seu Imóvel</h2>
        
        <!-- Benefícios do destaque -->
        <div class="highlight-benefits">
            <h3>Benefícios do Destaque</h3>
            <ul>
                <li>Seu imóvel aparecerá no topo dos resultados de busca</li>
                <li>Mais visibilidade para potenciais compradores ou locatários</li>
                <li>Aumente suas chances de fechar negócio rapidamente</li>
                <li>Destaque-se da concorrência</li>
            </ul>
        </div>
        
        <!-- Pré-visualização do imóvel -->
        <div class="property-preview">
            <h3>Imóvel a ser destacado</h3>
            <div class="property-card">
                <div class="property-image">
                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($title); ?>">
                </div>
                <div class="property-details">
                    <h4><?php echo esc_html($title); ?></h4>
                    <p class="property-price">R$ <?php echo number_format($price, 2, ',', '.'); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Valor do destaque -->
        <div class="highlight-pricing">
            <h3>Valor do destaque</h3>
            <div class="price-box">
                <span class="price-label">Valor por 30 dias:</span>
                <span class="price-value">R$ <?php echo number_format($highlight_price, 2, ',', '.'); ?></span>
            </div>
            <p class="price-info">O destaque tem duração de 30 dias e pode ser pausado a qualquer momento.</p>
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
                
                <div class="form-row">
                    <label class="checkbox-label">
                        <input type="checkbox" id="save_card" name="save_card">
                        Salvar este cartão para futuras transações
                    </label>
                </div>
            </div>
            
            <div class="terms-acceptance">
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
    
    // Obter dados do formulário
    $user_id = get_current_user_id();
    $immobile_id = isset($_POST['immobile_id']) ? intval($_POST['immobile_id']) : 0;
    $payment_method = isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : '';
    $card_id = isset($_POST['card_id']) ? sanitize_text_field($_POST['card_id']) : '';
    
    // Validar dados
    if (empty($immobile_id)) {
        wp_send_json_error(array('message' => 'ID do imóvel não fornecido.'));
        return;
    }
    
    // Verificar se o imóvel pertence ao corretor
    $broker_id = get_post_meta($immobile_id, 'broker', true);
    if ($broker_id != $user_id) {
        wp_send_json_error(array('message' => 'Você não tem permissão para destacar este imóvel.'));
        return;
    }
    
    // Verificar se o imóvel já está destacado
    $is_sponsored = get_post_meta($immobile_id, 'is_sponsored', true) === 'yes';
    if ($is_sponsored) {
        wp_send_json_error(array('message' => 'Este imóvel já está destacado.'));
        return;
    }
    
    error_log("Iniciando processo de destaque para imóvel ID: {$immobile_id}, Usuário: {$user_id}");
    
    // Processar pagamento
    // Para teste, vamos simplesmente marcar o imóvel como destacado sem processar pagamento
    update_post_meta($immobile_id, 'is_sponsored', 'yes');
    
    // Definir a data de expiração (30 dias a partir de hoje)
    $expiration_date = date('Y-m-d H:i:s', strtotime('+30 days'));
    update_post_meta($immobile_id, 'sponsored_expiration_date', $expiration_date);
    update_post_meta($immobile_id, 'highlight_paused', 'no');
    
    // Registrar o histórico de pagamento (mesmo sem pagamento real)
    $payment_history = get_post_meta($immobile_id, 'payment_history', true);
    if (!is_array($payment_history)) {
        $payment_history = array();
    }
    
    $payment_history[] = array(
        'type' => 'highlight',
        'date' => date('Y-m-d H:i:s'),
        'expiration' => $expiration_date,
        'amount' => 99.00,
        'status' => 'success'
    );
    
    update_post_meta($immobile_id, 'payment_history', $payment_history);
    
    error_log("Destaque ativado com sucesso para imóvel ID: {$immobile_id}");
    
    // Enviar resposta de sucesso
    wp_send_json_success(array(
        'message' => 'Imóvel destacado com sucesso!',
        'redirect_url' => '/corretores/painel/'
    ));
}
add_action('wp_ajax_highlight_payment_process', 'highlight_payment_ajax_handler');

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
 * Processa o pagamento do destaque via AJAX
 */
function highlight_payment_process_ajax() {
    // Verificar nonce para segurança
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'highlight_payment_nonce')) {
        wp_send_json_error(array('message' => 'Erro de segurança. Recarregue a página e tente novamente.'));
        return;
    }
    
    // Verificar se temos o ID do imóvel
    if (!isset($_POST['immobile_id']) || empty($_POST['immobile_id'])) {
        wp_send_json_error(array('message' => 'ID do imóvel não fornecido.'));
        return;
    }
    
    // Obter variáveis
    $immobile_id = intval($_POST['immobile_id']);
    $current_user_id = get_current_user_id();
    $payment_method = isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : 'direct';
    
    // Verificar se o usuário está logado
    if (!is_user_logged_in() || (!current_user_can('author') && !current_user_can('administrator'))) {
        wp_send_json_error(array('message' => 'Você precisa estar logado como um corretor para destacar um imóvel.'));
        return;
    }
    
    // Verificar se o imóvel pertence ao usuário atual ou se é administrador
    $author_id = get_post_field('post_author', $immobile_id);
    if (intval($author_id) !== $current_user_id && !current_user_can('administrator')) {
        wp_send_json_error(array('message' => 'Você não tem permissão para destacar este imóvel.'));
        return;
    }
    
    // Verificar se o imóvel já está em destaque
    $is_highlighted = get_post_meta($immobile_id, '_is_highlighted', true);
    if ($is_highlighted) {
        wp_send_json_error(array('message' => 'Este imóvel já está em destaque.'));
        return;
    }
    
    // Verificar qual método de pagamento foi usado
    $payment_success = false;
    
    if ($payment_method === 'direct') {
        // Pagamento direto (sem processamento de cartão)
        $payment_success = true;
    } elseif ($payment_method === 'saved' && isset($_POST['card_id'])) {
        // Processar pagamento com cartão salvo
        $card_id = sanitize_text_field($_POST['card_id']);
        $saved_cards = get_user_meta($current_user_id, '_saved_payment_cards', true);
        
        if (!empty($saved_cards) && is_array($saved_cards)) {
            foreach ($saved_cards as $card) {
                if ($card['id'] === $card_id) {
                    // Aqui processaria o pagamento com o cartão salvo
                    $payment_success = true;
                    break;
                }
            }
        }
        
        if (!$payment_success) {
            wp_send_json_error(array('message' => 'Cartão não encontrado ou inválido.'));
            return;
        }
    } elseif ($payment_method === 'new' && isset($_POST['token'])) {
        // Processar pagamento com novo cartão
        $token = sanitize_text_field($_POST['token']);
        $payment_method_id = isset($_POST['payment_method_id']) ? sanitize_text_field($_POST['payment_method_id']) : '';
        $issuer_id = isset($_POST['issuer_id']) ? sanitize_text_field($_POST['issuer_id']) : '';
        $identification_number = isset($_POST['identification_number']) ? sanitize_text_field($_POST['identification_number']) : '';
        
        // Salvar cartão se solicitado
        if (isset($_POST['save_card']) && $_POST['save_card'] === 'true') {
            // Aqui salvaria as informações do cartão
            $card_data = array(
                'id' => uniqid('card_'),
                'payment_method' => $payment_method_id,
                'last_four' => substr($token, -4),
                'issuer_id' => $issuer_id
            );
            
            $saved_cards = get_user_meta($current_user_id, '_saved_payment_cards', true);
            if (empty($saved_cards) || !is_array($saved_cards)) {
                $saved_cards = array();
            }
            
            $saved_cards[] = $card_data;
            update_user_meta($current_user_id, '_saved_payment_cards', $saved_cards);
        }
        
        // Aqui processaria o pagamento com o novo cartão
        $payment_success = true;
    } else {
        wp_send_json_error(array('message' => 'Método de pagamento inválido ou dados incompletos.'));
        return;
    }
    
    // Se o pagamento foi bem-sucedido, destaca o imóvel
    if ($payment_success) {
        // Data atual
        $current_time = current_time('mysql');
        $expiry_date = date('Y-m-d H:i:s', strtotime('+30 days', strtotime($current_time)));
        
        // Atualizar metas do imóvel
        update_post_meta($immobile_id, '_is_highlighted', true);
        update_post_meta($immobile_id, '_highlight_start_date', $current_time);
        update_post_meta($immobile_id, '_highlight_expiry_date', $expiry_date);
        update_post_meta($immobile_id, '_highlight_paused', false);
        
        // Registrar a transação
        $transaction_id = uniqid('highlight_');
        update_post_meta($immobile_id, '_highlight_transaction_id', $transaction_id);
        
        // Registrar no log
        error_log("Imóvel destacado: ID {$immobile_id}, Usuário {$current_user_id}, Transação {$transaction_id}");
        
        // Enviar resposta de sucesso
        wp_send_json_success(array(
            'message' => 'Imóvel destacado com sucesso!',
            'redirect_url' => home_url('/corretores/meus-imoveis/')
        ));
    } else {
        wp_send_json_error(array('message' => 'Erro ao processar o pagamento. Tente novamente.'));
    }
}

/**
 * Carrega os estilos CSS para a página de destaque
 */
function enqueue_highlight_styles() {
    wp_enqueue_style('highlight-css', get_template_directory_uri() . '/inc/custom/broker/assets/css/highlight.css', array(), '1.0');
}
add_action('wp_enqueue_scripts', 'enqueue_highlight_styles');