<?php
/**
 * Colunas personalizadas e filtros para a listagem de leads
 */

// Garantir que o arquivo seja acessado apenas pelo WordPress
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Adicionar colunas personalizadas na listagem de leads
 */
function add_lead_columns($columns) {
    $new_columns = array();
    
    // Inserir colunas padrão que queremos manter
    if (isset($columns['cb'])) {
        $new_columns['cb'] = $columns['cb'];
    }
    
    if (isset($columns['title'])) {
        $new_columns['title'] = $columns['title'];
    }
    
    // Adicionar nossas colunas personalizadas
    $new_columns['email'] = 'E-mail';
    $new_columns['phone'] = 'Telefone';
    $new_columns['immobile'] = 'Imóvel';
    $new_columns['location'] = 'Localização';
    $new_columns['price'] = 'Preço/Oferta';
    $new_columns['date'] = 'Data';
    
    return $new_columns;
}
add_filter('manage_lead_posts_columns', 'add_lead_columns');

/**
 * Preencher o conteúdo das colunas personalizadas
 */
function manage_lead_columns_content($column, $post_id) {
    switch ($column) {
        case 'email':
            echo esc_html(get_post_meta($post_id, 'email', true));
            break;
            
        case 'phone':
            $phone = get_post_meta($post_id, 'whatsapp', true) ?: get_post_meta($post_id, 'phone', true);
            echo esc_html($phone);
            break;
            
        case 'immobile':
            $immobile_id = get_post_meta($post_id, 'immobile_id', true);
            if ($immobile_id) {
                $immobile = get_post($immobile_id);
                if ($immobile) {
                    echo '<a href="' . get_edit_post_link($immobile_id) . '">' . esc_html($immobile->post_title) . '</a>';
                }
            } else {
                echo '—';
            }
            break;
            
        case 'location':
            // Obter localização do lead
            $location = get_post_meta($post_id, 'location', true);
            
            // Se não tiver localização direta, tentar obter do imóvel
            if (empty($location)) {
                $immobile_id = get_post_meta($post_id, 'immobile_id', true);
                if ($immobile_id) {
                    $terms = wp_get_post_terms($immobile_id, 'locations');
                    if (!empty($terms) && !is_wp_error($terms)) {
                        $location = $terms[0]->name;
                    }
                }
            }
            
            echo $location ? esc_html($location) : '—';
            break;
            
        case 'price':
            // Obter preço/oferta do lead
            $amount = get_post_meta($post_id, 'amount', true);
            
            // Se não tiver preço diretamente, tentar obter do imóvel
            if (empty($amount)) {
                $immobile_id = get_post_meta($post_id, 'immobile_id', true);
                if ($immobile_id) {
                    $amount = get_post_meta($immobile_id, 'price', true);
                }
            }
            
            if (!empty($amount)) {
                echo 'R$ ' . number_format($amount, 2, ',', '.');
            } else {
                echo '—';
            }
            break;
    }
}
add_action('manage_lead_posts_custom_column', 'manage_lead_columns_content', 10, 2);

/**
 * Tornar as colunas ordenáveis
 */
function make_lead_columns_sortable($columns) {
    $columns['email'] = 'email';
    $columns['location'] = 'location';
    $columns['price'] = 'price';
    $columns['immobile'] = 'immobile';
    return $columns;
}
add_filter('manage_edit-lead_sortable_columns', 'make_lead_columns_sortable');

/**
 * Implementar ordenação personalizada
 */
function lead_columns_orderby($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }
    
    $orderby = $query->get('orderby');
    
    if ('email' === $orderby) {
        $query->set('meta_key', 'email');
        $query->set('orderby', 'meta_value');
    }
    
    if ('location' === $orderby) {
        $query->set('meta_key', 'location');
        $query->set('orderby', 'meta_value');
    }
    
    if ('price' === $orderby) {
        $query->set('meta_key', 'amount');
        $query->set('orderby', 'meta_value_num');
    }
    
    if ('immobile' === $orderby) {
        $query->set('meta_key', 'immobile_id');
        $query->set('orderby', 'meta_value_num');
    }
}
add_action('pre_get_posts', 'lead_columns_orderby');

/**
 * Adicionar filtros personalizados no topo da listagem
 */
