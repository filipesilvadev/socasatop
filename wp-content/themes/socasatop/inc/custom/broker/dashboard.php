<?php
/**
 * Dashboard para corretores
 */

// Função para renderizar o conteúdo do dashboard
function broker_dashboard_content($atts) {
    error_log('Iniciando broker_dashboard_content');
    
    if (!is_user_logged_in()) {
        error_log('Dashboard de corretores: Usuário não está logado');
        return '<div class="notice notice-error">
            <p>Você precisa estar logado para acessar o painel de corretor.</p>
            <p><a href="' . wp_login_url(get_permalink()) . '" class="button">Fazer Login</a></p>
        </div>';
    }

    $user = wp_get_current_user();
    error_log('Dashboard de corretores: Usuário #' . $user->ID . ' com roles: ' . implode(', ', $user->roles));
    
    if (!in_array('author', (array) $user->roles) && !in_array('administrator', (array) $user->roles)) {
        error_log('Dashboard de corretores: Usuário não tem permissão de corretor');
        return '<div class="notice notice-error">
            <p>Acesso restrito a corretores.</p>
            <p>Se você é um corretor e está vendo esta mensagem, por favor entre em contato com o suporte.</p>
        </div>';
    }

    // Verificar se os arquivos necessários existem
    $js_file = get_stylesheet_directory() . '/inc/custom/broker/assets/js/broker-dashboard.js';
    if (!file_exists($js_file)) {
        error_log('Dashboard de corretores: Arquivo JavaScript não encontrado: ' . $js_file);
        return '<div class="notice notice-error">
            <p>Erro ao carregar recursos necessários.</p>
            <p>Por favor, contate o suporte técnico informando o erro: DASH-001</p>
        </div>';
    }

    // Carregar jQuery primeiro (embora normalmente já esteja carregado no WordPress)
    wp_enqueue_script('jquery');

    // Carregar React e Chart.js para o dashboard dinâmico
    wp_enqueue_script('react', 'https://unpkg.com/react@17/umd/react.production.min.js', array('jquery'), '17.0.0', true);
    wp_enqueue_script('react-dom', 'https://unpkg.com/react-dom@17/umd/react-dom.production.min.js', array('react'), '17.0.0', true);
    wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js', array('jquery'), '3.7.1', true);
    
    // Carregar Font Awesome
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css', array(), '5.15.3');
    
    // Carregar estilos do dashboard
    wp_enqueue_style('broker-dashboard', get_stylesheet_directory_uri() . '/inc/custom/broker/assets/css/broker-dashboard.css', array(), wp_rand());
    
    // Carregar o script do dashboard com versão aleatória para evitar cache
    $version = wp_rand();
    wp_enqueue_script('broker-dashboard', get_stylesheet_directory_uri() . '/inc/custom/broker/assets/js/broker-dashboard.js', array('jquery', 'react', 'react-dom', 'chart-js'), $version, true);
    
    // Verificar se os scripts foram enfileirados corretamente
    $scripts_status = array();
    $scripts_status['jquery'] = wp_script_is('jquery', 'enqueued');
    $scripts_status['react'] = wp_script_is('react', 'enqueued');
    $scripts_status['react_dom'] = wp_script_is('react-dom', 'enqueued');
    $scripts_status['chart_js'] = wp_script_is('chart-js', 'enqueued');
    $scripts_status['broker_dashboard'] = wp_script_is('broker-dashboard', 'enqueued');
    
    error_log('Dashboard de corretores: Status dos scripts - ' . json_encode($scripts_status));
    
    if (array_search(false, $scripts_status) !== false) {
        error_log('Dashboard de corretores: Alguns scripts não foram enfileirados corretamente');
        return '<div class="notice notice-error">
            <p>Erro ao carregar recursos necessários.</p>
            <p>Por favor, contate o suporte técnico informando o erro: DASH-002</p>
        </div>';
    }
    
    // Localizar script com variáveis necessárias para as requisições AJAX
    wp_localize_script('broker-dashboard', 'site', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ajax_nonce'),
        'theme_url' => get_stylesheet_directory_uri(),
        'debug' => WP_DEBUG,
        'user' => array(
            'id' => $user->ID,
            'name' => $user->display_name,
            'roles' => $user->roles
        ),
        'scripts_loaded' => $scripts_status
    ));

    // Adicionar script inline para debug
    wp_add_inline_script('broker-dashboard', '
        console.log("Dashboard de corretores: Iniciando carregamento");
        console.log("Status dos scripts:", site.scripts_loaded);
        console.log("Informações do usuário:", site.user);
        
        document.addEventListener("DOMContentLoaded", function() {
            console.log("Dashboard de corretores: DOM carregado");
            if (typeof React === "undefined") {
                console.error("React não está carregado");
            }
            if (typeof ReactDOM === "undefined") {
                console.error("ReactDOM não está carregado");
            }
            if (typeof Chart === "undefined") {
                console.error("Chart.js não está carregado");
            }
            if (typeof jQuery === "undefined") {
                console.error("jQuery não está carregado");
            }
        });
    ', 'before');

    // Inicializar o dashboard
    ob_start();
    
    // Função para garantir que todas as URLs sejam HTTPS
    $ensure_https = function($url) {
        return str_replace('http://', 'https://', $url);
    };
    
    // Certificar-se de que o nonce esteja disponível no JavaScript
    echo '<script type="text/javascript">
        var brokerDashboardData = {
            ajax_url: "' . admin_url('admin-ajax.php') . '",
            broker_dashboard_nonce: "' . wp_create_nonce('broker_dashboard_nonce') . '",
            highlight_action_nonce: "' . wp_create_nonce('highlight_action_nonce') . '"
        };
        
        // Copiar dados para o objeto site se existir
        if (typeof site !== "undefined") {
            site.broker_dashboard_nonce = brokerDashboardData.broker_dashboard_nonce;
            site.highlight_action_nonce = brokerDashboardData.highlight_action_nonce;
        } else {
            var site = brokerDashboardData;
        }
    </script>';

    ?>
    <div class="broker-dashboard">
        <!-- Seção para o gráfico de métricas -->
        <div class="metrics-section">
            <h2>Métricas do Corretor</h2>
            <div class="chart-container" style="position: relative; height: 300px; margin-top: 20px;">
                <canvas id="broker-metrics-chart"></canvas>
            </div>
        </div>
        
        <!-- Contêiner para a aplicação React -->
        <div id="react-broker-dashboard"></div>
        
        <!-- Interface estática para listagem de imóveis -->
        <h2>Meus Imóveis</h2>
        
        <div class="dashboard-controls">
            <div class="dashboard-controls-left">
                <label class="bulk-select-container">
                    <input type="checkbox" id="select-all-properties">
                    <span class="checkbox-label">Selecionar Todos</span>
                </label>
                <div class="bulk-actions" style="display: none;">
                    <button id="bulk-delete-btn" class="action-button delete-button">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <div class="dashboard-controls-right">
                <a href="https://socasatop.com.br/adicionar-imoveis/" class="add-property-button">
                    <i class="fas fa-plus"></i> Adicionar Imóvel
                </a>
                <a href="/corretores/configuracoes-pagamento/" class="payment-settings-button">
                    <i class="fas fa-credit-card"></i> Configurações de Pagamento
                </a>
            </div>
        </div>
        
        <div class="property-list">
            <?php
            $user_id = get_current_user_id();
            $args = array(
                'post_type' => 'immobile', // Corrigido para 'immobile' em vez de 'property'
                'posts_per_page' => -1,
                'post_status' => array('publish', 'pending', 'draft'), // Incluir imóveis pendentes e rascunhos
                'meta_query' => array(
                    array(
                        'key' => 'broker',
                        'value' => $user_id,
                        'compare' => '='
                    )
                )
            );
            
            $properties = get_posts($args);
            
            if (empty($properties)) {
                echo '<div class="no-properties-message">';
                echo '<p>Você ainda não tem imóveis cadastrados.</p>';
                echo '<p><a href="/corretores/novo-imovel/" class="add-property-button">Adicionar seu primeiro imóvel</a></p>';
                echo '</div>';
            } else {
                foreach ($properties as $property) {
                    $property_id = $property->ID;
                    $property_title = $property->post_title;
                    $property_permalink = get_permalink($property_id);
                    
                    // Meta informações
                    $city = get_post_meta($property_id, 'city', true);
                    $street = get_post_meta($property_id, 'street', true);
                    $price = get_post_meta($property_id, 'price', true);
                    $property_type = get_post_meta($property_id, 'property_type', true);
                    
                    // Status de destaque
                    $is_sponsored = get_post_meta($property_id, 'is_sponsored', true) === 'yes';
                    $highlight_paused = get_post_meta($property_id, 'highlight_paused', true) === 'yes';
                    
                    // Obter a imagem em destaque
                    $thumbnail = get_the_post_thumbnail_url($property_id, 'medium');
                    
                    // Se não encontrou thumbnail padrão, tenta obter da galeria
                    if (!$thumbnail) {
                        $gallery = get_post_meta($property_id, 'immobile_gallery', true);
                        if ($gallery) {
                            $gallery_ids = explode(',', $gallery);
                            if (!empty($gallery_ids)) {
                                $thumbnail = wp_get_attachment_image_url($gallery_ids[0], 'medium');
                            }
                        }
                    }
                    
                    // Corrigir URL para HTTPS
                    if ($thumbnail) {
                        $thumbnail = $ensure_https($thumbnail);
                    }
                    
                    // Se ainda não houver imagem, usar uma imagem padrão
                    if (!$thumbnail) {
                        $thumbnail = $ensure_https(get_stylesheet_directory_uri() . '/assets/images/no-image.jpg');
                    }
                    
                    // Obter valor do imóvel
                    $formatted_price = !empty($price) ? 'R$ ' . number_format((float) $price, 2, ',', '.') : 'Valor não informado';
                    
                    // Obter número de visualizações
                    $views = get_post_meta($property_id, 'property_views', true);
                    if (empty($views)) {
                        $views = 0;
                    }
                    
                    // Obter data de publicação
                    $published_date = get_the_date('d/m/Y', $property_id);
                    
                    // Verificar status do imóvel
                    $status = $property->post_status;
                    if ($status === 'publish') {
                        $status_label = 'Publicado';
                        $status_class = 'status-published';
                    } elseif ($status === 'pending') {
                        $status_label = 'Pendente de Aprovação';
                        $status_class = 'status-pending';
                    } else {
                        $status_label = 'Rascunho';
                        $status_class = 'status-draft';
                    }
                    
                    ?>
                    <div class="property-item<?php echo $highlight_paused ? ' paused' : ''; ?>" data-property-id="<?php echo $property_id; ?>">
                        <div class="property-checkbox-container">
                            <input type="checkbox" class="property-checkbox" data-id="<?php echo $property_id; ?>">
                        </div>
                        <div class="property-image">
                            <?php if ($thumbnail) : ?>
                                <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr($property_title); ?>">
                            <?php else : ?>
                                <div class="no-image">Sem imagem</div>
                            <?php endif; ?>
                        </div>
                        <div class="property-details">
                            <h3 class="property-title">
                                <a href="<?php echo esc_url($property_permalink); ?>" target="_blank">
                                    <?php echo $property_title; ?>
                                </a>
                                <?php if ($is_sponsored && !$highlight_paused) : ?>
                                    <span class="sponsored-tag">Destaque</span>
                                <?php endif; ?>
                            </h3>
                            <div class="property-meta">
                                <?php if (!empty($formatted_price)): ?>
                                <span class="property-price"><?php echo $formatted_price; ?></span>
                                <?php endif; ?>
                                <span class="property-views"><i class="fas fa-eye"></i> <?php echo $views; ?> visualizações</span>
                                <span class="property-date"><i class="fas fa-calendar-alt"></i> Publicado em <?php echo $published_date; ?></span>
                                <span class="property-status <?php echo $status_class; ?>"><?php echo $status_label; ?></span>
                            </div>
                        </div>
                        <div class="property-actions">
                            <a href="<?php echo home_url('/corretores/editar-imovel/?property_id=' . $property_id); ?>" class="action-button" title="Editar Imóvel">
                                <i class="fas fa-edit"></i>
                            </a>
                            
                            <?php if ($is_sponsored) {
                                if ($highlight_paused === 'yes') { ?>
                                    <a href="<?php echo home_url('/corretores/destacar-imovel/?immobile_id=' . $property_id); ?>" class="action-button highlight-button" title="Reativar Destaque">
                                        <i class="fas fa-star"></i> <span class="button-label">Reativar</span>
                                    </a>
                                <?php } else { ?>
                                    <button data-id="<?php echo $property_id; ?>" class="action-button pause-highlight-button" title="Pausar Destaque">
                                        <i class="fas fa-pause"></i> <span class="button-label">Pausar</span>
                                    </button>
                                <?php }
                            } else { ?>
                                <a href="<?php echo home_url('/corretores/destacar-imovel/?immobile_id=' . $property_id); ?>" class="action-button highlight-button" title="Destacar Imóvel">
                                    <i class="fas fa-star"></i> <span class="button-label">Destacar</span>
                                </a>
                            <?php } ?>
                            
                            <button data-id="<?php echo $property_id; ?>" class="action-button delete-button" title="Excluir Imóvel">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <?php
                }
            }
            ?>
        </div>
    </div>
    
    <style>
        /* Estilos gerais */
        .broker-dashboard {
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        h2 {
            margin-bottom: 20px;
            color: #333;
        }
        
        /* Seção de métricas */
        .metrics-section {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .chart-container {
            width: 100%;
            height: 300px;
        }
        
        /* Controles do painel */
        .dashboard-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .dashboard-controls-left {
            display: flex;
            align-items: center;
        }
        
        .bulk-select-container {
            display: flex;
            align-items: center;
            margin-right: 10px;
        }
        
        .checkbox-label {
            margin-left: 5px;
        }
        
        .bulk-actions {
            display: flex;
            gap: 10px;
        }
        
        .add-property-button, .payment-settings-button {
            display: inline-flex;
            align-items: center;
            background-color: #1e56b3;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            margin-left: 10px;
            transition: background-color 0.2s;
        }
        
        .add-property-button:hover, .payment-settings-button:hover {
            background-color: #174291;
            text-decoration: none;
            color: white;
        }
        
        .payment-settings-button {
            background-color: #2196F3;
        }
        
        .payment-settings-button:hover {
            background-color: #0b7dda;
        }
        
        .add-property-button i, .payment-settings-button i {
            margin-right: 5px;
        }
        
        /* Lista de imóveis */
        .property-list {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .property-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .property-item:last-child {
            border-bottom: none;
        }
        
        .property-checkbox-container {
            margin-right: 15px;
        }
        
        .property-image {
            width: 80px;
            height: 80px;
            margin-right: 20px;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .property-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .no-image {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f5f5f5;
            color: #999;
            font-size: 12px;
            text-align: center;
        }
        
        .property-details {
            flex: 1;
        }
        
        .property-title {
            margin: 0 0 8px 0;
            font-size: 18px;
        }
        
        .property-title a {
            color: #333;
            text-decoration: none;
        }
        
        .property-title a:hover {
            color: #1e56b3;
        }
        
        .sponsored-tag {
            display: inline-block;
            background-color: #4CAF50;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            margin-left: 10px;
            vertical-align: middle;
        }
        
        .property-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            color: #666;
            font-size: 14px;
        }
        
        .property-price {
            font-weight: bold;
            color: #1e56b3;
        }
        
        .property-status {
            font-size: 12px;
            padding: 3px 8px;
            border-radius: 3px;
            display: inline-block;
        }
        
        .status-published {
            background-color: #4CAF50;
            color: white;
        }
        
        .status-pending {
            background-color: #FF9800;
            color: white;
        }
        
        .status-draft {
            background-color: #9E9E9E;
            color: white;
        }
        
        /* Botões de ação */
        .property-actions {
            display: flex;
            gap: 8px;
            margin-left: auto;
        }
        
        .action-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: auto;
            min-width: 36px;
            height: 36px;
            border-radius: 4px;
            background-color: #f5f5f5;
            color: #333;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s, color 0.2s;
            text-decoration: none;
            padding: 0 10px;
        }
        
        .action-button i {
            font-size: 14px;
        }
        
        .action-button .button-label {
            margin-left: 5px;
            font-size: 14px;
        }
        
        .edit-button {
            background-color: #f5f5f5;
            color: #333;
        }
        
        .edit-button:hover {
            background-color: #e0e0e0;
            color: #333;
            text-decoration: none;
        }
        
        .highlight-button {
            background-color: #4CAF50;
            color: white;
        }
        
        .highlight-button:hover {
            background-color: #43a047;
            color: white;
            text-decoration: none;
        }
        
        .pause-highlight-button {
            background-color: #ff9800;
            color: white;
        }
        
        .pause-highlight-button:hover {
            background-color: #f57c00;
            color: white;
        }
        
        .delete-button {
            background-color: #f44336;
            color: white;
        }
        
        .delete-button:hover {
            background-color: #e53935;
            color: white;
        }
        
        /* Mensagem de sem imóveis */
        .no-properties-message {
            padding: 30px;
            text-align: center;
            color: #666;
        }
        
        .no-properties-message p {
            margin-bottom: 20px;
        }
        
        .no-properties-message .add-property-button {
            display: inline-block;
        }
        
        /* Responsividade */
        @media (max-width: 768px) {
            .property-item {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .property-image {
                margin-bottom: 10px;
                margin-right: 0;
                width: 100%;
                height: 150px;
            }
            
            .property-details {
                width: 100%;
                margin-bottom: 10px;
            }
            
            .property-actions {
                width: 100%;
                justify-content: flex-start;
                margin-left: 0;
                margin-top: 10px;
            }
            
            .action-button {
                flex: 1;
                justify-content: center;
            }
            
            .action-button .button-label {
                display: inline-block;
            }
        }
    </style>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Manipulador para o checkbox "Selecionar todos"
        const selectAllCheckbox = document.getElementById('select-all-properties');
        const propertyCheckboxes = document.querySelectorAll('.property-checkbox');
        const bulkActions = document.querySelector('.bulk-actions');
        
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                const isChecked = this.checked;
                
                // Selecionar ou desmarcar todos os checkboxes dos imóveis
                propertyCheckboxes.forEach(function(checkbox) {
                    checkbox.checked = isChecked;
                });
                
                // Mostrar ou esconder ações em massa
                if (isChecked) {
                    bulkActions.style.display = 'block';
                } else {
                    bulkActions.style.display = 'none';
                }
            });
        }
        
        // Manipulador para checkboxes individuais
        propertyCheckboxes.forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                // Verificar se algum checkbox está selecionado
                const anyChecked = Array.from(propertyCheckboxes).some(cb => cb.checked);
                
                // Verificar se todos os checkboxes estão selecionados
                const allChecked = Array.from(propertyCheckboxes).every(cb => cb.checked);
                
                // Atualizar o estado do "Selecionar todos"
                if (selectAllCheckbox) {
                    selectAllCheckbox.checked = allChecked;
                }
                
                // Mostrar ou esconder ações em massa
                if (anyChecked) {
                    bulkActions.style.display = 'block';
                } else {
                    bulkActions.style.display = 'none';
                }
            });
        });
        
        // Manipulador para botão de exclusão em massa
        const bulkDeleteBtn = document.getElementById('bulk-delete-btn');
        if (bulkDeleteBtn) {
            bulkDeleteBtn.addEventListener('click', function() {
                const selectedIds = Array.from(document.querySelectorAll('.property-checkbox:checked'))
                    .map(checkbox => checkbox.dataset.id);
                
                if (selectedIds.length > 0) {
                    if (confirm(`Tem certeza que deseja excluir ${selectedIds.length} imóveis?`)) {
                        // Implementar lógica de exclusão em massa
                        deleteProperties(selectedIds);
                    }
                }
            });
        }
        
        // Função para excluir múltiplos imóveis
        function deleteProperties(ids) {
            // Enviar requisição AJAX para excluir os imóveis
            const xhr = new XMLHttpRequest();
            xhr.open('POST', site.ajax_url, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    
                    if (response.success) {
                        // Remover elementos da página
                        ids.forEach(function(id) {
                            const propertyElement = document.querySelector(`.property-item[data-property-id="${id}"]`);
                            if (propertyElement) {
                                propertyElement.remove();
                            }
                        });
                        
                        // Verificar se não restou nenhum imóvel
                        const remainingProperties = document.querySelectorAll('.property-item');
                        if (remainingProperties.length === 0) {
                            document.querySelector('.property-list').innerHTML = `
                                <div class="no-properties-message">
                                    <p>Você ainda não tem imóveis cadastrados.</p>
                                    <p><a href="https://socasatop.com.br/adicionar-imoveis/" class="add-property-button">Adicionar seu primeiro imóvel</a></p>
                                </div>
                            `;
                        }
                        
                        // Desmarcar "Selecionar todos" e esconder ações em massa
                        if (selectAllCheckbox) {
                            selectAllCheckbox.checked = false;
                        }
                        bulkActions.style.display = 'none';
                    } else {
                        alert(response.data || 'Erro ao excluir imóveis.');
                    }
                } else {
                    alert('Erro ao processar a requisição.');
                }
            };
            
            xhr.onerror = function() {
                alert('Erro ao processar a requisição.');
            };
            
            xhr.send(`action=bulk_delete_immobiles&nonce=${site.nonce}&property_ids=${JSON.stringify(ids)}`);
        }
    });
    </script>
    <?php
    
    return ob_get_clean();
}

