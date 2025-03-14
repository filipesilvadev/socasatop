<?php
if (!defined('ABSPATH')) exit;

function sct_landing_page_shortcode($atts) {
    ob_start();
    include dirname(__FILE__) . '/template.php';
    return ob_get_clean();
}

function sct_register_shortcode() {
    add_shortcode('landing_page', 'sct_landing_page_shortcode');
}
add_action('init', 'sct_register_shortcode');

function sct_enqueue_landing_assets() {
    wp_enqueue_style('sct-landing-style', get_stylesheet_directory_uri() . '/inc/custom/landing-page/assets/css/style.css', array(), '1.0.0');
    wp_enqueue_script('sct-landing-script', get_stylesheet_directory_uri() . '/inc/custom/landing-page/assets/js/script.js', array('jquery'), '1.0.0', true);
}
add_action('wp_enqueue_scripts', 'sct_enqueue_landing_assets');