<?php
/**
 * Arquivo responsável pela funcionalidade de aprovação de imóveis
 *
 * @package SoCasaTop
 */

// Impedir acesso direto ao arquivo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Função para configurar a aprovação do imóvel apenas quando for salvo como pendente
 */
function set_immobile_pending_on_save($post_id, $post, $update) {
    // Verificar se é o tipo de post 'immobile'
    if ($post->post_type !== 'immobile') {
        return;
    }

    // Ignorar salvamentos automáticos
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Verificar se é o primeiro salvamento (não é uma atualização)
    if (!$update) {
        // Verificar se o usuário é um corretor (não é administrador)
        if (!current_user_can('administrator') && current_user_can('broker')) {
            // Definir o status como pendente
            wp_update_post(array(
                'ID' => $post_id,
                'post_status' => 'pending',
            ));

            // Adicionar meta para indicar que é um imóvel que precisa de aprovação
            update_post_meta($post_id, 'needs_approval', 'yes');

            // Enviar notificação para o administrador sobre o novo imóvel
            send_admin_notification_for_approval($post_id);

            // Definir cookie para mostrar notificação na próxima página
            setcookie('immobile_created', 'yes', time() + 300, COOKIEPATH, COOKIE_DOMAIN);
        }
    }
}
add_action('wp_insert_post', 'set_immobile_pending_on_save', 10, 3);

/**
 * Envia e-mail para administradores sobre um novo imóvel para aprovação
 */
function send_admin_notification_for_approval($post_id) {
    $post = get_post($post_id);
    $broker = get_userdata($post->post_author);
    $admin_email = get_option('admin_email');
    
    $subject = '[SoCasaTop] Novo imóvel para aprovação';
    
    $message = sprintf(
        'Olá Administrador,<br><br>Um novo imóvel foi enviado para aprovação.<br><br>Título: %s<br>Corretor: %s (%s)<br><br>Por favor, acesse a área administrativa para revisar e aprovar este imóvel.<br><br>Link: %s<br><br>Atenciosamente,<br>SoCasaTop',
        $post->post_title,
        $broker->display_name,
        $broker->user_email,
        admin_url('edit.php?post_type=immobile&post_status=pending')
    );
    
    $headers = array('Content-Type: text/html; charset=UTF-8');
    
    wp_mail($admin_email, $subject, $message, $headers);
}

/**
 * Mostrar mensagem para o corretor após criar um imóvel
 */