// Função para atualizar o JavaScript do dashboard
function update_broker_dashboard_js() {
    $js_path = get_stylesheet_directory() . '/inc/custom/broker/assets/js/broker-dashboard.js';
    
    // Criar diretório de assets se não existir
    $dir_path = dirname($js_path);
    if (!file_exists($dir_path)) {
        try {
            if (!@mkdir($dir_path, 0755, true)) {
                error_log('Não foi possível criar o diretório: ' . $dir_path);
            }
        } catch (Exception $e) {
            error_log('Erro ao criar diretório: ' . $e->getMessage());
        }
    }
    
    $js_content = <<<EOT
(function($) {
    $(document).ready(function() {
        // Variável para controlar exibição dos botões de ações em massa
        let showBulkActions = false;
        
        // Função para formatar o slug do título para URL
        function formatTitleSlug(title) {
            return title.toLowerCase()
                .normalize('NFD').replace(/[\u0300-\u036f]/g, '') // Remove acentos
                .replace(/[^\w\s-]/g, '') // Remove caracteres especiais
                .replace(/\s+/g, '-') // Substitui espaços por hífens
                .replace(/--+/g, '-'); // Remove hífens duplicados
        }
        
        // Verificar se alguma propriedade está selecionada
        function checkSelectedProperties() {
            const hasSelected = $('.property-checkbox:checked').length > 0;
            
            if (hasSelected && !showBulkActions) {
                showBulkActions = true;
                $('.bulk-actions').show();
            } else if (!hasSelected && showBulkActions) {
                showBulkActions = false;
                $('.bulk-actions').hide();
            }
        }
        
        // Atualizar links dos títulos para usar o slug correto
        $('.property-title a').each(function() {
            const title = $(this).text().trim();
            const slug = formatTitleSlug(title);
            const url = '/imovel/' + slug + '/';
            $(this).attr('href', url);
        });
        
        // Selecionar/Deselecionar todos os imóveis
        $('#select-all-properties').on('change', function() {
            const isChecked = $(this).is(':checked');
            $('.property-checkbox').prop('checked', isChecked);
            checkSelectedProperties();
        });
        
        // Checkboxes individuais
        $('.property-checkbox').on('change', function() {
            checkSelectedProperties();
            
            // Verificar se todos estão selecionados para marcar o "selecionar todos"
            const totalCheckboxes = $('.property-checkbox').length;
            const totalChecked = $('.property-checkbox:checked').length;
            
            $('#select-all-properties').prop('checked', totalCheckboxes === totalChecked);
        });
        
        // Excluir imóvel
        $('.delete-button').on('click', function() {
            const propertyId = $(this).data('id');
            
            if (!propertyId) return;
            
            if (confirm('Tem certeza que deseja excluir este imóvel?')) {
                deleteProperty(propertyId);
            }
        });
        
        // Pausar destaque do imóvel
        $('.pause-highlight-button').on('click', function() {
            const propertyId = $(this).data('id');
            
            if (!propertyId) return;
            
            if (confirm('Tem certeza que deseja pausar o destaque deste imóvel? Ele não aparecerá mais como destacado.')) {
                pauseHighlight(propertyId);
            }
        });
        
        // Excluir imóveis em massa
        $('#bulk-delete-btn').on('click', function() {
            const selectedIds = [];
            
            $('.property-checkbox:checked').each(function() {
                const propertyId = $(this).data('id');
                if (propertyId) {
                    selectedIds.push(propertyId);
                }
            });
            
            if (selectedIds.length === 0) {
                alert('Selecione pelo menos um imóvel para excluir.');
                return;
            }
            
            if (confirm('Tem certeza que deseja excluir ' + selectedIds.length + ' imóveis selecionados?')) {
                bulkDeleteProperties(selectedIds);
            }
        });
        
        // Função para excluir um imóvel
        function deleteProperty(propertyId) {
            $.ajax({
                url: site.ajax_url,
                type: 'POST',
                data: {
                    action: 'delete_immobile',
                    nonce: site.nonce,
                    property_id: propertyId
                },
                success: function(response) {
                    if (response.success) {
                        $('.property-item[data-property-id="' + propertyId + '"]').fadeOut(300, function() {
                            $(this).remove();
                            
                            // Verificar se não há mais imóveis
                            if ($('.property-item').length === 0) {
                                $('.property-list').html(
                                    '<div class="no-properties-message">' +
                                    '<p>Você ainda não tem imóveis cadastrados.</p>' +
                                    '<p><a href="/corretores/novo-imovel/" class="add-property-button">Adicionar seu primeiro imóvel</a></p>' +
                                    '</div>'
                                );
                            }
                        });
                    } else {
                        alert(response.data);
                    }
                },
                error: function() {
                    alert('Erro ao processar a solicitação. Tente novamente.');
                }
            });
        }
        
        // Função para pausar destaque do imóvel
        function pauseHighlight(propertyId) {
            $.ajax({
                url: site.ajax_url,
                type: 'POST',
                data: {
                    action: 'pause_immobile_highlight',
                    nonce: site.nonce,
                    property_id: propertyId
                },
                success: function(response) {
                    if (response.success) {
                        // Inicializar as variáveis
                        var propertyItem = $('.property-item[data-property-id="' + propertyId + '"]');
                        
                        // Remover tag de destaque
                        propertyItem.find('.sponsored-tag').remove();
                        
                        // Substituir botão de pausar por botão de destacar
                        var actionButtons = propertyItem.find('.property-actions');
                        actionButtons.find('.pause-highlight-button').remove();
                        
                        const highlightUrl = '/corretores/destacar-imovel/?immobile_id=' + propertyId;
                        const highlightButton = '<a href="' + highlightUrl + '" class="action-button highlight-button" title="Reativar Destaque"><i class="fas fa-star"></i></a>';
                        
                        actionButtons.find('.edit-button').after(highlightButton);
                        
                        alert('Destaque do imóvel pausado com sucesso!');
                    } else {
                        alert(response.data);
                    }
                },
                error: function() {
                    alert('Erro ao processar a solicitação. Tente novamente.');
                }
            });
        }
        
        // Função para excluir imóveis em massa
        function bulkDeleteProperties(propertyIds) {
            $.ajax({
                url: site.ajax_url,
                type: 'POST',
                data: {
                    action: 'bulk_delete_immobiles',
                    nonce: site.nonce,
                    property_ids: propertyIds
                },
                success: function(response) {
                    if (response.success) {
                        // Remover imóveis da lista
                        $.each(propertyIds, function(index, id) {
                            $('.property-item[data-property-id="' + id + '"]').fadeOut(300, function() {
                                $(this).remove();
                            });
                        });
                        
                        // Resetar checkboxes
                        $('#select-all-properties').prop('checked', false);
                        checkSelectedProperties();
                        
                        // Verificar se não há mais imóveis
                        setTimeout(function() {
                            if ($('.property-item').length === 0) {
                                $('.property-list').html(
                                    '<div class="no-properties-message">' +
                                    '<p>Você ainda não tem imóveis cadastrados.</p>' +
                                    '<p><a href="/corretores/novo-imovel/" class="add-property-button">Adicionar seu primeiro imóvel</a></p>' +
                                    '</div>'
                                );
                            }
                        }, 300);
                        
                        alert('Imóveis excluídos com sucesso!');
                    } else {
                        alert(response.data);
                    }
                },
                error: function() {
                    alert('Erro ao processar a solicitação. Tente novamente.');
                }
            });
        }
    });
})(jQuery);
EOT;
    
    // Salvar o arquivo JavaScript
    try {
        // Garantir que estamos usando o caminho correto do tema atual e não do hello-elementor
        $theme_dir = get_stylesheet_directory();
        $js_path = $theme_dir . '/inc/custom/broker/assets/js/broker-dashboard.js';
        
        // Verificar se o diretório existe ou criar
        $dir_path = dirname($js_path);
        if (!file_exists($dir_path)) {
            try {
                if (!@mkdir($dir_path, 0755, true)) {
                    error_log('Não foi possível criar o diretório para o arquivo JS: ' . $dir_path);
                    return;
                }
            } catch (Exception $e) {
                error_log('Erro ao criar diretório para o arquivo JS: ' . $e->getMessage());
                return;
            }
        }
        
        // Verificar se o diretório tem permissão de escrita
        if (is_writable($dir_path)) {
            file_put_contents($js_path, $js_content);
        } else {
            error_log('Diretório sem permissão de escrita: ' . $dir_path);
        }
    } catch (Exception $e) {
        error_log('Erro ao salvar arquivo JS: ' . $e->getMessage());
    }
}

