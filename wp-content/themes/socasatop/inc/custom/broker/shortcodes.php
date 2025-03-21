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
    
    // Verificar se o ID do imóvel foi passado pela URL
    if (isset($_GET['immobile_id'])) {
        $immobile_id = intval($_GET['immobile_id']);
    } else {
        $immobile_id = intval($atts['immobile_id']);
    }
    
    if ($immobile_id <= 0) {
        return '<div class="error-message">ID do imóvel não fornecido ou inválido.</div>';
    }
    
    // Verificar se o imóvel existe
    $immobile = get_post($immobile_id);
    if (!$immobile) {
        return '<div class="error-message">Imóvel não encontrado.</div>';
    }
    
    // Incluir os scripts e estilos necessários
    wp_enqueue_style('highlight-css', get_stylesheet_directory_uri() . '/inc/custom/broker/assets/css/highlight.css', array(), '1.0.7');
    wp_enqueue_script('mercadopago-js', 'https://sdk.mercadopago.com/js/v2', array(), null, true);
    wp_enqueue_script('highlight-payment-js', get_stylesheet_directory_uri() . '/inc/custom/broker/assets/js/highlight-payment.js', array('jquery'), '1.0.7', true);
    
    // Verificar se o arquivo de processamento do destaque existe
    $highlight_file = get_template_directory() . '/inc/custom/broker/highlight-payment.php';
    if (!file_exists($highlight_file)) {
        error_log('Arquivo de destaque não encontrado: ' . $highlight_file);
        return '<div class="error-message">Sistema de destaque temporariamente indisponível.</div>';
    }
    
    // Incluir o arquivo do sistema de destaque
    require_once($highlight_file);
    
    // Usar diretamente a função de renderização do formulário
    ob_start();
    
    if (function_exists('render_highlight_payment_form')) {
        echo render_highlight_payment_form($immobile_id);
    } else {
        echo '<div class="error-message">O sistema de pagamento para destaque está temporariamente indisponível.</div>';
        error_log('Função render_highlight_payment_form não encontrada');
    }
    
    return ob_get_clean();
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
        'product_id' => 'publication',
        'success_url' => '/corretores/meus-imoveis/',
        'show_terms' => 'true'
    ));
}
add_shortcode('publication_payment', 'publication_payment_shortcode');

/**
 * Shortcode para o link de logout seguro com nonce
 * 
 * @return string HTML do link de logout
 */
function secure_logout_shortcode() {
    $logout_url = wp_logout_url(home_url());
    return '<a href="' . esc_url($logout_url) . '" class="logout-button">Sair</a>';
}
add_shortcode('secure_logout', 'secure_logout_shortcode');

/**
 * Shortcode para exibir configurações de pagamento
 */
