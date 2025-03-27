<?php
/**
 * Registro do Post Type Avisos e funcionalidades relacionadas
 * Este CPT permite enviar notificações via WhatsApp para usuários com função 'author'
 */

// Garantir que o arquivo seja acessado apenas pelo WordPress
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registra o Custom Post Type Avisos
 */
function register_avisos_post_type() {
    $labels = array(
        'name'                  => 'Avisos',
        'singular_name'         => 'Aviso',
        'menu_name'             => 'Avisos',
        'name_admin_bar'        => 'Aviso',
        'archives'              => 'Arquivo de Avisos',
        'attributes'            => 'Atributos do Aviso',
        'parent_item_colon'     => 'Aviso Pai:',
        'all_items'             => 'Todos os Avisos',
        'add_new_item'          => 'Adicionar Novo Aviso',
        'add_new'               => 'Adicionar Novo',
        'new_item'              => 'Novo Aviso',
        'edit_item'             => 'Editar Aviso',
        'update_item'           => 'Atualizar Aviso',
        'view_item'             => 'Ver Aviso',
        'view_items'            => 'Ver Avisos',
        'search_items'          => 'Buscar Aviso',
        'not_found'             => 'Não encontrado',
        'not_found_in_trash'    => 'Não encontrado na Lixeira',
        'featured_image'        => 'Imagem Destacada',
        'set_featured_image'    => 'Definir imagem destacada',
        'remove_featured_image' => 'Remover imagem destacada',
        'use_featured_image'    => 'Usar como imagem destacada',
        'insert_into_item'      => 'Inserir no Aviso',
        'uploaded_to_this_item' => 'Enviado para este Aviso',
        'items_list'            => 'Lista de Avisos',
        'items_list_navigation' => 'Navegação da lista de Avisos',
        'filter_items_list'     => 'Filtrar lista de Avisos',
    );
    
    $args = array(
        'label'                 => 'Aviso',
        'description'           => 'Avisos para envio via WhatsApp',
        'labels'                => $labels,
        'supports'              => array('title', 'editor'),
        'hierarchical'          => false,
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 20,
        'menu_icon'             => 'dashicons-megaphone',
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => false,
        'can_export'            => true,
        'has_archive'           => false,
        'exclude_from_search'   => true,
        'publicly_queryable'    => false,
        'capability_type'       => 'post',
    );
    
    register_post_type('avisos', $args);
}
add_action('init', 'register_avisos_post_type');

/**
 * Registra opções para a funcionalidade de avisos
 */
function register_avisos_settings() {
    // Registrar campo de configuração para a URL do webhook
    register_setting('general', 'avisos_webhook_url', 'sanitize_url');
    
    // Adicionar campo na página de configurações
    add_settings_section(
        'avisos_settings_section',
        'Configurações de Avisos WhatsApp',
        'avisos_settings_section_callback',
        'general'
    );
    
    add_settings_field(
        'avisos_webhook_url',
        'URL do Webhook (n8n)',
        'avisos_webhook_url_callback',
        'general',
        'avisos_settings_section'
    );
}
add_action('admin_init', 'register_avisos_settings');

/**
 * Callback para a seção de configurações
 */
function avisos_settings_section_callback() {
    echo '<p>Configure a URL do webhook para onde serão enviados os dados dos corretores quando um novo aviso for publicado.</p>';
}

/**
 * Callback para o campo de URL do webhook
 */
function avisos_webhook_url_callback() {
    $webhook_url = get_option('avisos_webhook_url');
    echo '<input type="url" id="avisos_webhook_url" name="avisos_webhook_url" value="' . esc_attr($webhook_url) . '" class="regular-text" placeholder="https://seu-servidor-n8n.com/webhook/...">';
    echo '<p class="description">URL do webhook do n8n que receberá os dados dos corretores.</p>';
}

/**
 * Adiciona meta box para opções de envio do aviso
 */
function avisos_opcoes_meta_box() {
    add_meta_box(
        'avisos_opcoes_meta_box',
        'Opções de Envio do Aviso',
        'avisos_opcoes_meta_box_callback',
        'avisos',
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'avisos_opcoes_meta_box');

/**
 * Callback para exibir o conteúdo do meta box
 */
function avisos_opcoes_meta_box_callback($post) {
    wp_nonce_field('avisos_opcoes_meta_box', 'avisos_opcoes_meta_box_nonce');
    
    $enviar_whatsapp = get_post_meta($post->ID, '_enviar_whatsapp', true);
    $status_envio = get_post_meta($post->ID, '_status_envio', true);
    $data_envio = get_post_meta($post->ID, '_data_envio', true);
    $webhook_url = get_option('avisos_webhook_url');
    
    ?>
    <p>
        <label for="enviar_whatsapp">
            <input type="checkbox" id="enviar_whatsapp" name="enviar_whatsapp" value="1" <?php checked($enviar_whatsapp, '1'); ?> />
            Enviar notificação via WhatsApp
        </label>
    </p>
    
    <?php if (empty($webhook_url)): ?>
        <div class="notice notice-warning inline">
            <p>
                <strong>Atenção:</strong> A URL do webhook não está configurada. 
                <a href="<?php echo admin_url('options-general.php'); ?>#avisos_webhook_url">Configure aqui</a>
            </p>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($status_envio)) : ?>
        <p><strong>Status do envio:</strong> <?php echo esc_html($status_envio); ?></p>
    <?php endif; ?>
    
    <?php if (!empty($data_envio)) : ?>
        <p><strong>Data do envio:</strong> <?php echo esc_html(date_i18n('d/m/Y H:i:s', strtotime($data_envio))); ?></p>
    <?php endif; ?>
    
    <?php
}

