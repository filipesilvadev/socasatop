<?php
/**
 * Script de Teste para Integração de Pagamentos
 * 
 * Este arquivo testa a integração entre o checkout de propriedades
 * e os cartões salvos na página de configurações de pagamento.
 */

// Impedir acesso direto ao arquivo
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Executa o teste de integração de pagamentos
 */
function test_payment_integration() {
    if (!is_user_logged_in() || !current_user_can('administrator')) {
        return "Acesso não autorizado. Este teste só pode ser executado por administradores.";
    }
    
    $user_id = get_current_user_id();
    
    // Resultado do teste
    $result = array(
        'success' => true,
        'messages' => array(),
        'cards' => array()
    );
    
    // Passo 1: Verificar se a função get_user_mercadopago_cards existe
    if (!function_exists('get_user_mercadopago_cards')) {
        $result['success'] = false;
        $result['messages'][] = "ERRO: Função get_user_mercadopago_cards não encontrada. Verifique se o arquivo payment-settings.php está carregado.";
        return format_test_results($result);
    }
    
    // Passo 2: Verificar se a função de checkout existe
    if (!function_exists('render_multi_product_checkout')) {
        $result['success'] = false;
        $result['messages'][] = "ERRO: Função render_multi_product_checkout não encontrada. Verifique se o arquivo payment-checkout.php está carregado.";
        return format_test_results($result);
    }
    
    // Passo 3: Buscar cartões salvos do usuário
    $saved_cards = get_user_mercadopago_cards($user_id);
    $result['cards'] = $saved_cards;
    
    if (empty($saved_cards)) {
        $result['messages'][] = "AVISO: Nenhum cartão salvo encontrado para o usuário atual (ID: {$user_id}).";
        
        // Criar um cartão de teste
        $test_card = array(
            'id' => 'test_card_' . md5(time()),
            'brand' => 'visa',
            'last_four' => '4242',
            'expiry_month' => '12',
            'expiry_year' => '2025',
            'cardholder_name' => 'Usuário de Teste',
            'created_at' => current_time('mysql')
        );
        
        // Salvar o cartão de teste
        update_user_meta($user_id, 'mercadopago_cards', array($test_card));
        update_user_meta($user_id, 'default_payment_card', $test_card['id']);
        
        $result['messages'][] = "INFO: Cartão de teste criado com sucesso.";
        $result['cards'] = array($test_card);
    } else {
        $result['messages'][] = "INFO: Encontrados " . count($saved_cards) . " cartões salvos para o usuário.";
    }
    
    // Passo 4: Verificar se o CSS de checkout está disponível
    $checkout_css_path = get_template_directory() . '/inc/custom/broker/assets/css/payment-checkout.css';
    if (!file_exists($checkout_css_path)) {
        $result['messages'][] = "AVISO: Arquivo CSS de checkout não encontrado em: {$checkout_css_path}";
    } else {
        $result['messages'][] = "INFO: CSS de checkout encontrado.";
    }
    
    // Passo 5: Simular um checkout com um produto de teste
    $test_products = array(
        array(
            'id' => 'test_product',
            'name' => 'Produto de Teste',
            'price' => 10.00,
            'description' => 'Este é um produto de teste para verificar a integração de pagamentos'
        )
    );
    
    $checkout_html = render_multi_product_checkout($test_products);
    
    // Verificar se o checkout contém os elementos esperados
    if (strpos($checkout_html, 'multi-product-checkout') !== false) {
        $result['messages'][] = "INFO: Função de checkout está gerando o HTML corretamente.";
    } else {
        $result['success'] = false;
        $result['messages'][] = "ERRO: Função de checkout não está gerando o HTML esperado.";
    }
    
    // Verificar se o checkout inclui os cartões salvos
    if (strpos($checkout_html, 'saved-cards-list') !== false) {
        $result['messages'][] = "INFO: Lista de cartões salvos está sendo incluída no checkout.";
    } else {
        $result['success'] = false;
        $result['messages'][] = "ERRO: Lista de cartões salvos não está sendo incluída no checkout.";
    }
    
    // Verificar se o JS de pagamento está carregando
    if (wp_script_is('payment-core-js', 'registered')) {
        $result['messages'][] = "INFO: Script payment-core.js está registrado.";
    } else {
        $result['messages'][] = "AVISO: Script payment-core.js não está registrado.";
    }
    
    // Testar o shortcode de pagamento
    $shortcode_results = test_payment_shortcode();
    $result['shortcode_test'] = $shortcode_results;
    
    if ($result['success']) {
        $result['messages'][] = "SUCESSO: A integração entre checkout e cartões salvos está funcionando corretamente!";
    }
    
    return format_test_results($result);
}

