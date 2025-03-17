<?php
/**
 * Shortcodes personalizados para o sistema
 * 
 * Fornece shortcodes para funcionalidades do sistema.
 */

// Impedir acesso direto ao arquivo
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Incluir arquivos necessários para o shortcode de pagamento
$payment_settings_path = get_template_directory() . '/inc/custom/broker/payment-settings.php';
if (file_exists($payment_settings_path)) {
    require_once($payment_settings_path);
} else {
    // Criar uma função fallback se o arquivo não existir
    if (!function_exists('render_payment_settings_page')) {
        function render_payment_settings_page() {
            return '<div class="payment-settings">Configurações de pagamento temporariamente indisponíveis.</div>';
        }
    }
}

// Incluir highlight-payment.php com verificação
$highlight_payment_path = get_template_directory() . '/inc/custom/broker/highlight-payment.php';
if (file_exists($highlight_payment_path)) {
    require_once($highlight_payment_path);
} else {
    // Função fallback se o arquivo não existir
    if (!function_exists('render_highlight_payment_page')) {
        function render_highlight_payment_page() {
            return '<div class="highlight-payment">Sistema de pagamento de destaque temporariamente indisponível.</div>';
        }
    }
}

// Incluir payment-unified.php com verificação
$payment_unified_path = get_template_directory() . '/inc/custom/broker/payment-unified.php';
if (file_exists($payment_unified_path)) {
    require_once($payment_unified_path);
} else {
    // Criar funções fallback se o arquivo não existir
    if (!function_exists('render_payment_unified_page')) {
        function render_payment_unified_page() {
            return '<div class="payment-unified">Sistema de pagamento unificado temporariamente indisponível.</div>';
        }
    }
}

// Incluir payment-checkout.php com verificação
$payment_checkout_path = get_template_directory() . '/inc/custom/broker/payment-checkout.php';
if (file_exists($payment_checkout_path)) {
    require_once($payment_checkout_path);
} else {
    // Criar funções fallback se o arquivo não existir
    if (!function_exists('render_payment_checkout_page')) {
        function render_payment_checkout_page() {
            return '<div class="payment-checkout">Checkout de pagamento temporariamente indisponível.</div>';
        }
    }
}

/**
 * Shortcode para exibir o carrossel de imóveis patrocinados
 */



// Shortcode para o Dashboard de Corretores
function broker_dashboard_shortcode($atts) {
    error_log('Shortcode do dashboard de corretores sendo executado');
    
    // Incluir o arquivo que contém a função broker_dashboard_content
    $dashboard_file = get_stylesheet_directory() . '/inc/custom/broker/dashboard.php';
    if (file_exists($dashboard_file)) {
        error_log('Arquivo do dashboard encontrado: ' . $dashboard_file);
        require_once($dashboard_file);
        
        // Executar a função que renderiza o dashboard
        if (function_exists('broker_dashboard_content')) {
            error_log('Função broker_dashboard_content encontrada, executando...');
            try {
                $content = broker_dashboard_content($atts);
                error_log('Dashboard renderizado com sucesso');
                return $content;
            } catch (Exception $e) {
                error_log('Erro ao renderizar o dashboard: ' . $e->getMessage());
                return '<div class="notice notice-error">
                    <p>Erro ao carregar o dashboard de corretores.</p>
                    <p>Por favor, contate o suporte técnico informando o erro: DASH-003</p>
                </div>';
            }
        } else {
            error_log('Função broker_dashboard_content não encontrada');
            return '<div class="notice notice-error">
                <p>Erro ao carregar o dashboard de corretores.</p>
                <p>Por favor, contate o suporte técnico informando o erro: DASH-004</p>
            </div>';
        }
    } else {
        error_log('Arquivo do dashboard não encontrado: ' . $dashboard_file);
        return '<div class="notice notice-error">
            <p>Dashboard de corretores temporariamente indisponível.</p>
            <p>Por favor, contate o suporte técnico informando o erro: DASH-005</p>
        </div>';
    }
}

// Registrar o shortcode e verificar se foi registrado corretamente
add_action('init', function() {
    add_shortcode('broker_dashboard', 'broker_dashboard_shortcode');
    
    if (!shortcode_exists('broker_dashboard')) {
        error_log('Shortcode broker_dashboard não foi registrado corretamente');
    } else {
        error_log('Shortcode broker_dashboard registrado com sucesso');
    }
});

