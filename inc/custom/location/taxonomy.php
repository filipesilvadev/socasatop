<?php

function create_location_taxonomy()
{
    $labels = array(
        'name' => 'Localidades',
        'singular_name' => 'Localidade',
        'search_items' => 'Pesquisar localidade',
        'all_items' => 'Todas localidades',
        'edit_item' => 'Editar localidade',
        'update_item' => 'Atualizar localidade',
        'add_new_item' => 'Adicionar nova localidades',
        'new_item_name' => 'Nova localidade',
        'menu_name' => 'Localidades',
    );

    register_taxonomy('locations', array('immobile'), array(
        'hierarchical' => true,
        'labels' => $labels,
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'location'),
    ));
}
add_action('init', 'create_location_taxonomy', 0);


function display_locations()
{
    ob_start();
    require_once(__DIR__ . "/list.php");
    $content = ob_get_clean();
    return $content;
}
add_shortcode('locations', 'display_locations');


function display_form_location()
{
    ob_start();
    require_once(__DIR__ . "/form.php");
    $content = ob_get_clean();
    return $content;
}
add_shortcode('form_location', 'display_form_location');