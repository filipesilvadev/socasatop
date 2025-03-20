<?php
/**
 * Página de administração para leads
 */

// Garantir que o arquivo seja acessado apenas pelo WordPress
if (!defined('ABSPATH')) {
    exit;
}

function leads_admin_page() {
    add_menu_page(
        'Leads de Imóveis',
        'Leads',
        'manage_options',
        'leads-management',
        'render_leads_admin_page',
        'dashicons-admin-users',
        25
    );
}
add_action('admin_menu', 'leads_admin_page');

function render_leads_admin_page() {
    // Verificar ação de importação
    if (isset($_POST['action']) && $_POST['action'] === 'import_leads' && isset($_FILES['csv_file'])) {
        handle_leads_import();
    }
    
    // Processar filtros
    $filters = [
        'date_start' => isset($_GET['date_start']) ? sanitize_text_field($_GET['date_start']) : '',
        'date_end' => isset($_GET['date_end']) ? sanitize_text_field($_GET['date_end']) : '',
        'immobile' => isset($_GET['immobile']) ? intval($_GET['immobile']) : 0,
        'broker' => isset($_GET['broker']) ? intval($_GET['broker']) : 0,
        'lead_name' => isset($_GET['lead_name']) ? sanitize_text_field($_GET['lead_name']) : '',
    ];

    // Obter imóveis para o filtro
    $immobiles = get_posts([
        'post_type' => 'immobile',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
    ]);
    
    // Obter corretores para o filtro
    $brokers = get_users([
        'role__in' => ['author', 'administrator'],
        'orderby' => 'display_name',
    ]);
    
    // Preparar argumentos para busca de leads
    $args = [
        'post_type' => 'lead',
        'posts_per_page' => 50,
        'orderby' => 'date',
        'order' => 'DESC',
    ];
    
    // Aplicar filtros na consulta
    if (!empty($filters['date_start']) || !empty($filters['date_end'])) {
        $args['date_query'] = [];
        
        if (!empty($filters['date_start'])) {
            $args['date_query']['after'] = $filters['date_start'];
        }
        
        if (!empty($filters['date_end'])) {
            $args['date_query']['before'] = $filters['date_end'];
        }
        
        $args['date_query']['inclusive'] = true;
    }
    
    if (!empty($filters['lead_name'])) {
        $args['s'] = $filters['lead_name'];
    }
    
    $meta_query = [];
    
    if (!empty($filters['immobile'])) {
        $meta_query[] = [
            'key' => 'immobile_id',
            'value' => $filters['immobile'],
            'compare' => '=',
        ];
    }
    
    if (!empty($filters['broker'])) {
        $meta_query[] = [
            'key' => 'broker_id',
            'value' => $filters['broker'],
            'compare' => '=',
        ];
    }
    
    if (!empty($meta_query)) {
        $args['meta_query'] = $meta_query;
    }
    
    $leads = get_posts($args);
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Gerenciamento de Leads</h1>
        
        <!-- Botões para adicionar lead e importar CSV -->
        <div class="wrap" style="margin-bottom: 15px;">
            <a href="<?php echo admin_url('post-new.php?post_type=lead'); ?>" class="page-title-action">Adicionar Novo Lead</a>
            <button type="button" id="import-leads-btn" class="page-title-action">Importar Leads (CSV)</button>
        </div>
        
        <!-- Formulário para importação de CSV (inicialmente oculto) -->
        <div id="import-leads-form" style="display:none; margin-bottom: 20px; padding: 15px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <h3>Importar Leads via CSV</h3>
            <p>Faça upload de um arquivo CSV com os dados dos leads para importação em lote.</p>
            <p>O arquivo deve conter os seguintes cabeçalhos: <code>name,email,phone,immobile_id,location,amount,details,property_type</code></p>
            
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="import_leads">
                <?php wp_nonce_field('import_leads_nonce', 'import_leads_nonce'); ?>
                
                <input type="file" name="csv_file" accept=".csv" required>
                <p><button type="submit" class="button button-primary">Importar Agora</button></p>
            </form>
            
            <p><a href="#" id="download-sample-csv">Baixar modelo CSV</a></p>
        </div>
        
        <div class="tablenav top">
            <form method="get">
                <input type="hidden" name="page" value="leads-management">
                
                <div class="alignleft actions">
                    <label for="date_start">De:</label>
                    <input type="date" id="date_start" name="date_start" value="<?php echo esc_attr($filters['date_start']); ?>">
                    
                    <label for="date_end">Até:</label>
                    <input type="date" id="date_end" name="date_end" value="<?php echo esc_attr($filters['date_end']); ?>">
                    
                    <label for="immobile">Imóvel:</label>
                    <select name="immobile" id="immobile">
                        <option value="">Todos</option>
                        <?php foreach ($immobiles as $immobile) : ?>
                            <option value="<?php echo $immobile->ID; ?>" <?php selected($filters['immobile'], $immobile->ID); ?>>
                                <?php echo $immobile->post_title; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <label for="broker">Corretor:</label>
                    <select name="broker" id="broker">
                        <option value="">Todos</option>
                        <?php foreach ($brokers as $broker) : ?>
                            <option value="<?php echo $broker->ID; ?>" <?php selected($filters['broker'], $broker->ID); ?>>
                                <?php echo $broker->display_name; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <label for="lead_name">Nome do Lead:</label>
                    <input type="text" id="lead_name" name="lead_name" value="<?php echo esc_attr($filters['lead_name']); ?>">
                    
                    <input type="submit" class="button action" value="Filtrar">
                </div>
            </form>
            <br class="clear">
        </div>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>WhatsApp</th>
                    <th>Imóvel</th>
                    <th>Localização</th>
                    <th>Preço/Oferta</th>
                    <th>Corretor</th>
                    <th>Data de Captura</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($leads)) : ?>
                    <tr>
                        <td colspan="8">Nenhum lead encontrado.</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($leads as $lead) : 
                        $immobile_id = get_post_meta($lead->ID, 'immobile_id', true);
                        $broker_id = get_post_meta($lead->ID, 'broker_id', true);
                        $immobile = get_post($immobile_id);
                        $broker = get_userdata($broker_id);
                        
                        // Obter localização
                        $location = get_post_meta($lead->ID, 'location', true);
                        if (empty($location) && $immobile_id) {
                            $immobile_terms = wp_get_post_terms($immobile_id, 'locations');
                            if (!empty($immobile_terms) && !is_wp_error($immobile_terms)) {
                                $location = $immobile_terms[0]->name;
                            }
                        }
                        
                        // Obter preço/oferta
                        $amount = get_post_meta($lead->ID, 'amount', true);
                        if (empty($amount) && $immobile_id) {
                            $amount = get_post_meta($immobile_id, 'price', true);
                        }
                    ?>
                        <tr>
                            <td>
                                <a href="<?php echo admin_url('post.php?post=' . $lead->ID . '&action=edit'); ?>">
                                    <?php echo $lead->post_title; ?>
                                </a>
                            </td>
                            <td><?php echo get_post_meta($lead->ID, 'email', true); ?></td>
                            <td><?php echo get_post_meta($lead->ID, 'whatsapp', true) ?: get_post_meta($lead->ID, 'phone', true); ?></td>
                            <td>
                                <?php if ($immobile) : ?>
                                    <a href="<?php echo get_permalink($immobile_id); ?>" target="_blank">
                                        <?php echo $immobile->post_title; ?>
                                    </a>
                                <?php else : ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($location ?: 'N/A'); ?></td>
                            <td>
                                <?php 
                                if (!empty($amount)) {
                                    echo 'R$ ' . number_format($amount, 2, ',', '.');
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </td>
                            <td>
                                <?php echo $broker ? $broker->display_name : 'N/A'; ?>
                            </td>
                            <td>
                                <?php echo get_the_date('d/m/Y H:i:s', $lead->ID); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Script para controle da importação CSV -->
    <script>
    jQuery(document).ready(function($) {
        // Mostrar/esconder formulário de importação
        $('#import-leads-btn').on('click', function() {
            $('#import-leads-form').slideToggle();
        });
        
        // Gerar e baixar modelo CSV
        $('#download-sample-csv').on('click', function(e) {
            e.preventDefault();
            
            // Cabeçalhos e exemplo
            var csvContent = "name,email,phone,immobile_id,location,amount,details,property_type\n";
            csvContent += "João Silva,joao@exemplo.com,11987654321,,Vila Leopoldina,500000,Quero comprar casa térrea,Térreo";
            
            // Criar elemento para download
            var encodedUri = encodeURI("data:text/csv;charset=utf-8," + csvContent);
            var link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "modelo_importacao_leads.csv");
            document.body.appendChild(link);
            
            // Trigger download
            link.click();
            document.body.removeChild(link);
        });
    });
    </script>
    <?php
}