/**
 * Formata o resultado do teste para exibição
 */
function format_test_results($result) {
    $output = '<div class="payment-integration-test">';
    $output .= '<h2>Resultado do Teste de Integração</h2>';
    
    if ($result['success']) {
        $output .= '<div class="test-status success">Todos os testes passaram!</div>';
    } else {
        $output .= '<div class="test-status error">Alguns testes falharam. Verifique os detalhes abaixo.</div>';
    }
    
    $output .= '<div class="test-details">';
    $output .= '<h3>Mensagens:</h3>';
    $output .= '<ul class="test-messages">';
    
    foreach ($result['messages'] as $message) {
        $type = 'info';
        
        if (strpos($message, 'ERRO:') === 0) {
            $type = 'error';
        } else if (strpos($message, 'AVISO:') === 0) {
            $type = 'warning';
        } else if (strpos($message, 'SUCESSO:') === 0) {
            $type = 'success';
        }
        
        $output .= "<li class=\"{$type}\">{$message}</li>";
    }
    
    $output .= '</ul>';
    
    if (!empty($result['cards'])) {
        $output .= '<h3>Cartões Detectados:</h3>';
        $output .= '<ul class="test-cards">';
        
        foreach ($result['cards'] as $card) {
            $output .= '<li>';
            $output .= '<strong>ID:</strong> ' . esc_html($card['id']) . '<br>';
            $output .= '<strong>Bandeira:</strong> ' . esc_html($card['brand']) . '<br>';
            $output .= '<strong>Últimos 4 dígitos:</strong> ' . esc_html($card['last_four']) . '<br>';
            $output .= '<strong>Validade:</strong> ' . esc_html($card['expiry_month']) . '/' . esc_html($card['expiry_year']);
            $output .= '</li>';
        }
        
        $output .= '</ul>';
    }
    
    $output .= '</div>'; // .test-details
    
    $output .= '<style>
        .payment-integration-test {
            max-width: 800px;
            margin: 20px 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }
        .test-status {
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-weight: bold;
        }
        .test-status.success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }
        .test-status.error {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }
        .test-messages {
            list-style: none;
            padding: 0;
        }
        .test-messages li {
            padding: 8px 10px;
            margin-bottom: 5px;
            border-radius: 4px;
        }
        .test-messages li.error {
            background-color: #ffebee;
            border-left: 4px solid #f44336;
        }
        .test-messages li.warning {
            background-color: #fff8e1;
            border-left: 4px solid #ffc107;
        }
        .test-messages li.success {
            background-color: #e8f5e9;
            border-left: 4px solid #4caf50;
        }
        .test-messages li.info {
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
        }
        .test-cards {
            list-style: none;
            padding: 0;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
        }
        .test-cards li {
            padding: 15px;
            border-radius: 8px;
            background-color: #f5f5f5;
            border: 1px solid #e0e0e0;
        }
    </style>';
    
    $output .= '</div>'; // .payment-integration-test
    
    return $output;
}

/**
 * Shortcode para executar o teste de integração
 */
function payment_integration_test_shortcode() {
    return test_payment_integration();
}
add_shortcode('test_payment_integration', 'payment_integration_test_shortcode');

/**
 * Testa o shortcode de pagamento
 * 
 * @return array Resultados do teste
 */
