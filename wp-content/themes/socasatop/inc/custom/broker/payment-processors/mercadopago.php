<?php
/**
 * Processador de Pagamento - Mercado Pago
 * 
 * Este arquivo contém as funções específicas para processar pagamentos com o Mercado Pago.
 * O objetivo é modularizar a integração para facilitar a manutenção e reduzir conflitos.
 */

// Impedir acesso direto ao arquivo
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Classe para processamento de pagamentos do Mercado Pago
 */
class Socasa_MercadoPago_Payment {
    // Configurações
    private $config;
    
    // Instância única (singleton)
    private static $instance = null;
    
    /**
     * Construtor
     */
    private function __construct() {
        // Carregar configurações
        $this->config = [
            'public_key' => get_option('mercadopago_public_key', ''),
            'access_token' => get_option('mercadopago_access_token', ''),
            'sandbox' => get_option('mercadopago_sandbox', 'yes') === 'yes'
        ];
        
        // Inicializar hooks
        $this->init_hooks();
    }
    
    /**
     * Inicializa hooks
     */
    private function init_hooks() {
        // Adicionar filtros para processamento de pagamento
        add_filter('socasa_process_payment_method_mercadopago', [$this, 'process_payment'], 10, 3);
        
        // Adicionar scripts necessários
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }
    
    /**
     * Obtém a instância única da classe (singleton)
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Carrega scripts necessários
     */
    public function enqueue_scripts() {
        // Registrar o SDK do Mercado Pago
        wp_register_script('mercadopago-sdk', 'https://sdk.mercadopago.com/js/v2', [], null, true);
    }
    
    /**
     * Processa um pagamento
     * 
     * @param array $result Resultado atual do processamento
     * @param array $payment_data Dados do pagamento
     * @param array $product Informações do produto
     * @return array Resultado do processamento
     */
    public function process_payment($result, $payment_data, $product) {
        // Verificar se temos acesso ao token
        if (empty($this->config['access_token'])) {
            return [
                'success' => false,
                'message' => 'Configuração de pagamento incompleta. Token de acesso não configurado.'
            ];
        }
        
        // Verificar método de pagamento
        $payment_method = isset($payment_data['payment_method']) ? $payment_data['payment_method'] : '';
        $payment_data_info = isset($payment_data['payment_data']) ? $payment_data['payment_data'] : [];
        
        switch ($payment_method) {
            case 'card':
                return $this->process_card_payment($payment_data_info, $product);
                
            case 'wallet':
                return $this->process_wallet_payment($payment_data_info, $product);
                
            case 'saved_card':
                return $this->process_saved_card_payment($payment_data_info, $product);
                
            default:
                return [
                    'success' => false,
                    'message' => 'Método de pagamento não suportado ou não especificado.'
                ];
        }
    }
    
    /**
     * Processa pagamento com cartão
     */
    private function process_card_payment($payment_data, $product) {
        // Verificar dados necessários
        if (empty($payment_data['token']) || empty($payment_data['payment_method_id'])) {
            return [
                'success' => false,
                'message' => 'Dados de pagamento incompletos.'
            ];
        }
        
        // Preparar dados para API
        $api_data = [
            'transaction_amount' => floatval($payment_data['transaction_amount']),
            'token' => $payment_data['token'],
            'description' => $payment_data['description'],
            'installments' => intval($payment_data['installments']),
            'payment_method_id' => $payment_data['payment_method_id'],
            'payer' => $payment_data['payer']
        ];
        
        // Incluir issuer_id se disponível
        if (!empty($payment_data['issuer_id'])) {
            $api_data['issuer_id'] = $payment_data['issuer_id'];
        }
        
        // Processar pagamento via API
        return $this->send_payment_request($api_data);
    }
    
    /**
     * Processa pagamento com wallet (pagamento via conta MP)
     */
    private function process_wallet_payment($payment_data, $product) {
        // Implementação depende da documentação específica da API
        // Neste exemplo, vamos simular um processamento bem-sucedido
        
        // Na implementação real, você chamaria a API apropriada
        return [
            'success' => true,
            'message' => 'Pagamento via wallet processado com sucesso!',
            'payment_id' => 'wallet_' . uniqid(),
            'status' => 'approved'
        ];
    }
    
    /**
     * Processa pagamento com cartão salvo
     */
    private function process_saved_card_payment($payment_data, $product) {
        // Verificar dados necessários
        if (empty($payment_data['saved_card_id'])) {
            return [
                'success' => false,
                'message' => 'ID do cartão salvo não fornecido.'
            ];
        }
        
        $user_id = get_current_user_id();
        $cards = get_user_meta($user_id, 'mercadopago_cards', true) ?: [];
        
        // Encontrar o cartão salvo
        $card_info = null;
        foreach ($cards as $card) {
            if ($card['id'] === $payment_data['saved_card_id']) {
                $card_info = $card;
                break;
            }
        }
        
        if (!$card_info) {
            return [
                'success' => false,
                'message' => 'Cartão não encontrado.'
            ];
        }
        
        // Preparar dados para API
        $api_data = [
            'transaction_amount' => floatval($payment_data['amount']),
            'description' => $payment_data['description'],
            'installments' => 1,
            'payment_method_id' => $card_info['payment_method_id'],
            'token' => $card_info['token'],
            'payer' => [
                'email' => $card_info['email']
            ]
        ];
        
        // Processar pagamento via API
        return $this->send_payment_request($api_data);
    }
    
