<?php
/**
 * Restrições para usuários com papel de Autor
 * 
 * Este arquivo contém funções para restringir o acesso de usuários com papel de Autor
 * - Remove a barra de administração preta para Autores
 * - Bloqueia o acesso ao painel de administração (wp-admin) para Autores
 */

/**
 * Remove a barra de administração preta para usuários com papel de Autor
 */
function remove_admin_bar_for_authors() {
    // Verifica se o usuário está logado e tem o papel de Autor
    if (is_user_logged_in() && current_user_can('author') && !current_user_can('administrator')) {
        // Remove a barra de administração
        show_admin_bar(false);
    }
}
add_action('after_setup_theme', 'remove_admin_bar_for_authors');

/**
 * Bloqueia o acesso ao painel de administração para usuários com papel de Autor
 */
function restrict_admin_access_for_authors() {
    // Verifica se o usuário está na área administrativa, está logado e tem o papel de Autor
    if (is_admin() && is_user_logged_in() && current_user_can('author') && !current_user_can('administrator')) {
        // Exceções: permitir AJAX, uploads e acesso a API REST
        global $pagenow;
        if ($pagenow === 'admin-ajax.php' || $pagenow === 'async-upload.php' || $pagenow === 'rest-api') {
            return;
        }
        
        // Redireciona para a página inicial do site
        wp_redirect(home_url());
        exit;
    }
}
add_action('init', 'restrict_admin_access_for_authors');
