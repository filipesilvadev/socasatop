<?php
/**
 * Registro do post type Lead
 */

// Garantir que o arquivo seja acessado apenas pelo WordPress
if (!defined('ABSPATH')) {
    exit;
}

function register_lead_post_type() {
    $labels = array(
        'name'                  => 'Leads',
        'singular_name'         => 'Lead',
        'menu_name'             => 'Leads',
        'name_admin_bar'        => 'Lead',
        'archives'              => 'Arquivo de Leads',
        'attributes'            => 'Atributos do Lead',
        'parent_item_colon'     => 'Lead Pai:',
        'all_items'             => 'Todos os Leads',
        'add_new_item'          => 'Adicionar Novo Lead',
        'add_new'               => 'Adicionar Novo',
        'new_item'              => 'Novo Lead',
        'edit_item'             => 'Editar Lead',
        'update_item'           => 'Atualizar Lead',
        'view_item'             => 'Ver Lead',
        'view_items'            => 'Ver Leads',
        'search_items'          => 'Buscar Lead',
        'not_found'             => 'Não encontrado',
        'not_found_in_trash'    => 'Não encontrado na Lixeira',
        'featured_image'        => 'Imagem Destacada',
        'set_featured_image'    => 'Definir imagem destacada',
        'remove_featured_image' => 'Remover imagem destacada',
        'use_featured_image'    => 'Usar como imagem destacada',
        'insert_into_item'      => 'Inserir no Lead',
        'uploaded_to_this_item' => 'Enviado para este Lead',
        'items_list'            => 'Lista de Leads',
        'items_list_navigation' => 'Navegação da lista de Leads',
        'filter_items_list'     => 'Filtrar lista de Leads',
    );
    
    $args = array(
        'label'                 => 'Lead',
        'description'           => 'Lead de contato para imóveis',
        'labels'                => $labels,
        'supports'              => array('title'),
        'hierarchical'          => false,
        'public'                => false,
        'show_ui'               => true,
        'show_in_menu'          => true,  // Mostrar no menu para acesso direto
        'menu_position'         => 25,
        'menu_icon'             => 'dashicons-admin-users',
        'show_in_admin_bar'     => false,
        'show_in_nav_menus'     => false,
        'can_export'            => true,
        'has_archive'           => false,
        'exclude_from_search'   => true,
        'publicly_queryable'    => false,
        'capability_type'       => 'post',
        'map_meta_cap'          => true,
    );
    
    register_post_type('lead', $args);
}
add_action('init', 'register_lead_post_type');

// Incluir arquivo de colunas personalizadas e filtros para a listagem de leads
require_once dirname(__FILE__) . '/columns.php'; 