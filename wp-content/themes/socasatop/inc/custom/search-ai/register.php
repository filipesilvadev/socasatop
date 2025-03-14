<?php
if (!defined('ABSPATH')) {
    exit;
}

function register_smart_search_api() {
    require_once __DIR__ . '/api.php';
    new Smart_Search_API();
}
add_action('rest_api_init', 'register_smart_search_api');