function show_immobile_created_notice() {
    if (isset($_COOKIE['immobile_created']) && $_COOKIE['immobile_created'] === 'yes') {
        // Limpar o cookie
        setcookie('immobile_created', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
        
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p>Seu imóvel foi enviado para aprovação. Um administrador revisará as informações em breve.</p>';
        echo '</div>';
    }
}
add_action('admin_notices', 'show_immobile_created_notice');

/**
 * Enviar notificação quando um imóvel for aprovado ou reprovado
 */
function send_broker_notification($post_id, $status, $rejection_reason = '') {
    $post = get_post($post_id);
    $broker = get_userdata($post->post_author);
    
    if ($status === 'approved') {
        $subject = '[SoCasaTop] Seu imóvel foi aprovado';
        
        $message = sprintf(
            'Olá %s,<br><br>Seu imóvel "%s" foi aprovado e já está publicado no site.<br><br>Você pode visualizá-lo no seguinte link: %s<br><br>Atenciosamente,<br>SoCasaTop',
            $broker->display_name,
            $post->post_title,
            get_permalink($post_id)
        );
    } else {
        $subject = '[SoCasaTop] Seu imóvel precisa de ajustes';
        
        $message = sprintf(
            'Olá %s,<br><br>Seu imóvel "%s" foi revisado, mas precisa de algumas modificações antes de ser publicado.<br><br>Motivo: %s<br><br>Por favor, edite o imóvel corrigindo os pontos mencionados e envie novamente para aprovação.<br><br>Link para edição: %s<br><br>Atenciosamente,<br>SoCasaTop',
            $broker->display_name,
            $post->post_title,
            $rejection_reason,
            admin_url('post.php?post=' . $post_id . '&action=edit')
        );
    }
    
    $headers = array('Content-Type: text/html; charset=UTF-8');
    
    wp_mail($broker->user_email, $subject, $message, $headers);
}

/**
 * Shortcode para exibir a página de aprovação de imóveis
 */
function immobile_approval_shortcode() {
    ob_start();
    display_immobile_approval_page();
    return ob_get_clean();
}
add_shortcode('immobile_approval', 'immobile_approval_shortcode');

/**
 * Função para exibir a página de aprovação de imóveis
 */
function display_immobile_approval_page() {
    // Verificar se o usuário é administrador
    if (!current_user_can('administrator')) {
        echo '<div class="immobile-approval-container">';
        echo '<p>Você não tem permissão para acessar esta página. Esta funcionalidade é restrita a administradores.</p>';
        echo '</div>';
        return;
    }

    // Enqueue CSS e JS
    wp_enqueue_style('immobile-approval-style', get_template_directory_uri() . '/inc/custom/immobile/assets/approval.css', array(), '1.0.0');
    wp_enqueue_script('immobile-approval-script', get_template_directory_uri() . '/inc/custom/immobile/assets/approval.js', array('jquery'), '1.0.0', true);
    
    // Passar variáveis para o script
    wp_localize_script('immobile-approval-script', 'approval_vars', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('immobile_approval_nonce')
    ));

    // Obter filtros
    $broker_filter = isset($_GET['broker']) ? sanitize_text_field($_GET['broker']) : '';
    $date_start = isset($_GET['date_start']) ? sanitize_text_field($_GET['date_start']) : '';
    $date_end = isset($_GET['date_end']) ? sanitize_text_field($_GET['date_end']) : '';

    // Configurar os argumentos da consulta
    $args = array(
        'post_type' => 'immobile',
        'post_status' => 'pending',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => 'needs_approval',
                'value' => 'yes',
                'compare' => '='
            )
        )
    );

    // Adicionar filtro por corretor
    if (!empty($broker_filter)) {
        $args['author'] = $broker_filter;
    }

    // Adicionar filtro por data
    if (!empty($date_start) || !empty($date_end)) {
        $date_query = array();
        
        if (!empty($date_start)) {
            $date_query['after'] = $date_start . ' 00:00:00';
        }
        
        if (!empty($date_end)) {
            $date_query['before'] = $date_end . ' 23:59:59';
        }
        
        $date_query['inclusive'] = true;
        
        $args['date_query'] = array($date_query);
    }

    // Executar consulta
    $immobiles_query = new WP_Query($args);

    // Obter corretores
    $brokers = get_users(array(
        'role__in' => array('broker', 'author'),
    ));

    ?>
    <div class="immobile-approval-container">
        <h2>Aprovação de Imóveis</h2>
        
        <div class="approval-filters">
            <form id="approval-filter-form" class="filter-form" method="get">
                <div class="filter-group">
                    <label for="broker_filter">Corretor:</label>
                    <select name="broker" id="broker_filter">
                        <option value="">Todos os corretores</option>
                        <?php foreach ($brokers as $broker) : ?>
                            <option value="<?php echo esc_attr($broker->ID); ?>" <?php selected($broker_filter, $broker->ID); ?>>
                                <?php echo esc_html($broker->display_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="date_start">Data Inicial:</label>
                    <input type="date" id="date_start" name="date_start" value="<?php echo esc_attr($date_start); ?>">
                </div>
                
                <div class="filter-group">
                    <label for="date_end">Data Final:</label>
                    <input type="date" id="date_end" name="date_end" value="<?php echo esc_attr($date_end); ?>">
                </div>
                
                <button type="submit" class="filter-button">Filtrar</button>
                <a href="#" id="reset-filters" class="reset-button">Limpar Filtros</a>
            </form>
        </div>
        
        <?php if ($immobiles_query->have_posts()) : ?>
            <table class="approval-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Título</th>
                        <th>Corretor</th>
                        <th>Data de Envio</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($immobiles_query->have_posts()) : $immobiles_query->the_post(); 
                        $post_id = get_the_ID();
                        $author_id = get_post_field('post_author', $post_id);
                        $author = get_userdata($author_id);
                        $author_name = $author ? $author->display_name : 'Usuário desconhecido';
                        
                        // Criar URL de visualização temporária
                        $preview_url = add_query_arg(array(
                            'preview' => 'true',
                            'post_type' => 'immobile',
                            'p' => $post_id,
                            '_wpnonce' => wp_create_nonce('post_preview_' . $post_id)
                        ), home_url());
                    ?>
                        <tr>
                            <td><?php echo esc_html($post_id); ?></td>
                            <td>
                                <a href="<?php echo esc_url(get_edit_post_link($post_id)); ?>" target="_blank">
                                    <?php echo esc_html(get_the_title()); ?>
                                </a>
                            </td>
                            <td><?php echo esc_html($author_name); ?></td>
                            <td><?php echo esc_html(get_the_date('d/m/Y H:i')); ?></td>
                            <td class="action-buttons">
                                <a href="<?php echo esc_url($preview_url); ?>" target="_blank" class="preview-button">Visualizar</a>
                                <button class="approve-button" data-id="<?php echo esc_attr($post_id); ?>">Aprovar</button>
                                <button class="reject-button" data-id="<?php echo esc_attr($post_id); ?>">Reprovar</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else : ?>
            <div class="no-items">Não há imóveis pendentes de aprovação no momento.</div>
        <?php endif; ?>
        
        <?php wp_reset_postdata(); ?>
        
        <!-- Modal de Reprovação -->
        <div id="reject-modal" class="modal">
            <div class="modal-content">
                <span class="close-modal">&times;</span>
                <h3>Motivo da Reprovação</h3>
                <form id="rejection-form">
                    <input type="hidden" id="immobile_id" name="immobile_id" value="">
                    <textarea id="rejection_reason" name="rejection_reason" placeholder="Descreva o motivo da reprovação que será enviado ao corretor"></textarea>
                    <div class="form-buttons">
                        <button type="button" class="cancel-button">Cancelar</button>
                        <button type="submit" class="submit-button">Confirmar Reprovação</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Funções AJAX para aprovar e rejeitar imóveis
 */
function approve_immobile_ajax() {
    // Verificar nonce
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'immobile_approval_nonce')) {
        wp_send_json_error('Erro de segurança');
        return;
    }
    
    // Verificar permissões
    if (!current_user_can('administrator')) {
        wp_send_json_error('Você não tem permissão para realizar esta ação');
        return;
    }
    
    // Obter ID do imóvel
    $immobile_id = isset($_POST['immobile_id']) ? intval($_POST['immobile_id']) : 0;
    
    if (!$immobile_id) {
        wp_send_json_error('ID do imóvel inválido');
        return;
    }
    
    // Atualizar status para publicado
    $result = wp_update_post(array(
        'ID' => $immobile_id,
        'post_status' => 'publish'
    ));
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
        return;
    }
    
    // Atualizar meta
    update_post_meta($immobile_id, 'needs_approval', 'no');
    
    // Registrar no log
    log_immobile_approval_activity($immobile_id, 'approved', get_current_user_id(), 'Imóvel aprovado e publicado');
    
    // Enviar notificação
    send_broker_notification($immobile_id, 'approved');
    
    wp_send_json_success('Imóvel aprovado com sucesso');
}
add_action('wp_ajax_approve_immobile', 'approve_immobile_ajax');