function test_payment_shortcode() {
    $results = array(
        'success' => array(),
        'error' => array(),
        'warning' => array()
    );
    
    // Verificar se o shortcode está registrado
    global $shortcode_tags;
    if (!isset($shortcode_tags['socasa_payment_form'])) {
        $results['error'][] = 'O shortcode [socasa_payment_form] não está registrado.';
        return $results;
    }
    
    $results['success'][] = 'O shortcode [socasa_payment_form] está registrado corretamente.';
    
    // Testar o shortcode com um produto válido
    $product_id = 'sponsored_listing';
    $shortcode_output = do_shortcode('[socasa_payment_form product_id="' . $product_id . '"]');
    
    if (empty($shortcode_output)) {
        $results['error'][] = 'O shortcode não gerou nenhuma saída.';
    } elseif (strpos($shortcode_output, 'payment-error') !== false) {
        $results['error'][] = 'O shortcode gerou um erro: ' . strip_tags($shortcode_output);
    } else {
        $results['success'][] = 'O shortcode gerou uma saída válida para o produto "' . $product_id . '".';
        
        // Verificar se o formulário de pagamento está presente
        if (strpos($shortcode_output, 'payment-form') !== false) {
            $results['success'][] = 'O formulário de pagamento está presente na saída do shortcode.';
        } else {
            $results['warning'][] = 'O formulário de pagamento não foi encontrado na saída do shortcode.';
        }
        
        // Verificar se os scripts necessários foram enfileirados
        if (wp_script_is('payment-core-js', 'enqueued')) {
            $results['success'][] = 'O script payment-core-js foi enfileirado corretamente.';
        } else {
            $results['error'][] = 'O script payment-core-js não foi enfileirado.';
        }
        
        if (wp_script_is('mercadopago-sdk', 'enqueued')) {
            $results['success'][] = 'O script mercadopago-sdk foi enfileirado corretamente.';
        } else {
            $results['error'][] = 'O script mercadopago-sdk não foi enfileirado.';
        }
        
        // Verificar se os estilos necessários foram enfileirados
        if (wp_style_is('payment-checkout-css', 'enqueued')) {
            $results['success'][] = 'O estilo payment-checkout-css foi enfileirado corretamente.';
        } else {
            $results['error'][] = 'O estilo payment-checkout-css não foi enfileirado.';
        }
    }
    
    // Testar o shortcode com um produto inválido
    $invalid_product_id = 'invalid_product';
    $invalid_shortcode_output = do_shortcode('[socasa_payment_form product_id="' . $invalid_product_id . '"]');
    
    if (strpos($invalid_shortcode_output, 'payment-error') !== false && 
        strpos($invalid_shortcode_output, 'Produto não encontrado') !== false) {
        $results['success'][] = 'O shortcode tratou corretamente um produto inválido.';
    } else {
        $results['error'][] = 'O shortcode não tratou corretamente um produto inválido.';
    }
    
    return $results;
}

/**
 * Testa o processamento de pagamento com cartão salvo
 */
function test_saved_card_payment() {
    global $wpdb;
    
    $result = [
        'success' => false,
        'messages' => [],
        'details' => []
    ];
    
    // Verificar se o usuário está logado
    if (!is_user_logged_in()) {
        $result['messages'][] = "ERRO: Usuário não autenticado.";
        return $result;
    }
    
    $user_id = get_current_user_id();
    $result['messages'][] = "INFO: Teste para o usuário ID: " . $user_id;
    
    // Verificar se temos a função de processar pagamento
    if (!function_exists('process_payment_with_method')) {
        $result['messages'][] = "ERRO: Função process_payment_with_method não encontrada.";
        return $result;
    }
    
    // Buscar cartões salvos
    if (!function_exists('get_user_mercadopago_cards')) {
        $result['messages'][] = "ERRO: Função get_user_mercadopago_cards não encontrada.";
        return $result;
    }
    
    $saved_cards = get_user_mercadopago_cards($user_id);
    $result['cards'] = $saved_cards;
    
    if (empty($saved_cards)) {
        $result['messages'][] = "ERRO: Nenhum cartão salvo encontrado para este usuário.";
        return $result;
    }
    
    $result['messages'][] = "INFO: Encontrados " . count($saved_cards) . " cartões salvos.";
    
    // Obter o primeiro cartão para teste
    $first_card_id = array_key_first($saved_cards);
    $first_card = $saved_cards[$first_card_id];
    
    $result['messages'][] = "INFO: Usando cartão ID: " . $first_card_id . " para teste.";
    $result['details']['test_card'] = $first_card;
    
    // Construir produto de teste
    $test_product = [
        'id' => 'test_product_' . uniqid(),
        'name' => 'Teste de Integração - Cartão Salvo',
        'price' => 0.01, // Usar valor mínimo para teste
        'description' => 'Produto de teste para validar integração de pagamento'
    ];
    
    // Preparar dados de pagamento
    $payment_data = [
        'payment_method' => 'saved_card',
        'payment_data' => [
            'saved_card_id' => $first_card_id,
            'description' => 'Teste de pagamento - ' . date('Y-m-d H:i:s'),
            'amount' => 0.01
        ]
    ];
    
    $result['details']['payment_data'] = $payment_data;
    
    // Testar processamento (sem envio real)
    $test_mode = true;
    
    if ($test_mode) {
        $result['messages'][] = "INFO: Teste executado em modo de simulação (sem envio real).";
        $result['success'] = true;
        return $result;
    }
    
    // Processar pagamento real
    try {
        $payment_result = process_payment_with_method('mercadopago', $payment_data, $test_product);
        
        $result['details']['payment_result'] = $payment_result;
        
        if ($payment_result['success']) {
            $result['messages'][] = "SUCESSO: Pagamento processado com sucesso.";
            $result['success'] = true;
        } else {
            $result['messages'][] = "FALHA: Erro no processamento: " . $payment_result['message'];
        }
    } catch (Exception $e) {
        $result['messages'][] = "ERRO: Exceção no processamento: " . $e->getMessage();
    }
    
    return $result;
}

