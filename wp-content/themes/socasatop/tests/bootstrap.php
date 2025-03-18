<?php
/**
 * Bootstrap do PHPUnit para testes do WordPress
 */

$_tests_dir = getenv('WP_TESTS_DIR');
if (!$_tests_dir) {
    $_tests_dir = '/tmp/wordpress-tests-lib';
}

// Carregar o framework de teste do WordPress
require_once $_tests_dir . '/includes/functions.php';

function _manually_load_theme() {
    switch_theme('socasatop');
}
tests_add_filter('muplugins_loaded', '_manually_load_theme');

// Iniciar o framework de teste
require $_tests_dir . '/includes/bootstrap.php';

// Carregar funções do tema
require dirname(dirname(__FILE__)) . '/functions.php'; 