function add_lead_filters() {
    global $typenow;
    
    // Apenas na tela de leads
    if ($typenow !== 'lead') {
        return;
    }
    
    // Filtro por localização
    $locations = get_terms([
        'taxonomy' => 'locations',
        'hide_empty' => false,
    ]);
    
    $current_location = isset($_GET['lead_location']) ? sanitize_text_field($_GET['lead_location']) : '';
    
    if (!empty($locations) && !is_wp_error($locations)) {
        echo '<select name="lead_location">';
        echo '<option value="">Todas as Localizações</option>';
        
        foreach ($locations as $location) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($location->name),
                selected($current_location, $location->name, false),
                esc_html($location->name)
            );
        }
        
        echo '</select>';
    }
    
    // Filtro por imóvel
    $immobiles = get_posts([
        'post_type' => 'immobile',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
    ]);
    
    $current_immobile = isset($_GET['lead_immobile']) ? intval($_GET['lead_immobile']) : 0;
    
    if (!empty($immobiles)) {
        echo '<select name="lead_immobile">';
        echo '<option value="">Todos os Imóveis</option>';
        
        foreach ($immobiles as $immobile) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($immobile->ID),
                selected($current_immobile, $immobile->ID, false),
                esc_html($immobile->post_title)
            );
        }
        
        echo '</select>';
    }
    
    // Filtro por faixa de preço
    $price_ranges = [
        '' => 'Qualquer Preço',
        '0-300000' => 'Até R$ 300.000',
        '300001-500000' => 'R$ 300.001 a R$ 500.000',
        '500001-800000' => 'R$ 500.001 a R$ 800.000',
        '800001-1000000' => 'R$ 800.001 a R$ 1.000.000',
        '1000001-9999999999' => 'Acima de R$ 1.000.000'
    ];
    
    $current_price_range = isset($_GET['lead_price_range']) ? sanitize_text_field($_GET['lead_price_range']) : '';
    
    echo '<select name="lead_price_range">';
    
    foreach ($price_ranges as $range => $label) {
        printf(
            '<option value="%s" %s>%s</option>',
            esc_attr($range),
            selected($current_price_range, $range, false),
            esc_html($label)
        );
    }
    
    echo '</select>';
}
add_action('restrict_manage_posts', 'add_lead_filters');

/**
 * Modificar a consulta para aplicar os filtros personalizados
 */
function apply_lead_filters($query) {
    global $pagenow, $typenow;
    
    // Verificar se estamos na página correta
    if (!is_admin() || $pagenow !== 'edit.php' || $typenow !== 'lead' || !$query->is_main_query()) {
        return;
    }
    
    // Aplicar filtro de localização
    if (!empty($_GET['lead_location'])) {
        $location = sanitize_text_field($_GET['lead_location']);
        
        // Precisamos verificar em dois lugares: diretamente no lead ou no imóvel relacionado
        $meta_query = $query->get('meta_query');
        if (!is_array($meta_query)) {
            $meta_query = [];
        }
        
        // Para simplificar, vamos apenas verificar no próprio lead
        $meta_query[] = [
            'key' => 'location',
            'value' => $location,
            'compare' => '=',
        ];
        
        $query->set('meta_query', $meta_query);
    }
    
    // Aplicar filtro de imóvel
    if (!empty($_GET['lead_immobile'])) {
        $immobile_id = intval($_GET['lead_immobile']);
        
        $meta_query = $query->get('meta_query');
        if (!is_array($meta_query)) {
            $meta_query = [];
        }
        
        $meta_query[] = [
            'key' => 'immobile_id',
            'value' => $immobile_id,
            'compare' => '=',
        ];
        
        $query->set('meta_query', $meta_query);
    }
    
    // Aplicar filtro de faixa de preço
    if (!empty($_GET['lead_price_range'])) {
        $price_range = sanitize_text_field($_GET['lead_price_range']);
        
        if (preg_match('/^(\d+)-(\d+)$/', $price_range, $matches)) {
            $min_price = intval($matches[1]);
            $max_price = intval($matches[2]);
            
            $meta_query = $query->get('meta_query');
            if (!is_array($meta_query)) {
                $meta_query = [];
            }
            
            $meta_query[] = [
                'key' => 'amount',
                'value' => [$min_price, $max_price],
                'type' => 'NUMERIC',
                'compare' => 'BETWEEN',
            ];
            
            $query->set('meta_query', $meta_query);
        }
    }
}
add_action('pre_get_posts', 'apply_lead_filters');

/**
 * Adicionar botão para importar leads no topo da listagem
 */