/**
 * Função para processar a importação de leads via CSV
 */
function handle_leads_import() {
    // Verificar nonce
    if (!isset($_POST['import_leads_nonce']) || !wp_verify_nonce($_POST['import_leads_nonce'], 'import_leads_nonce')) {
        wp_die('Verificação de segurança falhou. Por favor, tente novamente.');
    }
    
    // Verificar permissões
    if (!current_user_can('manage_options')) {
        wp_die('Você não tem permissão para realizar esta ação.');
    }
    
    // Verificar arquivo
    if (!isset($_FILES['csv_file'])) {
        wp_die('Nenhum arquivo enviado.');
    }
    
    $file = $_FILES['csv_file'];
    
    // Verificar erro de upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        wp_die('Erro no upload do arquivo: ' . $file['error']);
    }
    
    // Verificar tipo de arquivo
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($file_ext !== 'csv') {
        wp_die('Por favor, envie um arquivo CSV válido.');
    }
    
    // Abrir arquivo para leitura
    $handle = fopen($file['tmp_name'], 'r');
    if (!$handle) {
        wp_die('Não foi possível abrir o arquivo para leitura.');
    }
    
    // Ler cabeçalhos
    $headers = fgetcsv($handle, 1000, ',');
    if (!$headers) {
        fclose($handle);
        wp_die('O arquivo parece estar vazio ou mal formatado.');
    }
    
    // Validar cabeçalhos mínimos necessários
    $required_headers = ['name', 'email'];
    $missing_headers = array_diff($required_headers, $headers);
    
    if (count($missing_headers) > 0) {
        fclose($handle);
        wp_die('Cabeçalhos obrigatórios ausentes: ' . implode(', ', $missing_headers));
    }
    
    // Processar linhas
    $imported = 0;
    $errors = [];
    
    while (($data = fgetcsv($handle, 1000, ',')) !== false) {
        // Pular linhas vazias
        if (count($data) <= 1 && empty($data[0])) {
            continue;
        }
        
        // Mapear dados para array associativo
        $lead_data = [];
        foreach ($headers as $index => $header) {
            if (isset($data[$index])) {
                $lead_data[$header] = $data[$index];
            } else {
                $lead_data[$header] = '';
            }
        }
        
        // Verificar dados mínimos
        if (empty($lead_data['name'])) {
            $errors[] = 'Linha ignorada: Nome do lead ausente';
            continue;
        }
        
        // Criar lead
        $lead_id = wp_insert_post([
            'post_title' => sanitize_text_field($lead_data['name']),
            'post_type' => 'lead',
            'post_status' => 'publish',
        ]);
        
        if (is_wp_error($lead_id)) {
            $errors[] = 'Erro ao criar lead para "' . $lead_data['name'] . '": ' . $lead_id->get_error_message();
            continue;
        }
        
        // Salvar metadados
        $meta_fields = [
            'email' => 'email',
            'phone' => 'phone',
            'whatsapp' => 'phone', // Usar mesmo campo para compatibilidade
            'immobile_id' => 'immobile_id',
            'location' => 'location',
            'amount' => 'amount',
            'details' => 'details',
            'property_type' => 'property_type',
            'status' => 'status',
            'financing' => 'financing',
            'condominium' => 'condominium',
            'bedrooms' => 'bedrooms',
            'facade' => 'facade',
        ];
        
        foreach ($meta_fields as $csv_field => $meta_key) {
            if (isset($lead_data[$csv_field]) && $lead_data[$csv_field] !== '') {
                if ($csv_field === 'immobile_id' && !empty($lead_data[$csv_field])) {
                    // Verificar se o imóvel existe
                    $immobile = get_post($lead_data[$csv_field]);
                    if ($immobile && $immobile->post_type === 'immobile') {
                        update_post_meta($lead_id, $meta_key, intval($lead_data[$csv_field]));
                    }
                } else {
                    update_post_meta($lead_id, $meta_key, sanitize_text_field($lead_data[$csv_field]));
                }
            }
        }
        
        $imported++;
    }
    
    fclose($handle);
    
    // Exibir mensagem de resultado
    $message = sprintf('Importação concluída. %d leads importados com sucesso.', $imported);
    if (!empty($errors)) {
        $message .= ' Erros encontrados: ' . count($errors);
    }
    
    add_settings_error('lead_import', 'lead_import', $message, $imported > 0 ? 'updated' : 'error');
    
    // Se houver erros, logar para administradores
    if (!empty($errors)) {
        error_log('Erros na importação de leads: ' . print_r($errors, true));
    }
    
    set_transient('lead_import_results', [
        'imported' => $imported,
        'errors' => $errors
    ], 60 * 5); // 5 minutos
}

// Para exibir notificações
add_action('admin_notices', function() {
    settings_errors('lead_import');
}); 