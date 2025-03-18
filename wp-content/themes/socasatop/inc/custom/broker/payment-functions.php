<?php
/**
 * Funções compartilhadas para processamento de pagamentos
 */

function process_payment_with_saved_card($card_id, $amount, $description = '') {
    if (!is_user_logged_in()) {
        return array(
            'success' => false,
            'message' => 'Usuário não autenticado'
        );
    }
    
    $user_id = get_current_user_id();
    $saved_cards = get_user_meta($user_id, 'mercadopago_cards', true);
    
    if (!is_array($saved_cards)) {
        return array(
            'success' => false,
            'message' => 'Nenhum cartão encontrado'
        );
    }
    
    $card = null;
    foreach ($saved_cards as $saved_card) {
        if ($saved_card['id'] === $card_id) {
            $card = $saved_card;
            break;
        }
    }
    
    if (!$card) {
        return array(
            'success' => false,
            'message' => 'Cartão não encontrado'
        );
    }
    
    try {
        $mp_config = get_mercadopago_config();
        
        if (empty($mp_config['access_token'])) {
            throw new Exception('Token de acesso do Mercado Pago não configurado');
        }
        
        // Criar pagamento no Mercado Pago
        $payment_data = array(
            'transaction_amount' => floatval($amount),
            'token' => $card['token'],
            'description' => $description ?: 'Pagamento SoCasa Top',
            'installments' => 1,
            'payment_method_id' => $card['payment_method_id'],
            'payer' => array(
                'email' => wp_get_current_user()->user_email,
                'identification' => array(
                    'type' => 'CPF',
                    'number' => $card['identification_number']
                )
            )
        );
        
        // Fazer requisição para a API do Mercado Pago
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.mercadopago.com/v1/payments');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payment_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $mp_config['access_token'],
            'Content-Type: application/json'
        ));
        
        $response = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new Exception('Erro na requisição: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        if ($http_status !== 201) {
            $error_data = json_decode($response, true);
            throw new Exception(
                isset($error_data['message']) ? $error_data['message'] : 'Erro ao processar pagamento'
            );
        }
        
        $payment_response = json_decode($response, true);
        
        // Salvar informações do pagamento
        $payment_info = array(
            'id' => $payment_response['id'],
            'status' => $payment_response['status'],
            'status_detail' => $payment_response['status_detail'],
            'date_created' => $payment_response['date_created'],
            'date_approved' => $payment_response['date_approved'],
            'payment_method_id' => $payment_response['payment_method_id'],
            'payment_type_id' => $payment_response['payment_type_id'],
            'transaction_amount' => $payment_response['transaction_amount'],
            'card_last_four_digits' => $card['last_four'],
            'description' => $payment_data['description']
        );
        
        update_user_meta($user_id, 'last_payment_info', $payment_info);
        
        return array(
            'success' => true,
            'payment_info' => $payment_info
        );
        
    } catch (Exception $e) {
        error_log('Erro ao processar pagamento com cartão salvo: ' . $e->getMessage());
        return array(
            'success' => false,
            'message' => $e->getMessage()
        );
    }
}

function process_payment_with_new_card($payment_data) {
    if (!is_user_logged_in()) {
        return array(
            'success' => false,
            'message' => 'Usuário não autenticado'
        );
    }
    
    try {
        $mp_config = get_mercadopago_config();
        
        if (empty($mp_config['access_token'])) {
            throw new Exception('Token de acesso do Mercado Pago não configurado');
        }
        
        // Fazer requisição para a API do Mercado Pago
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.mercadopago.com/v1/payments');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payment_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $mp_config['access_token'],
            'Content-Type: application/json'
        ));
        
        $response = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new Exception('Erro na requisição: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        if ($http_status !== 201) {
            $error_data = json_decode($response, true);
            throw new Exception(
                isset($error_data['message']) ? $error_data['message'] : 'Erro ao processar pagamento'
            );
        }
        
        $payment_response = json_decode($response, true);
        
        // Salvar informações do pagamento
        $payment_info = array(
            'id' => $payment_response['id'],
            'status' => $payment_response['status'],
            'status_detail' => $payment_response['status_detail'],
            'date_created' => $payment_response['date_created'],
            'date_approved' => $payment_response['date_approved'],
            'payment_method_id' => $payment_response['payment_method_id'],
            'payment_type_id' => $payment_response['payment_type_id'],
            'transaction_amount' => $payment_response['transaction_amount'],
            'card_last_four_digits' => substr($payment_response['card']['last_four_digits'], -4),
            'description' => $payment_data['description']
        );
        
        update_user_meta(get_current_user_id(), 'last_payment_info', $payment_info);
        
        return array(
            'success' => true,
            'payment_info' => $payment_info
        );
        
    } catch (Exception $e) {
        error_log('Erro ao processar pagamento com novo cartão: ' . $e->getMessage());
        return array(
            'success' => false,
            'message' => $e->getMessage()
        );
    }
}

// A função get_card_brand_logo foi movida para payment-settings.php para evitar duplicação
// e manter uma única fonte de verdade para o gerenciamento de logos dos cartões

function format_card_expiry($month, $year) {
    return sprintf('%02d/%s', intval($month), substr($year, -2));
}

function mask_card_number($number) {
    return '•••• •••• •••• ' . substr($number, -4);
}

function is_valid_card_expiry($month, $year) {
    $current_year = intval(date('Y'));
    $current_month = intval(date('m'));
    
    $expiry_year = intval($year);
    $expiry_month = intval($month);
    
    if ($expiry_year < $current_year) {
        return false;
    }
    
    if ($expiry_year === $current_year && $expiry_month < $current_month) {
        return false;
    }
    
    return true;
} 