// Garantir que os scripts necessários sejam carregados
add_action('wp_enqueue_scripts', function() {
    if (has_shortcode(get_post()->post_content, 'broker_dashboard')) {
        error_log('Página contém shortcode broker_dashboard, carregando scripts...');
        
        wp_enqueue_script('jquery');
        wp_enqueue_script('react', 'https://unpkg.com/react@17/umd/react.production.min.js', array('jquery'), '17.0.0', true);
        wp_enqueue_script('react-dom', 'https://unpkg.com/react-dom@17/umd/react-dom.production.min.js', array('react'), '17.0.0', true);
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js', array('jquery'), '3.7.1', true);
        
        // Verificar se os scripts foram enfileirados corretamente
        $scripts_status = array();
        $scripts_status['jquery'] = wp_script_is('jquery', 'enqueued');
        $scripts_status['react'] = wp_script_is('react', 'enqueued');
        $scripts_status['react_dom'] = wp_script_is('react-dom', 'enqueued');
        $scripts_status['chart_js'] = wp_script_is('chart-js', 'enqueued');
        
        error_log('Status dos scripts: ' . json_encode($scripts_status));
    }
});

// Shortcode universal para pagamentos
function socasa_payment_shortcode($atts) {
    // Incluir o arquivo do sistema de pagamento unificado
    $payment_unified_file = get_template_directory() . '/inc/custom/broker/payment-unified.php';
    $payment_product_file = get_template_directory() . '/inc/custom/broker/payment-product.php';
    
    $files_exist = true;
    
    if (file_exists($payment_unified_file)) {
        include_once($payment_unified_file);
    } else {
        $files_exist = false;
    }
    
    if (file_exists($payment_product_file)) {
        include_once($payment_product_file);
    } else {
        $files_exist = false;
    }
    
    if (!$files_exist) {
        return '<p class="payment-error">Sistema de pagamento temporariamente indisponível.</p>';
    }
    
    // Extrair atributos
    $atts = shortcode_atts(array(
        'entity_id' => 0,
        'product_id' => '', // opcional, será detectado automaticamente se não informado
        'success_url' => '',
        'show_terms' => 'true'
    ), $atts);
    
    // Verificar se existe um entity_id
    if (empty($atts['entity_id'])) {
        // Verificar se é possível obter da URL
        if (isset($_GET['id'])) {
            $atts['entity_id'] = intval($_GET['id']);
        } elseif (isset($_GET['entity_id'])) {
            $atts['entity_id'] = intval($_GET['entity_id']);
        } elseif (isset($_GET['immobile_id'])) {
            $atts['entity_id'] = intval($_GET['immobile_id']);
        }
    }
    
    // Se ainda estiver vazio, retornar erro
    if (empty($atts['entity_id'])) {
        return '<p class="payment-error">É necessário especificar a entidade para pagamento.</p>';
    }
    
    global $unified_payment_system;
    
    // Se não tiver produto_id especificado, tentar detectar automaticamente
    if (empty($atts['product_id']) && isset($unified_payment_system) && method_exists($unified_payment_system, 'detect_product')) {
        $detected_product_id = $unified_payment_system->detect_product($atts['entity_id']);
        if ($detected_product_id) {
            $atts['product_id'] = $detected_product_id;
        }
    }
    
    // Carregar o produto para verificar se existe
    if (!empty($atts['product_id']) && function_exists('socasa_get_product')) {
        $product = socasa_get_product($atts['product_id']);
        if (!$product) {
            return '<p class="payment-error">Produto não encontrado ou inválido.</p>';
        }
    }
    
    // Converter show_terms para booleano
    $show_terms = ($atts['show_terms'] === 'true');
    
    // Usar o formulário de pagamento unificado
    if (function_exists('render_unified_payment_form')) {
        return render_unified_payment_form(array(
            'product_id' => $atts['product_id'],
            'entity_id' => $atts['entity_id'],
            'success_url' => $atts['success_url'],
            'show_terms' => $show_terms
        ));
    } else {
        return '<p class="payment-error">Sistema de pagamento temporariamente indisponível.</p>';
    }
}
add_shortcode('socasa_payment', 'socasa_payment_shortcode');