// Atualizar JS ao ativar o tema
add_action('after_switch_theme', 'update_broker_dashboard_js');

// Função para verificar se é necessário atualizar o JS
function check_and_update_broker_dashboard_js() {
    $js_path = get_stylesheet_directory() . '/inc/custom/broker/assets/js/broker-dashboard.js';
    
    if (!file_exists($js_path)) {
        update_broker_dashboard_js();
    }
}
add_action('init', 'check_and_update_broker_dashboard_js');

// Verificar se a função já existe antes de declará-la
if (!function_exists('get_broker_metrics')) {
    function get_broker_metrics() {
        check_ajax_referer('ajax_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Usuário não autenticado');
        }

        $user_id = get_current_user_id();
        $last_30_days = array();
        
        for ($i = 0; $i < 30; $i++) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $views = get_user_meta($user_id, "metrics_views_{$date}", true) ?: 0;
            $clicks = get_user_meta($user_id, "metrics_clicks_{$date}", true) ?: 0;
            $conversions = get_user_meta($user_id, "metrics_conversions_{$date}", true) ?: 0;

            $last_30_days[] = array(
                'date' => $date,
                'views' => (int)$views,
                'clicks' => (int)$clicks,
                'conversions' => (int)$conversions
            );
        }

        wp_send_json_success(array('metrics' => array_reverse($last_30_days)));
    }
}
add_action('wp_ajax_get_broker_metrics', 'get_broker_metrics');

