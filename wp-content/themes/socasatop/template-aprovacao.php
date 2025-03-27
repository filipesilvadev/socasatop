<?php
/**
 * Template Name: Aprovação de Imóveis
 * Description: Modelo para página de aprovação de imóveis
 */

// Verificar se o usuário está logado e é um administrador
if (!is_user_logged_in() || !current_user_can('administrator')) {
    wp_redirect(home_url());
    exit;
}

get_header();

// Carregar estilos e scripts necessários manualmente
wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
wp_enqueue_style('approval-styles', get_stylesheet_directory_uri() . '/inc/custom/immobile/assets/approval.css', array(), '1.0.0');
wp_enqueue_script('jquery-ui-datepicker');
wp_enqueue_script('approval-scripts', get_stylesheet_directory_uri() . '/inc/custom/immobile/assets/approval.js', array('jquery', 'jquery-ui-datepicker'), '1.0.0', true);
?>

<div class="container py-5">
    <div class="row">
        <div class="col-12">
            <?php
            // Chamar a função de exibição da página de aprovação
            if (function_exists('display_immobile_approval_page')) {
                display_immobile_approval_page();
            } else {
                echo '<div class="alert alert-danger">A função de aprovação de imóveis não está disponível. Por favor, verifique a instalação.</div>';
            }
            ?>
        </div>
    </div>
</div>

<?php get_footer(); ?> 