function add_lead_import_button() {
    global $typenow;
    
    if ($typenow !== 'lead') {
        return;
    }
    
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Adicionar botão de importação após o botão de "Adicionar Novo"
        $('.wrap .page-title-action').after('<button type="button" id="import-leads-btn" class="page-title-action">Importar Leads (CSV)</button>');
        
        // Criar e adicionar o formulário de importação (inicialmente oculto)
        var importForm = $('<div id="import-leads-form" style="display:none; margin: 20px 0; padding: 15px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">' +
            '<h3>Importar Leads via CSV</h3>' +
            '<p>Faça upload de um arquivo CSV com os dados dos leads para importação em lote.</p>' +
            '<p>O arquivo deve conter os seguintes cabeçalhos: <code>name,email,phone,immobile_id,location,amount,details,property_type</code></p>' +
            '<form method="post" enctype="multipart/form-data" action="<?php echo admin_url('admin-post.php'); ?>">' +
            '<input type="hidden" name="action" value="import_leads_csv">' +
            '<?php echo wp_nonce_field('import_leads_nonce', 'import_leads_nonce', true, false); ?>' +
            '<input type="file" name="csv_file" accept=".csv" required>' +
            '<p><button type="submit" class="button button-primary">Importar Agora</button></p>' +
            '</form>' +
            '<p><a href="#" id="download-sample-csv">Baixar modelo CSV</a></p>' +
            '</div>');
        
        $('.wrap h1.wp-heading-inline').after(importForm);
        
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
add_action('admin_footer', 'add_lead_import_button');

/**
 * Processar a importação de leads via CSV
 */
function handle_lead_csv_import() {
    // Verificar nonce
    if (!isset($_POST['import_leads_nonce']) || !wp_verify_nonce($_POST['import_leads_nonce'], 'import_leads_nonce')) {
        wp_die('Verificação de segurança falhou. Por favor, tente novamente.', 'Erro de Segurança', [
            'response' => 403,
            'back_link' => true,
        ]);
    }
    
    // Verificar permissões
    if (!current_user_can('edit_posts')) {
        wp_die('Você não tem permissão para realizar esta ação.', 'Permissão Negada', [
            'response' => 403,
            'back_link' => true,
        ]);
    }
    
    // Verificar arquivo
    if (!isset($_FILES['csv_file'])) {
        wp_die('Nenhum arquivo enviado.', 'Erro de Upload', ['back_link' => true]);
    }
    
    $file = $_FILES['csv_file'];
    
    // Verificar erro de upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        wp_die('Erro no upload do arquivo: ' . $file['error'], 'Erro de Upload', ['back_link' => true]);
    }
    
    // Verificar tipo de arquivo
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($file_ext !== 'csv') {
        wp_die('Por favor, envie um arquivo CSV válido.', 'Tipo de Arquivo Inválido', ['back_link' => true]);
    }
    
    // Abrir arquivo para leitura
    $handle = fopen($file['tmp_name'], 'r');
    if (!$handle) {
        wp_die('Não foi possível abrir o arquivo para leitura.', 'Erro de Leitura', ['back_link' => true]);
    }
    
    // Ler cabeçalhos
    $headers = fgetcsv($handle, 1000, ',');
    if (!$headers) {
        fclose($handle);
        wp_die('O arquivo parece estar vazio ou mal formatado.', 'Arquivo Inválido', ['back_link' => true]);
    }
    
    // Validar cabeçalhos mínimos necessários
    $required_headers = ['name', 'email'];
    $missing_headers = array_diff($required_headers, $headers);
    
    if (count($missing_headers) > 0) {
        fclose($handle);
        wp_die('Cabeçalhos obrigatórios ausentes: ' . implode(', ', $missing_headers), 'Formato Inválido', ['back_link' => true]);
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
    
    // Redirecionar com mensagem apropriada
    $redirect_url = admin_url('edit.php?post_type=lead');
    
    if ($imported > 0) {
        $message = sprintf('Importação concluída. %d leads importados com sucesso.', $imported);
        if (!empty($errors)) {
            $message .= ' Alguns erros foram encontrados.';
        }
        
        $redirect_url = add_query_arg('import_success', $imported, $redirect_url);
        
        if (!empty($errors)) {
            // Salvar erros em uma opção temporária
            set_transient('lead_import_errors', $errors, 60 * 5); // 5 minutos
            $redirect_url = add_query_arg('import_errors', count($errors), $redirect_url);
        }
    } else {
        $redirect_url = add_query_arg('import_failed', '1', $redirect_url);
    }
    
    wp_redirect($redirect_url);
    exit;
}
add_action('admin_post_import_leads_csv', 'handle_lead_csv_import');

/**
 * Exibir notificações após importação
 */
function display_lead_import_notices() {
    global $pagenow, $typenow;
    
    if ($pagenow !== 'edit.php' || $typenow !== 'lead') {
        return;
    }
    
    if (isset($_GET['import_success'])) {
        $count = intval($_GET['import_success']);
        $message = sprintf('Sucesso! %d leads foram importados.', $count);
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
    }
    
    if (isset($_GET['import_errors'])) {
        $errors = get_transient('lead_import_errors');
        if ($errors) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p>Importação concluída com avisos. Alguns registros não puderam ser importados:</p>';
            echo '<ul>';
            
            foreach ($errors as $error) {
                echo '<li>' . esc_html($error) . '</li>';
            }
            
            echo '</ul>';
            echo '</div>';
        }
    }
    
    if (isset($_GET['import_failed'])) {
        echo '<div class="notice notice-error is-dismissible"><p>A importação falhou. Nenhum lead foi importado.</p></div>';
    }
}
add_action('admin_notices', 'display_lead_import_notices'); 