/**
 * Exibe interface para testes de integração
 */
function render_test_payment_integration_page() {
    // Verificar permissões
    if (!current_user_can('manage_options')) {
        wp_die('Acesso não autorizado.');
    }
    
    $test_results = [];
    $show_card_test = false;
    
    // Processar ações
    if (isset($_POST['action']) && check_admin_referer('payment_test_nonce')) {
        switch ($_POST['action']) {
            case 'test_basic_integration':
                $test_results = test_basic_payment_integration();
                break;
                
            case 'test_saved_card':
                $test_results = test_saved_card_payment();
                $show_card_test = true;
                break;
        }
    }
    
    // Interface de usuário
    ?>
    <div class="wrap">
        <h1>Teste de Integração de Pagamento</h1>
        
        <div class="card">
            <h2>Testes Disponíveis</h2>
            
            <form method="post" action="">
                <?php wp_nonce_field('payment_test_nonce'); ?>
                
                <p>
                    <button type="submit" name="action" value="test_basic_integration" class="button button-primary">
                        Testar Integração Básica
                    </button>
                    
                    <button type="submit" name="action" value="test_saved_card" class="button button-secondary">
                        Testar Pagamento com Cartão Salvo
                    </button>
                </p>
            </form>
        </div>
        
        <?php if (!empty($test_results)): ?>
        <div class="card test-results">
            <h2>Resultados do Teste</h2>
            
            <h3>Mensagens:</h3>
            <ul class="test-messages">
                <?php foreach ($test_results['messages'] as $msg): ?>
                <li><?php echo $msg; ?></li>
                <?php endforeach; ?>
            </ul>
            
            <?php if (!empty($test_results['details'])): ?>
            <h3>Detalhes:</h3>
            <pre><?php print_r($test_results['details']); ?></pre>
            <?php endif; ?>
            
            <?php if ($show_card_test && !empty($test_results['cards'])): ?>
            <h3>Cartões Salvos:</h3>
            <table class="widefat">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Bandeira</th>
                        <th>Últimos 4 dígitos</th>
                        <th>Validade</th>
                        <th>Criado em</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($test_results['cards'] as $id => $card): ?>
                    <tr>
                        <td><?php echo esc_html($id); ?></td>
                        <td><?php echo esc_html($card['brand'] ?? 'N/A'); ?></td>
                        <td><?php echo esc_html($card['last_four'] ?? 'N/A'); ?></td>
                        <td><?php echo esc_html(($card['expiry_month'] ?? '??') . '/' . ($card['expiry_year'] ?? '??')); ?></td>
                        <td><?php echo esc_html($card['created_at'] ?? 'N/A'); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
            
            <p>
                <a href="<?php echo admin_url('admin.php?page=payment_test'); ?>" class="button">Limpar Resultados</a>
            </p>
        </div>
        <?php endif; ?>
    </div>
    <style>
    .test-results {
        margin-top: 20px;
    }
    .test-messages li {
        margin-left: 20px;
    }
    </style>
    <?php
}

