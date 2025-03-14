<?php
function process_immobile_creation_payment() {
    check_ajax_referer('ajax_nonce', 'nonce');
    
    require_once get_stylesheet_directory() . '/inc/custom/immobile/mercadopago.php';
    
    $payment_data = $_POST['payment_data'];
    $immobile_list = $_POST['immobile_list'];
    
    try {
        $mp_payment = new Immobile_Payment();
        $payment_response = $mp_payment->process_payment($payment_data);
        
        error_log('Resposta Mercado Pago: ' . print_r($payment_response, true));
        
        if (isset($payment_response['status'])) {
            if ($payment_response['status'] === 'approved' || $payment_response['status'] === 'in_process') {
                foreach ($immobile_list as $immobile) {
                    $post_data = array(
                        'post_title'    => $immobile['location'] . ' - ' . $immobile['property_type'],
                        'post_status'   => 'pending',
                        'post_type'     => 'immobile'
                    );
                    
                    $post_id = wp_insert_post($post_data);
                    
                    if ($post_id) {
                        foreach ($immobile as $key => $value) {
                            update_post_meta($post_id, $key, $value);
                        }
                        update_post_meta($post_id, 'needs_approval', 'yes');
                        
                        if (function_exists('send_admin_notification_for_approval')) {
                            send_admin_notification_for_approval($post_id);
                        }
                    }
                }
                
                wp_send_json_success(['message' => 'Pagamento processado e imóveis salvos com sucesso']);
            } else {
                wp_send_json_error(['message' => 'Status do pagamento: ' . $payment_response['status']]);
            }
        } else {
            wp_send_json_error(['message' => isset($payment_response['message']) ? $payment_response['message'] : 'Erro desconhecido']);
        }
    } catch (Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
    
    wp_die();
}
add_action('wp_ajax_process_immobile_creation_payment', 'process_immobile_creation_payment');