// Verificar se a função já existe antes de declará-la
if (!function_exists('get_broker_properties')) {
    function get_broker_properties() {
        // Garantir que nenhum conteúdo seja enviado antes do JSON
        ob_clean();
        
        check_ajax_referer('ajax_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Usuário não autenticado');
        }

        $user_id = get_current_user_id();
        
        $args = array(
            'post_type' => 'immobile',
            'posts_per_page' => -1,
            'post_status' => array('publish', 'pending', 'draft'),
            'meta_query' => array(
                array(
                    'key' => 'broker',
                    'value' => $user_id
                )
            )
        );

        $query = new WP_Query($args);
        $properties = array();

        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            
            $properties[] = array(
                'id' => $post_id,
                'title' => get_the_title(),
                'status' => get_post_status(),
                'views' => (int)get_post_meta($post_id, 'total_views', true) ?: 0,
                'clicks' => (int)get_post_meta($post_id, 'total_clicks', true) ?: 0,
                'conversions' => (int)get_post_meta($post_id, 'total_conversions', true) ?: 0,
                'sponsored' => get_post_meta($post_id, 'is_sponsored', true) === 'yes'
            );
        }

        wp_reset_postdata();
        
        // Garantir que a resposta seja um JSON válido
        header('Content-Type: application/json');
        echo json_encode(array('success' => true, 'data' => array('properties' => $properties)));
        exit;
    }
}
add_action('wp_ajax_get_broker_properties', 'get_broker_properties');