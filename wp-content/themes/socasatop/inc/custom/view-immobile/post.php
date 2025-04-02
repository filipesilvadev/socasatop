<?php
function view_immobile_post()
{
    register_post_type(
        'listaimoveis',
        array(
            'labels' => array(
                'name' => "Listas de Imóveis",
                'singular_name' => "Lista de Imóveis",
                'menu_name' => 'Listas de Imóveis',
                'add_new' => 'Adicionar Nova',
                'add_new_item' => 'Adicionar Nova Lista',
                'edit_item' => 'Editar Lista',
                'view_item' => 'Ver Lista',
                'search_items' => 'Buscar Listas',
                'not_found' => 'Nenhuma lista encontrada',
                'not_found_in_trash' => 'Nenhuma lista encontrada na lixeira'
            ),
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array(
                'slug' => 'listaimoveis',
                'with_front' => true,
                'pages' => true,
                'feeds' => true,
            ),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 6,
            'supports' => array('title', 'editor'),
            'menu_icon' => 'dashicons-welcome-view-site',
            'show_in_rest' => true
        )
    );
}
add_action('init', 'view_immobile_post');

/**
 * Força a atualização das regras de rewrite do WordPress
 * Deve ser chamado apenas quando necessário
 */
function view_immobile_rewrite_flush() {
    // Primeiro, registre o tipo de post
    view_immobile_post();
    
    // Depois atualize as regras de rewrite
    flush_rewrite_rules();
}

// Adicionar uma ação para forçar atualização das regras quando necessário
add_action('after_switch_theme', 'view_immobile_rewrite_flush');

function view_immobile_fields_meta_box()
{
    add_meta_box(
        'view_immobile_fields_meta_box',
        'Dados Imóveis',
        'view_immobile_fields_meta_box_callback',
        'listaimoveis',
        'normal',
        'high'
    );
}

function view_immobile_fields_meta_box_callback($post)
{
    include_once __DIR__ . "/fields.php";
}
add_action('add_meta_boxes', 'view_immobile_fields_meta_box');

function save_view_imoveis_field_meta($post_id)
{
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $fields_to_save = array(
        'immobile_ids'
    );

    foreach ($fields_to_save as $field_name) {
        if (isset($_POST[$field_name])) {
            update_post_meta(
                $post_id,
                "$field_name",
                $_POST[$field_name]
            );
        }
    }
}
add_action('save_post_listaimoveis', 'save_view_imoveis_field_meta');