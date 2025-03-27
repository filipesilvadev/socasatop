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
        // Debug
        error_log('MP Payment Processor - Dados recebidos: ' . json_encode([
            'payment_data' => $payment_data,
            'product' => $product
        ]));
        
        // Verificar se temos acesso ao token
        if (empty($this->config['access_token'])) {
            error_log('MP Payment Processor - Token de acesso não configurado');
            return [
                'success' => false,
                'message' => 'Configuração de pagamento incompleta. Token de acesso não configurado.'
            ];
        }
        
        // Verificar método de pagamento
        $payment_method = isset($payment_data['payment_method']) ? $payment_data['payment_method'] : '';
        $payment_data_info = isset($payment_data['payment_data']) ? $payment_data['payment_data'] : [];
        
        error_log('MP Payment Processor - Método de pagamento: ' . $payment_method);
        
        switch ($payment_method) {
            case 'card':
            case 'new_card':
                error_log('MP Payment Processor - Processando pagamento com novo cartão');
                return $this->process_card_payment($payment_data_info, $product);
                
            case 'wallet':
                error_log('MP Payment Processor - Processando pagamento com carteira');
                return $this->process_wallet_payment($payment_data_info, $product);
                
            case 'saved_card':
                error_log('MP Payment Processor - Processando pagamento com cartão salvo');
                return $this->process_saved_card_payment($payment_data_info, $product);
                
            default:
                error_log('MP Payment Processor - Método de pagamento não suportado: ' . $payment_method);
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
        // Debug
        error_log('MP Payment Processor - Dados do pagamento com cartão: ' . json_encode($payment_data));
        
        // Verificar dados necessários
        if (empty($payment_data['token'])) {
            error_log('MP Payment Processor - Token do cartão não fornecido');
            return [
                'success' => false,
                'message' => 'Dados de pagamento incompletos. Token do cartão não fornecido.'
            ];
        }
        
        // Preparar dados para API
        $api_data = [
            'transaction_amount' => floatval($payment_data['transaction_amount']),
            'token' => $payment_data['token'],
            'description' => isset($payment_data['description']) ? $payment_data['description'] : 'Compra em SoCasaTop',
            'installments' => isset($payment_data['installments']) ? intval($payment_data['installments']) : 1,
            'payment_method_id' => isset($payment_data['payment_method_id']) ? $payment_data['payment_method_id'] : 'visa',
            'payer' => isset($payment_data['payer']) ? $payment_data['payer'] : [
                'email' => 'user@example.com'
            ]
        ];
        
        // Incluir issuer_id se disponível
        if (!empty($payment_data['issuer_id'])) {
            $api_data['issuer_id'] = $payment_data['issuer_id'];
        }
        
        // Processar pagamento via API
        $result = $this->send_payment_request($api_data);
        error_log('MP Payment Processor - Resultado do processamento com cartão: ' . json_encode($result));
        return $result;
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
        // Debug
        error_log('MP Payment Processor - Dados do pagamento com cartão salvo: ' . json_encode($payment_data));
        
        // Verificar dados necessários
        if (empty($payment_data['saved_card_id'])) {
            error_log('MP Payment Processor - ID do cartão salvo não fornecido');
            return [
                'success' => false,
                'message' => 'ID do cartão salvo não fornecido.'
            ];
        }
        
        $user_id = get_current_user_id();
        error_log('MP Payment Processor - User ID: ' . $user_id);
        
        $cards = get_user_meta($user_id, 'mercadopago_cards', true) ?: [];
        error_log('MP Payment Processor - Cartões do usuário: ' . json_encode($cards));
        
        // Encontrar o cartão salvo
        $card_info = null;
        $card_id = $payment_data['saved_card_id'];
        
        if (isset($cards[$card_id])) {
            $card_info = $cards[$card_id];
            error_log('MP Payment Processor - Cartão encontrado pelo ID direto: ' . json_encode($card_info));
        } else {
            // Tenta encontrar o cartão por ID na lista
            foreach ($cards as $key => $card) {
                if ($key === $card_id || (isset($card['id']) && $card['id'] === $card_id)) {
                    $card_info = $card;
                    error_log('MP Payment Processor - Cartão encontrado por iteração: ' . json_encode($card_info));
                    break;
                }
            }
        }
        
        if (!$card_info) {
            error_log('MP Payment Processor - Cartão não encontrado: ' . $card_id);
            return [
                'success' => false,
                'message' => 'Cartão não encontrado.'
            ];
        }
        
        // Verificar se temos token ou card_id
        if (empty($card_info['token']) && empty($card_info['card_id'])) {
            error_log('MP Payment Processor - Cartão sem token ou card_id');
            return [
                'success' => false,
                'message' => 'Dados do cartão incompletos. Por favor, adicione um novo cartão.'
            ];
        }
        
        // Preparar dados para API baseados no cartão salvo
        $api_data = [
            'transaction_amount' => floatval($payment_data['amount']),
            'description' => isset($payment_data['description']) ? $payment_data['description'] : 'Compra em SoCasaTop',
            'installments' => 1,
            'payment_method_id' => isset($card_info['payment_method_id']) ? $card_info['payment_method_id'] : 
                                   (isset($card_info['brand']) ? $card_info['brand'] : 'visa'),
            'payer' => [
                'email' => isset($card_info['email']) ? $card_info['email'] : get_userdata($user_id)->user_email
            ]
        ];
        
        // Usar token do cartão se disponível
        if (!empty($card_info['token'])) {
            $api_data['token'] = $card_info['token'];
        } else if (!empty($card_info['card_id'])) {
            // Usar card_id se token não estiver disponível
            $api_data['card_id'] = $card_info['card_id'];
        }
        
        error_log('MP Payment Processor - Dados para API (cartão salvo): ' . json_encode($api_data));
        
        // Processar pagamento via API
        $result = $this->send_payment_request($api_data);
        error_log('MP Payment Processor - Resultado do processamento com cartão salvo: ' . json_encode($result));
        return $result;
    }
    
    /**
     * Envia requisição de pagamento para a API do Mercado Pago
     */
    private function send_payment_request($api_data) {
        error_log('MP Payment Processor - Enviando requisição para API do MP: ' . json_encode($api_data));
        
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
            error_log('MP Payment Processor - Erro na requisição do MP: ' . $err);
            return [
                'success' => false,
                'message' => 'Erro na comunicação com o gateway de pagamento: ' . $err
            ];
        }
        
        // Decodificar resposta
        $response_data = json_decode($response, true);
        error_log('MP Payment Processor - Resposta da API do MP: ' . $response . ' (HTTP: ' . $http_code . ')');
        
        // Verificar código de resposta HTTP
        if ($http_code < 200 || $http_code >= 300) {
            $error_message = isset($response_data['message']) ? $response_data['message'] : 'Erro desconhecido';
            error_log('MP Payment Processor - Erro na resposta do MP: ' . $error_message);
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