function reject_immobile_ajax() {
    // Verificar nonce
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'immobile_approval_nonce')) {
        wp_send_json_error('Erro de segurança');
        return;
    }
    
    // Verificar permissões
    if (!current_user_can('administrator')) {
        wp_send_json_error('Você não tem permissão para realizar esta ação');
        return;
    }
    
    // Obter dados
    $immobile_id = isset($_POST['immobile_id']) ? intval($_POST['immobile_id']) : 0;
    $rejection_reason = isset($_POST['rejection_reason']) ? sanitize_textarea_field($_POST['rejection_reason']) : '';
    
    if (!$immobile_id) {
        wp_send_json_error('ID do imóvel inválido');
        return;
    }
    
    if (empty($rejection_reason)) {
        wp_send_json_error('É necessário informar o motivo da reprovação');
        return;
    }
    
    // Atualizar status para rascunho
    $result = wp_update_post(array(
        'ID' => $immobile_id,
        'post_status' => 'draft'
    ));
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
        return;
    }
    
    // Atualizar meta
    update_post_meta($immobile_id, 'needs_approval', 'no');
    update_post_meta($immobile_id, 'rejection_reason', $rejection_reason);
    
    // Registrar no log
    log_immobile_approval_activity($immobile_id, 'rejected', get_current_user_id(), $rejection_reason);
    
    // Enviar notificação
    send_broker_notification($immobile_id, 'rejected', $rejection_reason);
    
    wp_send_json_success('Imóvel reprovado com sucesso');
}
add_action('wp_ajax_reject_immobile', 'reject_immobile_ajax');

/**
 * Adicionar contador de imóveis pendentes no menu do administrador
 */
function add_pending_immobiles_count_to_menu() {
    // Verificar se o usuário é administrador
    if (!current_user_can('administrator')) {
        return;
    }
    
    global $menu;
    
    // Contar imóveis pendentes
    $args = array(
        'post_type' => 'immobile',
        'post_status' => 'pending',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => 'needs_approval',
                'value' => 'yes',
                'compare' => '='
            )
        )
    );
    
    $pending_query = new WP_Query($args);
    $count = $pending_query->found_posts;
    
    // Se não houver imóveis pendentes, não mostrar o contador
    if ($count < 1) {
        return;
    }
    
    // Procurar o menu de imóveis
    foreach ($menu as $key => $value) {
        if (isset($value[2]) && $value[2] === 'edit.php?post_type=immobile') {
            // Adicionar o contador
            $menu[$key][0] .= ' <span class="awaiting-mod">' . $count . '</span>';
            break;
        }
    }
}
add_action('admin_menu', 'add_pending_immobiles_count_to_menu');

