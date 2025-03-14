<?php
/**
 * Gerenciamento de Cartões e Configurações de Pagamento do Corretor
 */

/**
 * Renderiza o shortcode para a página de configurações de pagamento do corretor
 */
function render_payment_settings_page() {
    if (!is_user_logged_in()) {
        return 'Você precisa estar logado para acessar esta página.';
    }

    $user = wp_get_current_user();
    if (!in_array('author', (array) $user->roles)) {
        return 'Acesso restrito a corretores.';
    }
    
    $user_id = get_current_user_id();
    
    // Carregar a classe do Mercado Pago
    require_once get_stylesheet_directory() . '/inc/custom/immobile/mercadopago.php';
    $mp_payment = new Immobile_Payment();
    $mp_config = get_mercadopago_config();
    
    // Obter cartões salvos
    $cards = get_user_mercadopago_cards($user_id);
    $default_card_id = get_user_meta($user_id, 'default_payment_card', true);
    
    // Enfileirar scripts do Mercado Pago
    wp_enqueue_script('mercadopago-sdk', 'https://sdk.mercadopago.com/js/v2', array(), null, true);
    wp_enqueue_script(
        'broker-payment-settings', 
        get_stylesheet_directory_uri() . '/inc/custom/broker/assets/js/payment-settings.js',
        array('jquery', 'mercadopago-sdk'),
        wp_rand(),
        true
    );
    
    wp_localize_script('broker-payment-settings', 'payment_settings', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('payment_settings_nonce'),
        'public_key' => $mp_config['public_key'],
        'user_id' => $user_id
    ));
    
    ob_start();
    ?>
    <div class="payment-settings-container">
        <h2>Configurações de Pagamento</h2>
        
        <div class="section">
            <h3>Seus Cartões Registrados</h3>
            
            <?php if (!empty($cards)) : ?>
                <div class="cards-list">
                    <?php foreach ($cards as $card) : ?>
                        <div class="card-item <?php echo $card['id'] === $default_card_id ? 'default' : ''; ?>">
                            <div class="card-info">
                                <div class="card-type">
                                    <img src="<?php echo get_card_brand_logo($card['brand']); ?>" alt="<?php echo $card['brand']; ?>">
                                </div>
                                <div class="card-details">
                                    <div class="card-number">•••• •••• •••• <?php echo $card['last_four']; ?></div>
                                    <div class="card-expiry">Expira: <?php echo $card['expiry_month']; ?>/<?php echo $card['expiry_year']; ?></div>
                                </div>
                            </div>
                            <div class="card-actions">
                                <?php if ($card['id'] !== $default_card_id) : ?>
                                    <button class="set-default-card" data-card-id="<?php echo $card['id']; ?>">Definir como Padrão</button>
                                <?php else : ?>
                                    <span class="default-tag">Padrão</span>
                                <?php endif; ?>
                                <button class="delete-card" data-card-id="<?php echo $card['id']; ?>">Remover</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p>Você ainda não tem cartões registrados.</p>
            <?php endif; ?>
            
            <button id="add-new-card" class="primary-button">Adicionar Novo Cartão</button>
        </div>
        
        <div id="card-form-container" style="display: none;" class="section mt-5">
            <h3>Adicionar Novo Cartão</h3>
            <div id="result-message"></div>
            
            <form id="card-form">
                <div class="form-row">
                    <label for="cardholderName">Nome do Titular</label>
                    <input type="text" id="cardholderName" data-checkout="cardholderName">
                </div>
                
                <div class="form-row">
                    <label for="cardNumberContainer">Número do Cartão</label>
                    <div id="cardNumberContainer" class="mp-field"></div>
                </div>
                
                <div class="form-row form-row-inline">
                    <div class="form-col">
                        <label for="expirationDateContainer">Data de Validade</label>
                        <div id="expirationDateContainer" class="mp-field"></div>
                    </div>
                    
                    <div class="form-col">
                        <label for="securityCodeContainer">Código de Segurança</label>
                        <div id="securityCodeContainer" class="mp-field"></div>
                    </div>
                </div>
                
                <div class="form-row form-row-inline">
                    <div class="form-col">
                        <label for="identificationType">Tipo de Documento</label>
                        <select id="identificationType" data-checkout="identificationType"></select>
                    </div>
                    
                    <div class="form-col">
                        <label for="identificationNumber">Número do Documento</label>
                        <input type="text" id="identificationNumber" data-checkout="identificationNumber">
                    </div>
                </div>
                
                <div class="form-row">
                    <label for="installments">Parcelas</label>
                    <select id="installments" data-checkout="installments"></select>
                </div>
                
                <div class="form-actions">
                    <button type="button" id="cancel-card-form" class="secondary-button">Cancelar</button>
                    <button type="button" id="save-card" class="primary-button">Salvar Cartão</button>
                </div>
            </form>
        </div>
        
        <div class="section mt-5">
            <h3>Assinaturas Ativas</h3>
            
            <?php
            $subscriptions = get_user_mercadopago_subscriptions($user_id);
            
            if (!empty($subscriptions)) : ?>
                <div class="subscriptions-list">
                    <?php foreach ($subscriptions as $subscription) : ?>
                        <div class="subscription-item">
                            <div class="subscription-info">
                                <div class="subscription-title">
                                    <a href="<?php echo get_permalink($subscription['immobile_id']); ?>">
                                        <?php echo get_the_title($subscription['immobile_id']); ?>
                                    </a>
                                </div>
                                <div class="subscription-details">
                                    <div>Iniciado em: <?php echo date('d/m/Y', strtotime($subscription['start_date'])); ?></div>
                                    <div>Próxima cobrança: <?php echo date('d/m/Y', strtotime($subscription['next_charge'])); ?></div>
                                    <div>Valor: R$ <?php echo number_format($subscription['amount'], 2, ',', '.'); ?>/mês</div>
                                </div>
                            </div>
                            <div class="subscription-actions">
                                <button class="pause-subscription" data-subscription-id="<?php echo $subscription['id']; ?>" data-immobile-id="<?php echo $subscription['immobile_id']; ?>">
                                    Pausar Assinatura
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p>Você não possui assinaturas ativas.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
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
    </script>
    
    <style>
        .payment-settings-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .section {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 24px;
            margin-bottom: 24px;
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
        
        .cards-list, .subscriptions-list {
            margin-bottom: 20px;
        }
        
        .card-item, .subscription-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px;
            border-radius: 6px;
            border: 1px solid #ddd;
            margin-bottom: 12px;
            transition: all 0.2s ease;
        }
        
        .card-item:hover, .subscription-item:hover {
            border-color: #bbb;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        }
        
        .card-item.default {
            border-color: #4CAF50;
            background-color: #f0f9f0;
        }
        
        .card-info, .subscription-info {
            display: flex;
            align-items: center;
        }
        
        .card-type {
            margin-right: 16px;
        }
        
        .card-type img {
            width: 40px;
            height: auto;
        }
        
        .card-number, .subscription-title {
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .subscription-title a {
            color: #1e56b3;
            text-decoration: none;
        }
        
        .subscription-title a:hover {
            text-decoration: underline;
        }
        
        .card-expiry, .subscription-details {
            color: #666;
            font-size: 14px;
        }
        
        .card-actions, .subscription-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        button {
            padding: 8px 16px;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
        }
        
        .primary-button {
            background-color: #1e56b3;
            color: white;
            padding: 10px 18px;
            font-weight: 500;
        }
        
        .primary-button:hover {
            background-color: #174291;
        }
        
        .secondary-button {
            background-color: #f0f0f0;
            color: #333;
        }
        
        .secondary-button:hover {
            background-color: #e0e0e0;
        }
        
        .set-default-card {
            background-color: #2196F3;
            color: white;
        }
        
        .set-default-card:hover {
            background-color: #0b7dda;
        }
        
        .delete-card, .pause-subscription {
            background-color: #f44336;
            color: white;
        }
        
        .delete-card:hover, .pause-subscription:hover {
            background-color: #d32f2f;
        }
        
        .default-tag {
            background-color: #4CAF50;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            margin-right: 8px;
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
        
        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 20px;
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
        
        .mt-5 {
            margin-top: 40px;
        }
    </style>
    <?php
    return ob_get_clean();
}
add_shortcode('payment_settings', 'render_payment_settings_page');

/**
 * Obtém os cartões salvos do usuário
 */
function get_user_mercadopago_cards($user_id) {
    $saved_cards = get_user_meta($user_id, 'mercadopago_cards', true);
    
    if (empty($saved_cards) || !is_array($saved_cards)) {
        return array();
    }
    
    return $saved_cards;
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
 * Obtém a URL do logo da bandeira do cartão
 */
function get_card_brand_logo($brand) {
    $brand = strtolower($brand);
    
    $brand_logos = array(
        'visa' => 'https://logospng.org/download/visa/logo-visa-icon-1024.png',
        'mastercard' => 'https://logospng.org/download/mastercard/logo-mastercard-icon-1024.png',
        'amex' => 'https://logospng.org/download/american-express/logo-american-express-icon-1024.png',
        'elo' => 'https://logospng.org/download/elo/logo-elo-icon-1024.png',
        'hipercard' => 'https://logospng.org/download/hipercard/logo-hipercard-icon-1024.png',
    );
    
    return isset($brand_logos[$brand]) ? $brand_logos[$brand] : '';
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
function set_default_payment_card() {
    check_ajax_referer('payment_settings_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Usuário não autenticado');
    }
    
    $user_id = get_current_user_id();
    $card_id = isset($_POST['card_id']) ? sanitize_text_field($_POST['card_id']) : '';
    
    if (empty($card_id)) {
        wp_send_json_error('ID do cartão não fornecido');
    }
    
    // Verificar se o cartão existe
    $saved_cards = get_user_meta($user_id, 'mercadopago_cards', true);
    $card_exists = false;
    
    if (!empty($saved_cards)) {
        foreach ($saved_cards as $card) {
            if ($card['id'] === $card_id) {
                $card_exists = true;
                break;
            }
        }
    }
    
    if (!$card_exists) {
        wp_send_json_error('Cartão não encontrado');
    }
    
    // Atualizar cartão padrão
    update_user_meta($user_id, 'default_payment_card', $card_id);
    
    wp_send_json_success(array(
        'message' => 'Cartão definido como padrão com sucesso'
    ));
}
add_action('wp_ajax_set_default_payment_card', 'set_default_payment_card');

/**
 * Remove um cartão
 */
function delete_payment_card() {
    check_ajax_referer('payment_settings_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Usuário não autenticado');
    }
    
    $user_id = get_current_user_id();
    $card_id = isset($_POST['card_id']) ? sanitize_text_field($_POST['card_id']) : '';
    
    if (empty($card_id)) {
        wp_send_json_error('ID do cartão não fornecido');
    }
    
    // Remover o cartão da lista
    $saved_cards = get_user_meta($user_id, 'mercadopago_cards', true);
    $new_cards = array();
    $card_removed = false;
    
    if (!empty($saved_cards)) {
        foreach ($saved_cards as $card) {
            if ($card['id'] !== $card_id) {
                $new_cards[] = $card;
            } else {
                $card_removed = true;
            }
        }
    }
    
    if (!$card_removed) {
        wp_send_json_error('Cartão não encontrado');
    }
    
    update_user_meta($user_id, 'mercadopago_cards', $new_cards);
    
    // Se o cartão removido era o padrão, definir o primeiro cartão da lista como padrão
    $default_card_id = get_user_meta($user_id, 'default_payment_card', true);
    if ($default_card_id === $card_id && !empty($new_cards)) {
        update_user_meta($user_id, 'default_payment_card', $new_cards[0]['id']);
    } elseif (empty($new_cards)) {
        delete_user_meta($user_id, 'default_payment_card');
    }
    
    wp_send_json_success(array(
        'message' => 'Cartão removido com sucesso'
    ));
}
add_action('wp_ajax_delete_payment_card', 'delete_payment_card');

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
    }
    
    $user_id = intval($_POST['user_id']);
    $token = sanitize_text_field($_POST['token']);
    
    if (empty($token)) {
        wp_send_json_error('Token do cartão não fornecido');
    }
    
    require_once get_stylesheet_directory() . '/inc/custom/immobile/mercadopago.php';
    
    try {
        // Obter informações do cartão a partir do token
        $mp_payment = new Immobile_Payment();
        $mp_config = get_mercadopago_config();
        
        // Fazer uma requisição para a API do Mercado Pago para obter os detalhes do cartão
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.mercadopago.com/v1/payment_methods/card_tokens/" . $token);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Authorization: Bearer " . $mp_config['access_token'],
        ));
        
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        
        if ($err) {
            error_log("Error getting card info: " . $err);
            wp_send_json_error('Erro ao obter informações do cartão: ' . $err);
            return;
        }
        
        $card_data = json_decode($response, true);
        
        if (empty($card_data) || isset($card_data['error'])) {
            wp_send_json_error('Erro ao processar informações do cartão');
            return;
        }
        
        // Salvar o cartão no usuário
        $saved_cards = get_user_meta($user_id, 'mercadopago_cards', true);
        if (empty($saved_cards) || !is_array($saved_cards)) {
            $saved_cards = array();
        }
        
        $new_card = array(
            'id' => 'card_' . md5(time() . rand(1000, 9999)),
            'token' => $token,
            'brand' => $card_data['payment_method']['name'],
            'last_four' => $card_data['last_four_digits'],
            'expiry_month' => $card_data['expiration_month'],
            'expiry_year' => $card_data['expiration_year'],
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
        wp_send_json_error('Erro ao processar cartão: ' . $e->getMessage());
    }
}
add_action('wp_ajax_save_card', 'save_card_ajax');