/**
 * Salva os dados do meta box quando o post é salvo
 */
function save_avisos_meta_box_data($post_id) {
    // Verificar se o nonce é válido
    if (!isset($_POST['avisos_opcoes_meta_box_nonce']) || !wp_verify_nonce($_POST['avisos_opcoes_meta_box_nonce'], 'avisos_opcoes_meta_box')) {
        return;
    }
    
    // Verificar se é um salvamento automático
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Verificar permissões
    if (isset($_POST['post_type']) && 'avisos' == $_POST['post_type']) {
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }
    
    // Salvar checkbox
    $enviar_whatsapp = isset($_POST['enviar_whatsapp']) ? '1' : '0';
    update_post_meta($post_id, '_enviar_whatsapp', $enviar_whatsapp);
    
    // Se estiver sendo publicado e tiver marcado para enviar WhatsApp
    if (isset($_POST['post_status']) && $_POST['post_status'] === 'publish' && $enviar_whatsapp === '1') {
        // Apenas enviar se ainda não foi enviado ou se o status é falha
        $status_atual = get_post_meta($post_id, '_status_envio', true);
        if (empty($status_atual) || $status_atual === 'Falha no envio') {
            // Agendar o envio para ser processado após o salvamento completo do post
            wp_schedule_single_event(time() + 10, 'processar_envio_aviso_whatsapp', array($post_id));
            update_post_meta($post_id, '_status_envio', 'Agendado para envio');
        }
    }
}
add_action('save_post', 'save_avisos_meta_box_data');

/**
 * Hook para processar o envio de mensagens WhatsApp
 */
add_action('processar_envio_aviso_whatsapp', 'enviar_aviso_via_webhook');

/**
 * Função que envia os dados do aviso para o webhook do n8n
 */
function enviar_aviso_via_webhook($post_id) {
    $post = get_post($post_id);
    
    if (!$post || $post->post_type !== 'avisos') {
        return;
    }
    
    // Verificar se deve enviar por WhatsApp
    $enviar_whatsapp = get_post_meta($post_id, '_enviar_whatsapp', true);
    if ($enviar_whatsapp !== '1') {
        return;
    }
    
    // Obter URL do webhook
    $webhook_url = get_option('avisos_webhook_url');
    if (empty($webhook_url)) {
        update_post_meta($post_id, '_status_envio', 'Falha no envio - URL do webhook não configurada');
        return;
    }
    
    // Obter todos os usuários com função 'author'
    $autores = get_users(array('role' => 'author'));
    $contatos = array();
    
    // Preparar conteúdo da mensagem
    $titulo = $post->post_title;
    $conteudo = wp_strip_all_tags($post->post_content);
    
    // Preparar lista de corretores com seus números
    foreach ($autores as $autor) {
        $whatsapp = get_user_meta($autor->ID, 'whatsapp', true);
        
        // Se não tiver WhatsApp, tenta obter o telefone regular
        if (empty($whatsapp)) {
            $whatsapp = get_user_meta($autor->ID, 'phone', true);
        }
        
        // Apenas adiciona se tiver algum número
        if (!empty($whatsapp)) {
            $whatsapp_formatado = preg_replace('/[^0-9]/', '', $whatsapp);
            
            $contatos[] = array(
                'id' => $autor->ID,
                'nome' => $autor->display_name,
                'email' => $autor->user_email,
                'telefone' => $whatsapp,
                'telefone_formatado' => $whatsapp_formatado
            );
        }
    }
    
    // Preparar dados para envio ao webhook
    $dados = array(
        'aviso' => array(
            'id' => $post_id,
            'titulo' => $titulo,
            'conteudo' => $conteudo,
            'data_publicacao' => $post->post_date
        ),
        'corretores' => $contatos,
        'total_corretores' => count($contatos)
    );
    
    // Configuração do cURL para enviar ao webhook
    $ch = curl_init($webhook_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dados));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'X-API-Key: 41E55ED6FC45-4D16-A484-60E814343BDF'
    ));
    
    // Executar o pedido
    $resposta = curl_exec($ch);
    $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Verificar erros
    $erro = '';
    if (curl_errno($ch)) {
        $erro = curl_error($ch);
    }
    
    curl_close($ch);
    
    // Verificar resultado
    if ($status_code >= 200 && $status_code < 300) {
        update_post_meta($post_id, '_status_envio', 'Dados enviados com sucesso para o webhook');
        update_post_meta($post_id, '_data_envio', current_time('mysql'));
        update_post_meta($post_id, '_total_corretores', count($contatos));
    } else {
        update_post_meta($post_id, '_status_envio', 'Falha no envio para o webhook - Código: ' . $status_code);
        update_post_meta($post_id, '_erro_webhook', $erro ? $erro : 'Erro desconhecido');
    }
}

