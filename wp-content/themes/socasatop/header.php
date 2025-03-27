<?php
/**
 * The header for our theme
 *
 * @package SoCasaTop
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

$viewport_content = apply_filters( 'hello_elementor_viewport_content', 'width=device-width, initial-scale=1' );
$enable_skip_link = apply_filters( 'hello_elementor_enable_skip_link', true );
$skip_link_url = apply_filters( 'hello_elementor_skip_link_url', '#content' );
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="<?php echo esc_attr( $viewport_content ); ?>">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php if ( $enable_skip_link ) { ?>
<a class="skip-link screen-reader-text" href="<?php echo esc_url( $skip_link_url ); ?>"><?php echo esc_html__( 'Skip to content', 'hello-elementor' ); ?></a>
<?php } ?>

<?php
if ( ! function_exists( 'elementor_theme_do_location' ) || ! elementor_theme_do_location( 'header' ) ) {
    if ( function_exists('hello_elementor_display_header_footer') && hello_elementor_display_header_footer() ) {
        if ( did_action( 'elementor/loaded' ) && function_exists('hello_header_footer_experiment_active') && hello_header_footer_experiment_active() ) {
            get_template_part( 'template-parts/dynamic-header' );
        } else {
            get_template_part( 'template-parts/header' );
        }
    } else {
        // Fallback para quando as funções do Hello Elementor não estiverem disponíveis
        ?>
        <header id="masthead" class="site-header">
            <div class="site-branding">
                <?php the_custom_logo(); ?>
                <h1 class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></h1>
            </div>
            <nav id="site-navigation" class="main-navigation">
                <?php
                wp_nav_menu(
                    array(
                        'theme_location' => 'primary',
                        'menu_id'        => 'primary-menu',
                    )
                );
                ?>
            </nav>
        </header>
        <?php
    }
}
?>

<div id="content" class="site-content"> 