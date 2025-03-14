<?php
require_once __DIR__ . '/mercadopago.php';

add_action('rest_api_init', function() {
    register_rest_route('smart-search/v1', '/webhook', [
        'methods' => 'POST',
        'callback' => 'handle_mp_webhook',
        'permission_callback' => '__return_true'
    ]);
});

function handle_mp_webhook($request) {
    $body = $request->get_parsed_body();
    
    if ($body['type'] === 'payment') {
        $payment_id = $body['data']['id'];
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'sponsored_listings';
        
        $wpdb->update(
            $table_name,
            ['status' => $body['action'] === 'payment.approved' ? 'active' : 'inactive'],
            ['payment_id' => $payment_id],
            ['%s'],
            ['%s']
        );
    }
    
    return new WP_REST_Response(['success' => true], 200);
}