/**
 * Adiciona uma coluna de status do envio na lista de avisos
 */
function add_avisos_columns($columns) {
    $new_columns = array();
    
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        
        // Adicionar coluna após o título
        if ($key === 'title') {
            $new_columns['status_envio'] = 'Status do Envio';
            $new_columns['data_envio'] = 'Data do Envio';
            $new_columns['total_corretores'] = 'Total de Corretores';
        }
    }
    
    return $new_columns;
}
add_filter('manage_avisos_posts_columns', 'add_avisos_columns');

/**
 * Preenche o conteúdo das colunas personalizadas
 */
function fill_avisos_columns($column, $post_id) {
    switch ($column) {
        case 'status_envio':
            $status_envio = get_post_meta($post_id, '_status_envio', true);
            echo !empty($status_envio) ? esc_html($status_envio) : 'Não enviado';
            break;
        
        case 'data_envio':
            $data_envio = get_post_meta($post_id, '_data_envio', true);
            echo !empty($data_envio) ? esc_html(date_i18n('d/m/Y H:i:s', strtotime($data_envio))) : '-';
            break;
            
        case 'total_corretores':
            $total_corretores = get_post_meta($post_id, '_total_corretores', true);
            echo !empty($total_corretores) ? esc_html($total_corretores) : '-';
            break;
    }
}
add_action('manage_avisos_posts_custom_column', 'fill_avisos_columns', 10, 2);

/**
 * Adiciona botão para reenviar dados ao webhook na tela de edição
 */
function avisos_add_reenviar_button() {
    global $post;
    
    if ($post->post_type === 'avisos' && $post->post_status === 'publish') {
        ?>
        <div id="reenviar-container" style="margin: 10px 0;">
            <button type="button" id="reenviar-webhook" class="button button-secondary">
                <span class="dashicons dashicons-share-alt2" style="vertical-align: middle;"></span> 
                Reenviar Dados para Webhook
            </button>
            <span id="reenviar-status" style="margin-left: 10px;"></span>
        </div>
        
        <script>
            jQuery(document).ready(function($) {
                $('#reenviar-webhook').on('click', function() {
                    if (confirm('Tem certeza que deseja reenviar os dados para o webhook?')) {
                        const button = $(this);
                        const statusSpan = $('#reenviar-status');
                        
                        button.prop('disabled', true);
                        statusSpan.text('Processando...');
                        
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'reenviar_aviso_webhook',
                                post_id: <?php echo $post->ID; ?>,
                                nonce: '<?php echo wp_create_nonce('reenviar_aviso_nonce'); ?>'
                            },
                            success: function(response) {
                                if (response.success) {
                                    statusSpan.text('Dados reenviados com sucesso!');
                                    setTimeout(function() {
                                        location.reload();
                                    }, 2000);
                                } else {
                                    statusSpan.text('Erro: ' + response.data);
                                    button.prop('disabled', false);
                                }
                            },
                            error: function() {
                                statusSpan.text('Erro na solicitação.');
                                button.prop('disabled', false);
                            }
                        });
                    }
                });
            });
        </script>
        <?php
    }
}
add_action('edit_form_after_title', 'avisos_add_reenviar_button');

/**
 * Handler AJAX para reenviar dados ao webhook
 */
function reenviar_aviso_webhook_ajax() {
    // Verificar nonce e permissões
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'reenviar_aviso_nonce')) {
        wp_send_json_error('Erro de segurança');
    }
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Permissão negada');
    }
    
    $post_id = intval($_POST['post_id']);
    $post = get_post($post_id);
    
    if (!$post || $post->post_type !== 'avisos') {
        wp_send_json_error('Aviso não encontrado');
    }
    
    // Verificar se o webhook está configurado
    $webhook_url = get_option('avisos_webhook_url');
    if (empty($webhook_url)) {
        wp_send_json_error('URL do webhook não configurada. Configure nas Configurações Gerais do WordPress.');
        return;
    }
    
    // Preparar para reenvio
    update_post_meta($post_id, '_status_envio', 'Agendado para reenvio');
    
    // Agendar novamente o envio
    wp_schedule_single_event(time() + 5, 'processar_envio_aviso_whatsapp', array($post_id));
    
    wp_send_json_success();
}
add_action('wp_ajax_reenviar_aviso_webhook', 'reenviar_aviso_webhook_ajax'); 