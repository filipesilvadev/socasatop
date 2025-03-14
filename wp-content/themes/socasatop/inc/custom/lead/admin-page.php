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
        <h1>Gerenciamento de Leads</h1>
        
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
                    <th>Corretor</th>
                    <th>Data de Captura</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($leads)) : ?>
                    <tr>
                        <td colspan="6">Nenhum lead encontrado.</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($leads as $lead) : 
                        $immobile_id = get_post_meta($lead->ID, 'immobile_id', true);
                        $broker_id = get_post_meta($lead->ID, 'broker_id', true);
                        $immobile = get_post($immobile_id);
                        $broker = get_userdata($broker_id);
                    ?>
                        <tr>
                            <td><?php echo $lead->post_title; ?></td>
                            <td><?php echo get_post_meta($lead->ID, 'email', true); ?></td>
                            <td><?php echo get_post_meta($lead->ID, 'whatsapp', true); ?></td>
                            <td>
                                <?php if ($immobile) : ?>
                                    <a href="<?php echo get_permalink($immobile_id); ?>" target="_blank">
                                        <?php echo $immobile->post_title; ?>
                                    </a>
                                <?php else : ?>
                                    N/A
                                <?php endif; ?>
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
    <?php
} 