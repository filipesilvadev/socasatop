<?php
function view_immobile_post()
{
    register_post_type(
        'listaimoveis',
        array(
            'labels' => array(
                'name' => "Views Imóveis",
                'singular_name' => "View Imóvel"
            ),
            'public' => true,
            'supports' => array('title'),
            'menu_icon' => 'dashicons-welcome-view-site',
        )
    );
}
add_action('init', 'view_immobile_post');

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