// Registrar página de administração para testes (apenas para administradores)
function register_payment_test_admin_page() {
    add_menu_page(
        'Teste de Integração de Pagamento', // Título da página
        'Teste de Pagamento', // Título do menu
        'manage_options', // Capacidade necessária
        'payment_test', // Slug da página
        'render_test_payment_integration_page', // Função de callback
        'dashicons-money-alt', // Ícone
        99 // Posição no menu
    );
}
add_action('admin_menu', 'register_payment_test_admin_page');

/**
 * Testa a configuração do Mercado Pago
 */
function test_basic_payment_integration() {
    $results = [
        'success' => true,
        'messages' => [],
        'details' => []
    ];
    
    // Verificar se as funções necessárias existem
    $functions_to_check = [
        'get_mercadopago_config' => 'Verificar configuração do Mercado Pago',
        'process_payment_with_method' => 'Processar pagamento com método específico',
        'get_user_mercadopago_cards' => 'Obter cartões salvos do usuário',
        'render_multi_product_checkout' => 'Renderizar checkout unificado'
    ];
    
    foreach ($functions_to_check as $func => $description) {
        if (function_exists($func)) {
            $results['messages'][] = "INFO: Função {$func} encontrada ({$description}).";
        } else {
            $results['success'] = false;
            $results['messages'][] = "ERRO: Função {$func} não encontrada ({$description}).";
        }
    }
    
    // Verificar configuração do Mercado Pago
    if (function_exists('get_mercadopago_config')) {
        $mp_config = get_mercadopago_config();
        $results['details']['mp_config'] = [
            'sandbox' => $mp_config['sandbox'],
            'public_key' => !empty($mp_config['public_key']) ? substr($mp_config['public_key'], 0, 8) . '...' : 'Vazio',
            'access_token' => !empty($mp_config['access_token']) ? substr($mp_config['access_token'], 0, 8) . '...' : 'Vazio'
        ];
        
        if (empty($mp_config['public_key'])) {
            $results['messages'][] = "ERRO: Chave pública do Mercado Pago não configurada.";
            $results['success'] = false;
        } else {
            $results['messages'][] = "INFO: Chave pública do Mercado Pago configurada.";
        }
        
        if (empty($mp_config['access_token'])) {
            $results['messages'][] = "ERRO: Token de acesso do Mercado Pago não configurado.";
            $results['success'] = false;
        } else {
            $results['messages'][] = "INFO: Token de acesso do Mercado Pago configurado.";
        }
        
        // Verificar consistência de modo
        $is_test_key = strpos($mp_config['public_key'], 'TEST-') === 0;
        $is_test_token = strpos($mp_config['access_token'], 'TEST-') === 0;
        $is_prod_key = strpos($mp_config['public_key'], 'APP_USR-') === 0;
        $is_prod_token = strpos($mp_config['access_token'], 'APP_USR-') === 0;
        
        if ($mp_config['sandbox']) {
            if (!$is_test_key) {
                $results['messages'][] = "ERRO: Modo sandbox está ativado, mas a chave pública não é de teste.";
                $results['success'] = false;
            }
            if (!$is_test_token) {
                $results['messages'][] = "ERRO: Modo sandbox está ativado, mas o token de acesso não é de teste.";
                $results['success'] = false;
            }
        } else {
            if (!$is_prod_key) {
                $results['messages'][] = "ERRO: Modo produção está ativado, mas a chave pública não é de produção.";
                $results['success'] = false;
            }
            if (!$is_prod_token) {
                $results['messages'][] = "ERRO: Modo produção está ativado, mas o token de acesso não é de produção.";
                $results['success'] = false;
            }
        }
    }
    
    // Verificar se os arquivos necessários estão incluídos
    $required_files = [
        '/inc/custom/broker/payment-unified.php' => 'Processador de pagamento unificado',
        '/inc/custom/broker/payment-processors/mercadopago.php' => 'Processador do Mercado Pago',
        '/inc/custom/broker/payment-checkout.php' => 'Checkout unificado',
        '/inc/custom/broker/payment-settings.php' => 'Configurações de pagamento'
    ];
    
    foreach ($required_files as $file => $description) {
        $file_path = get_template_directory() . $file;
        if (file_exists($file_path)) {
            $results['messages'][] = "INFO: Arquivo {$file} encontrado ({$description}).";
        } else {
            $results['success'] = false;
            $results['messages'][] = "ERRO: Arquivo {$file} não encontrado ({$description}).";
        }
    }
    
    return $results;
} 