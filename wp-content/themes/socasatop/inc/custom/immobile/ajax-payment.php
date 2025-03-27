<?php
function process_immobile_creation_payment() {
    check_ajax_referer('immobile_nonce', 'nonce');
    
    require_once get_stylesheet_directory() . '/inc/custom/immobile/mercadopago.php';
    require_once get_stylesheet_directory() . '/inc/custom/broker/payment-settings.php';
    
    $payment_method = isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : 'new_card';
    $immobile_list = isset($_POST['immobile_list']) ? $_POST['immobile_list'] : [];
    
    if (empty($immobile_list)) {
        wp_send_json_error(['message' => 'Nenhum imóvel para salvar']);
        return;
    }
    
    try {
        $mp_payment = new Immobile_Payment();
        $payment_response = [];
        
        // Processar pagamento de acordo com o método escolhido
        if ($payment_method === 'saved_card') {
            // Processar pagamento com cartão salvo
            if (!isset($_POST['saved_card_id']) || empty($_POST['saved_card_id'])) {
                wp_send_json_error(['message' => 'ID do cartão salvo não fornecido']);
                return;
            }
            
            $user_id = get_current_user_id();
            $card_id = sanitize_text_field($_POST['saved_card_id']);
            $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
            
            // Buscar informações do cartão salvo
            $saved_cards = get_user_mercadopago_cards($user_id);
            $card_found = false;
            $card_data = null;
            
            // Procurar o cartão pelo ID
            foreach ($saved_cards as $id => $card) {
                if ($id === $card_id || (isset($card['id']) && $card['id'] === $card_id)) {
                    $card_data = $card;
                    $card_found = true;
                    break;
                }
            }
            
            if (!$card_found) {
                wp_send_json_error(['message' => 'Cartão não encontrado']);
                return;
            }
            
            // Criar dados para pagamento com cartão salvo
            $payment_data = [
                'saved_card_id' => $card_id,
                'amount' => $amount,
                'description' => 'Publicação de imóveis em SocasaTop',
                'user_id' => $user_id
            ];
            
            $payment_response = $mp_payment->process_saved_card_payment($payment_data);
            
        } else {
            // Processar pagamento com novo cartão
            if (!isset($_POST['payment_data']) || empty($_POST['payment_data'])) {
                wp_send_json_error(['message' => 'Dados de pagamento não fornecidos']);
                return;
            }
            
            $payment_data = $_POST['payment_data'];
            $payment_response = $mp_payment->process_payment($payment_data);
        }
        
        error_log('Resposta Mercado Pago: ' . print_r($payment_response, true));
        
        if (isset($payment_response['status'])) {
            if ($payment_response['status'] === 'approved' || $payment_response['status'] === 'in_process') {
                foreach ($immobile_list as $immobile) {
                    $post_data = array(
                        'post_title'    => $immobile['immobile_name'],
                        'post_content'  => isset($immobile['details']) ? $immobile['details'] : '',
                        'post_status'   => 'pending',
                        'post_type'     => 'immobile',
                        'post_author'   => get_current_user_id()
                    );
                    
                    $post_id = wp_insert_post($post_data);
                    
                    if ($post_id) {
                        foreach ($immobile as $key => $value) {
                            update_post_meta($post_id, $key, $value);
                        }
                        update_post_meta($post_id, 'needs_approval', 'yes');
                        update_post_meta($post_id, 'payment_id', isset($payment_response['id']) ? $payment_response['id'] : '');
                        update_post_meta($post_id, 'payment_status', $payment_response['status']);
                        update_post_meta($post_id, 'payment_method', $payment_method);
                        
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