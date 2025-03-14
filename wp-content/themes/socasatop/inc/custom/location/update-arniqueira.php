<?php
/**
 * Script para atualizar o nome da localidade de "ARNIQUEIRAS" para "ARNIQUEIRA"
 */

// Verificar se o usuário está logado e é administrador
if (!current_user_can('manage_options')) {
    wp_die('Acesso negado.');
}

// Procurar o termo ARNIQUEIRAS
$term = get_term_by('name', 'ARNIQUEIRAS', 'locations');
if ($term) {
    // Atualizar o nome para ARNIQUEIRA
    $result = wp_update_term($term->term_id, 'locations', array(
        'name' => 'ARNIQUEIRA'
    ));
    
    if (!is_wp_error($result)) {
        echo "Localidade atualizada com sucesso de 'ARNIQUEIRAS' para 'ARNIQUEIRA'.";
    } else {
        echo "Erro ao atualizar a localidade: " . $result->get_error_message();
    }
} else {
    // Procurar com variações de capitalização
    $term = get_term_by('name', 'Arniqueiras', 'locations');
    if ($term) {
        $result = wp_update_term($term->term_id, 'locations', array(
            'name' => 'ARNIQUEIRA'
        ));
        
        if (!is_wp_error($result)) {
            echo "Localidade atualizada com sucesso de 'Arniqueiras' para 'ARNIQUEIRA'.";
        } else {
            echo "Erro ao atualizar a localidade: " . $result->get_error_message();
        }
    } else {
        echo "Termo 'ARNIQUEIRAS' não encontrado na taxonomia 'locations'.";
    }
} 