function payment_settings_shortcode() {
    if (!is_user_logged_in()) {
        return '<div class="alert alert-warning">Você precisa estar logado para visualizar suas configurações de pagamento.</div>';
    }
    
    // Obter o ID do usuário
    $user_id = get_current_user_id();
    
    // Obter os cartões salvos usando a função melhorada
    $saved_cards = get_user_mercadopago_cards($user_id);
    
    // Identificar cartão padrão
    $default_card_id = get_user_meta($user_id, 'default_payment_card', true);
    
    // Iniciar o buffer de saída
    ob_start();
    
    // Container principal
    echo '<div class="payment-settings-container" style="max-width: 1200px; margin: 0 auto; padding: 20px;">';
    
    // Título da seção
    echo '<h2 class="section-title">Configurações de Pagamento</h2>';
    
    // Injetar o JavaScript para definir o ajaxurl globalmente
    echo '<script type="text/javascript">
        var ajaxurl = "' . admin_url('admin-ajax.php') . '";
        // Definir variáveis para depuração
        window.debug_info = {
            ajax_url_set: true,
            user_id: ' . $user_id . ',
            timestamp: "' . date('Y-m-d H:i:s') . '"
        };
    </script>';
    
    // Seção de cartões salvos
    echo '<div class="saved-cards-section" style="margin-top: 20px; margin-bottom: 30px;">';
    echo '<h3>Cartões Salvos</h3>';
    
    if (!empty($saved_cards)) {
        echo '<div class="saved-cards-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;">';
        
        foreach ($saved_cards as $card_id => $card) {
            // Garantir que todos os campos necessários existam
            $last_four = isset($card['last_four']) ? $card['last_four'] : (isset($card['last_four_digits']) ? $card['last_four_digits'] : '****');
            $brand = isset($card['brand']) ? $card['brand'] : (isset($card['payment_method_id']) ? $card['payment_method_id'] : 'card');
            $expiry_month = isset($card['expiry_month']) ? $card['expiry_month'] : (isset($card['expiration_month']) ? $card['expiration_month'] : '**');
            $expiry_year = isset($card['expiry_year']) ? $card['expiry_year'] : (isset($card['expiration_year']) ? $card['expiration_year'] : '****');
            
            // Estilo do cartão
            echo '<div class="saved-card" style="background-color: #f8f9fa; padding: 15px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">';
            
            // Indicador de cartão padrão se aplicável
            if ($card_id === $default_card_id) {
                echo '<div class="default-label" style="background-color: #28a745; color: white; display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 12px; margin-bottom: 10px;">Cartão Padrão</div>';
            }
            
            echo '<div class="card-details" style="display: flex; align-items: center; margin-bottom: 15px;">';
            
            // Logo da bandeira do cartão
            echo '<div class="card-brand" style="margin-right: 15px;">';
            $brand_logo_url = get_template_directory_uri() . '/inc/custom/broker/assets/images/card-brands/' . strtolower($brand) . '.png';
            $default_card_logo = get_template_directory_uri() . '/inc/custom/broker/assets/images/card-brands/generic-card.png';
            echo '<img src="' . esc_url($brand_logo_url) . '" alt="' . esc_attr($brand) . '" style="width: 50px; height: auto;" onerror="this.src=\'' . $default_card_logo . '\';">';
            echo '</div>';
            
            // Informações do cartão
            echo '<div class="card-info">';
            echo '<div class="card-number" style="font-size: 16px; font-weight: bold;">•••• •••• •••• ' . esc_html($last_four) . '</div>';
            echo '<div class="card-expiry" style="color: #666; font-size: 14px;">Validade: ' . esc_html($expiry_month) . '/' . esc_html($expiry_year) . '</div>';
            echo '</div>';
            
            echo '</div>';
            
            // Botões de ação
            echo '<div class="card-actions" style="display: flex; justify-content: space-between;">';
            
            // Botão para definir como padrão (apenas se não for o padrão atual)
            if ($card_id !== $default_card_id) {
                echo '<button class="set-default-card" data-card-id="' . esc_attr($card_id) . '" style="background-color: #007bff; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer;">Definir como padrão</button>';
            } else {
                echo '<div style="width: 140px;"></div>'; // Espaçador
            }
            
            // Botão para remover o cartão
            echo '<button class="remove-card" data-card-id="' . esc_attr($card_id) . '" style="background-color: #dc3545; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer;">Remover</button>';
            
            echo '</div>';
            echo '</div>'; // Fim do cartão
        }
        
        echo '</div>'; // Fim do grid
    } else {
        echo '<div class="no-cards-message" style="padding: 20px; background-color: #f8f9fa; border-radius: 8px; text-align: center; margin-top: 20px;">
            <p>Você ainda não possui cartões salvos.</p>
        </div>';
    }
    
    echo '</div>'; // Fim da seção de cartões
    
    // Botão para adicionar novo cartão
    echo '<div class="add-card-section" style="margin-top: 20px; text-align: center;">';
    echo '<button id="add-new-card" class="button button-primary" style="padding: 10px 20px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px;">Adicionar novo cartão</button>';
    echo '</div>';
    
    // Div para exibir mensagens
    echo '<div id="result-message" style="margin-top: 20px;"></div>';
    
    // Div para o formulário de novo cartão
    echo '<div id="card-form-container" style="display: none; margin-top: 20px; padding: 20px; background-color: #f8f9fa; border-radius: 8px;">';
    echo '<h3>Novo Cartão</h3>';
    echo '<form id="card-form" style="margin-top: 15px;">';
    echo '</form>';
    echo '<div style="margin-top: 15px; text-align: right;">';
    echo '<button id="save-card" class="button button-primary" style="background-color: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer;">Salvar cartão</button>';
    echo '<button id="cancel-card-form" class="button" style="background-color: #6c757d; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; margin-left: 10px;">Cancelar</button>';
    echo '</div>';
    echo '</div>';
    
    echo '</div>'; // Fim do container principal
    
    // Retornar o conteúdo gerado
    return ob_get_clean();
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
 * A função register_payment_assets foi movida para payment-loader.php
 * para evitar duplicação e conflitos
 */
/*
function register_payment_assets() {
    // Registrar e enfileirar CSS
    wp_register_style('payment-styles', get_stylesheet_directory_uri() . '/inc/custom/broker/assets/css/payment-styles.css');
    
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
*/

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