/**
 * Ajax handler para definir cartão padrão
 */
function set_default_card_ajax() {
    check_ajax_referer('payment_settings_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Usuário não autenticado');
    }
    
    $user_id = get_current_user_id();
    $card_id = isset($_POST['card_id']) ? sanitize_text_field($_POST['card_id']) : '';
    
    if (empty($card_id)) {
        wp_send_json_error('ID do cartão não fornecido');
    }
    
    // Verificar se o cartão existe
    $saved_cards = get_user_meta($user_id, 'mercadopago_cards', true);
    $card_exists = false;
    
    if (!empty($saved_cards)) {
        foreach ($saved_cards as $card) {
            if ($card['id'] === $card_id) {
                $card_exists = true;
                break;
            }
        }
    }
    
    if (!$card_exists) {
        wp_send_json_error('Cartão não encontrado');
    }
    
    // Atualizar cartão padrão
    update_user_meta($user_id, 'default_payment_card', $card_id);
    
    wp_send_json_success(array(
        'message' => 'Cartão definido como padrão com sucesso'
    ));
}
add_action('wp_ajax_set_default_card', 'set_default_card_ajax');

/**
 * Ajax handler para excluir cartão
 */
function delete_card_ajax() {
    check_ajax_referer('payment_settings_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Usuário não autenticado');
    }
    
    $user_id = get_current_user_id();
    $card_id = isset($_POST['card_id']) ? sanitize_text_field($_POST['card_id']) : '';
    
    if (empty($card_id)) {
        wp_send_json_error('ID do cartão não fornecido');
    }
    
    // Remover o cartão da lista
    $saved_cards = get_user_meta($user_id, 'mercadopago_cards', true);
    $new_cards = array();
    $card_removed = false;
    
    if (!empty($saved_cards)) {
        foreach ($saved_cards as $card) {
            if ($card['id'] !== $card_id) {
                $new_cards[] = $card;
            } else {
                $card_removed = true;
            }
        }
    }
    
    if (!$card_removed) {
        wp_send_json_error('Cartão não encontrado');
    }
    
    update_user_meta($user_id, 'mercadopago_cards', $new_cards);
    
    // Se o cartão removido era o padrão, definir o primeiro cartão da lista como padrão
    $default_card_id = get_user_meta($user_id, 'default_payment_card', true);
    if ($default_card_id === $card_id && !empty($new_cards)) {
        update_user_meta($user_id, 'default_payment_card', $new_cards[0]['id']);
    } elseif (empty($new_cards)) {
        delete_user_meta($user_id, 'default_payment_card');
    }
    
    wp_send_json_success(array(
        'message' => 'Cartão removido com sucesso'
    ));
}
add_action('wp_ajax_delete_card', 'delete_card_ajax'); 