// Manter os shortcodes antigos por compatibilidade, mas usando a nova implementação
function highlight_payment_form_shortcode($atts) {
    // Extrair atributos
    $atts = shortcode_atts(array(
        'immobile_id' => 0,
    ), $atts);
    
    return socasa_payment_shortcode(array(
        'entity_id' => $atts['immobile_id'],
        'product_id' => 'highlight',
        'success_url' => '/corretores/configuracoes-pagamento/',
        'show_terms' => 'true'
    ));
}
add_shortcode('highlight_payment_form', 'highlight_payment_form_shortcode');

// Shortcode para o pagamento durante a publicação de imóveis
function publication_payment_shortcode($atts) {
    // Extrair atributos
    $atts = shortcode_atts(array(
        'immobile_id' => 0,
    ), $atts);
    
    return socasa_payment_shortcode(array(
        'entity_id' => $atts['immobile_id'],
        'product_id' => 'basic_publication',
        'success_url' => '/corretores/meus-imoveis/',
        'show_terms' => 'false'
    ));
}
add_shortcode('publication_payment', 'publication_payment_shortcode');

/**
 * Shortcode para exibir configurações de pagamento
 */
function payment_settings_shortcode() {
    if (!is_user_logged_in()) {
        return '<div class="alert alert-warning">Você precisa estar logado para visualizar suas configurações de pagamento.</div>';
    }
    
    // Obter o ID do usuário
    $user_id = get_current_user_id();
    
    // Obter os cartões salvos
    $saved_cards = get_user_meta($user_id, 'mercadopago_cards', true);
    
    if (!is_array($saved_cards)) {
        $saved_cards = array();
    }
    
    // Identificar cartão padrão
    $default_card_id = false;
    foreach ($saved_cards as $id => $card) {
        if (isset($card['is_default']) && $card['is_default']) {
            $default_card_id = $id;
            break;
        }
    }
    
    // Iniciar o buffer de saída
    ob_start();
    
    // Container principal
    echo '<div class="payment-settings-container">';
    
    // Título da seção
    echo '<h2 class="section-title">Configurações de Pagamento</h2>';
    
    // Mensagem de ambiente de teste
    echo '<div class="alert alert-info mb-4">
        <strong>Ambiente de teste:</strong> Este é um ambiente simulado para fins de desenvolvimento.
        Os cartões e transações exibidos são fictícios.
    </div>';
    
    // Seção de cartões
    echo '<h3>Meus Cartões</h3>';
    
    if (!empty($saved_cards)) {
        echo '<div class="cards-container">';
        
        foreach ($saved_cards as $id => $card) {
            $is_default = isset($card['is_default']) && $card['is_default'];
            $card_class = $is_default ? 'card-item default' : 'card-item';
            $brand = isset($card['payment_method_id']) ? $card['payment_method_id'] : 'unknown';
            $last_digits = isset($card['last_four_digits']) ? $card['last_four_digits'] : '****';
            $expiry_month = isset($card['expiration_month']) ? $card['expiration_month'] : '**';
            $expiry_year = isset($card['expiration_year']) ? $card['expiration_year'] : '****';
            
            echo '<div class="' . $card_class . '">';
            
            if ($is_default) {
                echo '<span class="default-badge">Padrão</span>';
            }
            
            echo '<div class="card-brand">';
            echo '<img src="' . get_card_brand_logo($brand) . '" alt="' . $brand . '">';
            echo '<span>' . get_card_brand_name($brand) . '</span>';
            echo '</div>';
            
            echo '<div class="card-details">';
            echo '<div class="card-number">**** **** **** ' . $last_digits . '</div>';
            echo '<div class="card-expiry">Válido até: ' . $expiry_month . '/' . $expiry_year . '</div>';
            echo '</div>';
            
            echo '<div class="card-actions">';
            
            if (!$is_default) {
                echo '<button class="button button-secondary set-default-card" data-card-id="' . $id . '">Definir como padrão</button>';
            }
            
            echo '<button class="button button-danger delete-card" data-card-id="' . $id . '">Remover</button>';
            echo '</div>';
            
            echo '</div>';
        }
        
        echo '</div>';
    } else {
        echo '<div class="alert alert-warning">Você ainda não possui cartões cadastrados.</div>';
    }
    
    // Seção para adicionar novo cartão
    echo '<div class="add-card-section">';
    echo '<button id="add-new-card" class="button button-primary">Adicionar novo cartão</button>';
    echo '</div>';
    
    // Área para histórico de transações
    echo '<div class="transactions-section" style="margin-top: 30px;">';
    echo '<h3>Histórico de Transações</h3>';
    echo '<p>Aqui você pode ver suas transações recentes:</p>';
    
    // Dados de transações simuladas
    echo '<table class="transactions-table">
        <thead>
            <tr>
                <th>Data</th>
                <th>Descrição</th>
                <th>Valor</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>' . date('d/m/Y', strtotime('-2 days')) . '</td>
                <td>Destaque de Imóvel Premium</td>
                <td>R$ 149,90</td>
                <td><span class="status-approved">Aprovado</span></td>
            </tr>
            <tr>
                <td>' . date('d/m/Y', strtotime('-10 days')) . '</td>
                <td>Plano de Anúncios Plus</td>
                <td>R$ 299,90</td>
                <td><span class="status-approved">Aprovado</span></td>
            </tr>
            <tr>
                <td>' . date('d/m/Y', strtotime('-15 days')) . '</td>
                <td>Destaque de Imóvel Básico</td>
                <td>R$ 79,90</td>
                <td><span class="status-approved">Aprovado</span></td>
            </tr>
        </tbody>
    </table>';
    
    echo '</div>';
    
    echo '</div>'; // Fim do container principal
    
    // Obter o conteúdo do buffer
    $output = ob_get_clean();
    
    return $output;
}
add_shortcode('payment_settings', 'payment_settings_shortcode');