/**
 * Registrar atividade de aprovação/rejeição no log
 */
function log_immobile_approval_activity($immobile_id, $action, $user_id = null, $details = '') {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    $user = get_userdata($user_id);
    $immobile = get_post($immobile_id);
    
    if (!$immobile || $immobile->post_type !== 'immobile') {
        return false;
    }
    
    $broker_id = $immobile->post_author;
    $broker = get_userdata($broker_id);
    
    $log_entry = array(
        'post_title'    => sprintf('Log: %s - %s', $action, $immobile->post_title),
        'post_content'  => wp_json_encode(array(
            'immobile_id'    => $immobile_id,
            'immobile_title' => $immobile->post_title,
            'action'         => $action,
            'user_id'        => $user_id,
            'user_name'      => $user ? $user->display_name : 'Sistema',
            'broker_id'      => $broker_id,
            'broker_name'    => $broker ? $broker->display_name : 'Desconhecido',
            'details'        => $details,
            'timestamp'      => current_time('mysql')
        )),
        'post_status'   => 'publish',
        'post_type'     => 'immobile_log',
        'post_author'   => $user_id,
    );
    
    return wp_insert_post($log_entry);
}

/**
 * Registrar tipo de post personalizado para o log
 */
function register_immobile_log_post_type() {
    $args = array(
        'public'              => false,
        'publicly_queryable'  => false,
        'show_ui'             => true,
        'show_in_menu'        => 'edit.php?post_type=immobile',
        'query_var'           => false,
        'capability_type'     => 'post',
        'has_archive'         => false,
        'hierarchical'        => false,
        'menu_position'       => null,
        'supports'            => array('title'),
        'labels'              => array(
            'name'               => 'Logs de Imóveis',
            'singular_name'      => 'Log de Imóvel',
            'menu_name'          => 'Logs de Atividade',
            'all_items'          => 'Todos os Logs',
            'add_new'            => 'Adicionar Novo',
            'add_new_item'       => 'Adicionar Novo Log',
            'edit_item'          => 'Editar Log',
            'new_item'           => 'Novo Log',
            'view_item'          => 'Ver Log',
            'search_items'       => 'Buscar Logs',
            'not_found'          => 'Nenhum log encontrado',
            'not_found_in_trash' => 'Nenhum log encontrado na lixeira',
        ),
    );
    
    register_post_type('immobile_log', $args);
}
add_action('init', 'register_immobile_log_post_type');

/**
 * Personalizar colunas na listagem de logs
 */
function customize_immobile_log_columns($columns) {
    $new_columns = array(
        'cb'           => $columns['cb'],
        'title'        => 'Título',
        'immobile'     => 'Imóvel',
        'action'       => 'Ação',
        'user'         => 'Usuário',
        'broker'       => 'Corretor',
        'date'         => 'Data'
    );
    
    return $new_columns;
}
add_filter('manage_immobile_log_posts_columns', 'customize_immobile_log_columns');

/**
 * Preencher conteúdo das colunas personalizadas
 */
function fill_immobile_log_columns($column, $post_id) {
    $log_data = json_decode(get_post_field('post_content', $post_id), true);
    
    if (!$log_data) {
        echo '-';
        return;
    }
    
    switch ($column) {
        case 'immobile':
            $immobile_id = isset($log_data['immobile_id']) ? $log_data['immobile_id'] : 0;
            $immobile_title = isset($log_data['immobile_title']) ? $log_data['immobile_title'] : 'Desconhecido';
            
            if ($immobile_id) {
                echo '<a href="' . esc_url(get_edit_post_link($immobile_id)) . '">' . esc_html($immobile_title) . '</a>';
            } else {
                echo esc_html($immobile_title);
            }
            break;
            
        case 'action':
            $action = isset($log_data['action']) ? $log_data['action'] : '-';
            
            switch ($action) {
                case 'approved':
                    echo '<span style="color: green;">Aprovado</span>';
                    break;
                case 'rejected':
                    echo '<span style="color: red;">Reprovado</span>';
                    break;
                default:
                    echo esc_html($action);
            }
            break;
            
        case 'user':
            $user_name = isset($log_data['user_name']) ? $log_data['user_name'] : '-';
            echo esc_html($user_name);
            break;
            
        case 'broker':
            $broker_name = isset($log_data['broker_name']) ? $log_data['broker_name'] : '-';
            echo esc_html($broker_name);
            break;
    }
}
add_action('manage_immobile_log_posts_custom_column', 'fill_immobile_log_columns', 10, 2);