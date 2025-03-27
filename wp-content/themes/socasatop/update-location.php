<?php
/**
 * Script temporário para atualizar o nome da localidade de "ARNIQUEIRAS" para "ARNIQUEIRA"
 * 
 * Para usar: adicione este arquivo no tema e acesse: /wp-content/themes/socasatop/update-location.php
 * Depois de executar, remova este arquivo por motivos de segurança.
 */

// Carregar o WordPress
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php');

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
        
        // Listar todos os termos da taxonomia para verificação
        echo "<br><br>Termos existentes na taxonomia 'locations':<br>";
        $all_terms = get_terms(array(
            'taxonomy' => 'locations',
            'hide_empty' => false,
        ));
        
        if (!empty($all_terms) && !is_wp_error($all_terms)) {
            foreach ($all_terms as $term) {
                echo "- " . $term->name . " (ID: " . $term->term_id . ")<br>";
            }
        } else {
            echo "Nenhum termo encontrado ou erro ao buscar termos.";
        }
    }
} 