    /**
     * Envia requisição de pagamento para a API do Mercado Pago
     */
    private function send_payment_request($api_data) {
        // Configurar a requisição cURL
        $curl = curl_init();
        
        // Criar chave de idempotência para evitar duplicação
        $idempotency_key = uniqid() . '-' . time();
        
        // Configurar cURL
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.mercadopago.com/v1/payments',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($api_data),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->config['access_token'],
                'Content-Type: application/json',
                'X-Idempotency-Key: ' . $idempotency_key
            ],
        ]);
        
        // Executar a requisição
        $response = curl_exec($curl);
        $err = curl_error($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        // Tratar erros de conexão
        if ($err) {
            error_log('Erro na requisição do Mercado Pago: ' . $err);
            return [
                'success' => false,
                'message' => 'Erro na comunicação com o gateway de pagamento: ' . $err
            ];
        }
        
        // Decodificar resposta
        $response_data = json_decode($response, true);
        
        // Verificar código de resposta HTTP
        if ($http_code < 200 || $http_code >= 300) {
            $error_message = isset($response_data['message']) ? $response_data['message'] : 'Erro desconhecido';
            error_log('Erro na resposta do Mercado Pago: ' . $error_message);
            return [
                'success' => false,
                'message' => 'Erro no processamento do pagamento: ' . $error_message
            ];
        }
        
        // Verificar status do pagamento
        $status = isset($response_data['status']) ? $response_data['status'] : '';
        
        if ($status === 'approved') {
            return [
                'success' => true,
                'message' => 'Pagamento aprovado com sucesso!',
                'payment_id' => $response_data['id'],
                'status' => $status
            ];
        } elseif ($status === 'in_process' || $status === 'pending') {
            return [
                'success' => true,
                'message' => 'Pagamento em processamento. Você receberá uma confirmação em breve.',
                'payment_id' => $response_data['id'],
                'status' => $status
            ];
        } else {
            $status_detail = isset($response_data['status_detail']) ? $response_data['status_detail'] : 'unknown';
            error_log('Pagamento rejeitado pelo Mercado Pago: ' . $status_detail);
            return [
                'success' => false,
                'message' => 'Pagamento não aprovado. Motivo: ' . $this->get_status_message($status, $status_detail),
                'payment_id' => $response_data['id'],
                'status' => $status
            ];
        }
    }
    
    /**
     * Obtém mensagem amigável para o status de pagamento
     */
    private function get_status_message($status, $detail) {
        $messages = [
            'rejected' => [
                'cc_rejected_bad_filled_card_number' => 'Verifique o número do cartão.',
                'cc_rejected_bad_filled_date' => 'Verifique a data de validade.',
                'cc_rejected_bad_filled_other' => 'Verifique os dados do cartão.',
                'cc_rejected_bad_filled_security_code' => 'Verifique o código de segurança.',
                'cc_rejected_blacklist' => 'Não foi possível processar seu pagamento.',
                'cc_rejected_call_for_authorize' => 'Você deve autorizar o pagamento com seu banco.',
                'cc_rejected_card_disabled' => 'Ative seu cartão ou use outro meio de pagamento.',
                'cc_rejected_duplicated_payment' => 'Você já efetuou um pagamento com esse valor.',
                'cc_rejected_high_risk' => 'Seu pagamento foi recusado.',
                'cc_rejected_insufficient_amount' => 'Seu cartão possui saldo insuficiente.',
                'cc_rejected_invalid_installments' => 'Seu cartão não aceita esse número de parcelas.',
                'cc_rejected_max_attempts' => 'Você atingiu o limite de tentativas. Use outro cartão.',
                'default' => 'Não foi possível processar seu pagamento.'
            ]
        ];
        
        if (isset($messages[$status][$detail])) {
            return $messages[$status][$detail];
        } elseif (isset($messages[$status]['default'])) {
            return $messages[$status]['default'];
        } else {
            return 'Não foi possível processar seu pagamento. Tente novamente mais tarde.';
        }
    }
}

// Inicializar o processador de pagamento
function init_mercadopago_payment_processor() {
    // Carregar a instância da classe
    Socasa_MercadoPago_Payment::get_instance();
    
    // Adicionar função para lidar com pagamentos via Mercado Pago
    add_filter('socasa_payment_methods', 'register_mercadopago_payment_method');
}

/**
 * Registra o método de pagamento do Mercado Pago
 */
function register_mercadopago_payment_method($methods) {
    $methods['mercadopago'] = [
        'name' => 'Mercado Pago',
        'description' => 'Pague com cartão de crédito, débito ou saldo do Mercado Pago',
        'processor' => 'socasa_process_payment_method_mercadopago'
    ];
    
    return $methods;
}

// Inicializar o processador
init_mercadopago_payment_processor(); 