/**
 * Shortcode para o novo formulário de checkout multi-produtos
 * 
 * @param array $atts Atributos do shortcode
 * @return string HTML do formulário de checkout
 */
function multi_checkout_shortcode($atts) {
    wp_enqueue_style('payment-styles', get_template_directory_uri() . '/inc/custom/broker/assets/css/payment-styles.css');
    
    // Verificar se a função existe e chamá-la
    if (function_exists('multi_product_checkout_shortcode')) {
        return multi_product_checkout_shortcode($atts);
    }
    
    return '<div class="notice notice-warning">Sistema de checkout indisponível no momento. Por favor, contate o administrador.</div>';
}

/**
 * Registra os scripts e estilos necessários para o sistema de pagamento
 */
function register_payment_assets() {
    // Registrar e enfileirar CSS
    wp_register_style('payment-styles', get_template_directory_uri() . '/inc/custom/broker/assets/css/payment-styles.css');
    
    // Verificar se estamos em uma página que contém shortcodes de pagamento
    global $post;
    if (is_object($post) && 
        (has_shortcode($post->post_content, 'highlight_payment_form') || 
         has_shortcode($post->post_content, 'publication_payment') || 
         has_shortcode($post->post_content, 'socasa_payment') || 
         has_shortcode($post->post_content, 'multi_checkout'))) {
        
        wp_enqueue_style('payment-styles');
        
        // Incluir o SDK do Mercado Pago
        wp_enqueue_script('mercadopago-sdk', 'https://sdk.mercadopago.com/js/v2', array(), null, true);
    }
}
add_action('wp_enqueue_scripts', 'register_payment_assets');

// Garantir que este arquivo seja carregado
function load_broker_shortcodes() {
    // Registrar todos os shortcodes
    add_shortcode('broker_dashboard', 'broker_dashboard_shortcode');
    add_shortcode('socasa_payment', 'socasa_payment_shortcode');
    add_shortcode('highlight_payment_form', 'highlight_payment_form_shortcode');
    add_shortcode('publication_payment', 'publication_payment_shortcode');
    add_shortcode('payment_settings', 'payment_settings_shortcode');
    
    // Verificar se a função highlight_payment_shortcode existe e registrá-la
    if (function_exists('highlight_payment_shortcode')) {
        add_shortcode('highlight_payment', 'highlight_payment_shortcode');
    }
}

// Carregar na inicialização do WordPress
add_action('init', 'load_broker_shortcodes');

?>
