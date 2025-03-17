<?php
/**
 * Gerenciamento de Produtos e Assinaturas
 * 
 * Este arquivo gerencia os diferentes produtos de assinatura disponíveis no sistema.
 * Atualizado para usar uma estrutura modular de processadores de pagamento.
 */

// Impedir acesso direto ao arquivo
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!class_exists('Socasa_Products')) {

    class Socasa_Products {
        // Lista de produtos disponíveis no sistema
        private $products = [];
        
        // Lista de métodos de pagamento disponíveis
        private $payment_methods = [];
        
        /**
         * Construtor: define os produtos padrão do sistema
         */
        public function __construct() {
            $this->register_default_products();
            $this->init_hooks();
        }
        
        /**
         * Inicializa hooks necessários
         */
        private function init_hooks() {
            // Permitir que outros plugins/temas registrem métodos de pagamento
            $this->payment_methods = apply_filters('socasa_payment_methods', $this->payment_methods);
        }
        
        /**
         * Registra os produtos padrão do sistema
         */
        private function register_default_products() {
            // Produto: Destaque de Imóvel
            $this->register_product([
                'id' => 'highlight',
                'name' => 'Destaque de Imóvel',
                'description' => 'Coloque seu imóvel em destaque e aumente a visibilidade',
                'price' => 49.90,
                'recurrence' => 'monthly',
                'features' => [
                    'Imóvel aparece no topo das buscas',
                    'Aparece no carrossel de destaques',
                    'Selo de "Destaque" na listagem'
                ],
                'callback' => 'process_highlight_payment'
            ]);
            
            // Produto: Publicação Básica
            $this->register_product([
                'id' => 'basic_publication',
                'name' => 'Publicação Básica',
                'description' => 'Publicação padrão do seu imóvel no site',
                'price' => 19.90,
                'recurrence' => 'monthly',
                'features' => [
                    'Publicação do imóvel por 30 dias',
                    'Até 10 fotos',
                    'Contato direto com interessados'
                ],
                'callback' => 'process_publication_payment'
            ]);
            
            // Adicionar mais produtos conforme necessário...
            
            // Aplicar filtro para permitir que plugins/temas adicionem produtos
            $this->products = apply_filters('socasa_payment_products', $this->products);
        }
        
        /**
         * Registra um novo produto
         */
        public function register_product($product) {
            // Validar campos obrigatórios
            if (!isset($product['id']) || !isset($product['name']) || !isset($product['price'])) {
                return false;
            }
            
            // Padronizar propriedades
            $product = wp_parse_args($product, [
                'description' => '',
                'recurrence' => 'monthly',
                'features' => [],
                'callback' => '',
                'meta' => [],
                'payment_method' => 'default'
            ]);
            
            $this->products[$product['id']] = $product;
            return true;
        }
        
        /**
         * Obtém um produto pelo ID
         */
        public function get_product($product_id) {
            return isset($this->products[$product_id]) ? $this->products[$product_id] : null;
        }
        
        /**
         * Obtém todos os produtos
         */
        public function get_products() {
            return $this->products;
        }
        
        /**
         * Obtém todos os métodos de pagamento disponíveis
         */
        public function get_payment_methods() {
            return $this->payment_methods;
        }
        
        /**
         * Processa um pagamento para um produto específico
         */
        public function process_product_payment($product_id, $entity_id, $payment_data) {
            $product = $this->get_product($product_id);
            
            if (!$product) {
                return [
                    'success' => false,
                    'message' => 'Produto não encontrado'
                ];
            }
            
            // Determinar o processador de pagamento a ser usado
            // Por padrão, usaremos 'mercadopago', mas isso pode ser configurado por produto
            $payment_processor = isset($product['payment_method']) ? $product['payment_method'] : 'mercadopago';
            
            // Resultado inicial do processamento
            $result = [
                'success' => false,
                'message' => 'Nenhum processador de pagamento disponível.'
            ];
            
            // Tentar processar o pagamento com o processador apropriado
            $processor_filter = 'socasa_process_payment_method_' . $payment_processor;
            if (has_filter($processor_filter)) {
                $result = apply_filters($processor_filter, $result, $payment_data, $product);
            }
            
            // Se o pagamento foi bem-sucedido, executar o callback específico do produto
            if ($result['success']) {
                if (!empty($product['callback']) && function_exists($product['callback'])) {
                    // Passar os dados do resultado do pagamento para o callback
                    $callback_result = call_user_func($product['callback'], $entity_id, array_merge($payment_data, $result));
                    
                    // Mesclar os resultados, mantendo os dados do pagamento
                    if (is_array($callback_result)) {
                        $result = array_merge($result, $callback_result);
                    }
                } else {
                    // Usar o processador padrão se não houver callback específico
                    $default_result = $this->default_payment_processor($product, $entity_id, array_merge($payment_data, $result));
                    
                    // Mesclar os resultados, mantendo os dados do pagamento
                    if (is_array($default_result)) {
                        $result = array_merge($result, $default_result);
                    }
                }
            }
            
            return $result;
        }
        
        /**
         * Processador padrão de pagamentos
         */
        private function default_payment_processor($product, $entity_id, $payment_data) {
            // Implementação básica - pode ser expandida conforme necessário
            update_post_meta($entity_id, 'product_' . $product['id'] . '_active', 'yes');
            update_post_meta($entity_id, 'product_' . $product['id'] . '_expiry', date('Y-m-d H:i:s', strtotime('+30 days')));
            
            // Salvar o ID do pagamento, se disponível
            if (!empty($payment_data['payment_id'])) {
                update_post_meta($entity_id, 'product_' . $product['id'] . '_payment_id', $payment_data['payment_id']);
            }
            
            return [
                'success' => true,
                'message' => 'Pagamento processado com sucesso',
                'product' => $product,
                'entity_id' => $entity_id
            ];
        }
    }
    
    // Instanciar a classe para disponibilizar globalmente
    global $socasa_products;
    $socasa_products = new Socasa_Products();
    
    /**
     * Função auxiliar para obter um produto
     */
    function socasa_get_product($product_id) {
        global $socasa_products;
        return $socasa_products->get_product($product_id);
    }
    
    /**
     * Função auxiliar para obter todos os produtos
     */
    function socasa_get_products() {
        global $socasa_products;
        return $socasa_products->get_products();
    }
    
    /**
     * Função auxiliar para obter métodos de pagamento
     */
    function socasa_get_payment_methods() {
        global $socasa_products;
        return $socasa_products->get_payment_methods();
    }
    
    /**
     * Processador específico para pagamento de destaque
     */
    function process_highlight_payment($immobile_id, $payment_data) {
        // Marcar o imóvel como destacado
        update_post_meta($immobile_id, 'is_sponsored', 'yes');
        
        // Registrar na tabela de assinaturas
        global $wpdb;
        $trial_end = date('Y-m-d H:i:s', strtotime('+30 days'));
        $next_billing = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        $wpdb->insert(
            $wpdb->prefix . 'immobile_subscriptions',
            array(
                'immobile_id' => $immobile_id,
                'broker_id' => get_current_user_id(),
                'payment_id' => isset($payment_data['payment_id']) ? $payment_data['payment_id'] : '',
                'subscription_status' => 'active',
                'trial_ends_at' => $trial_end,
                'next_billing_date' => $next_billing
            )
        );
        
        return [
            'success' => true,
            'message' => 'Imóvel destacado com sucesso!'
        ];
    }
    
    /**
     * Processador específico para pagamento de publicação
     */
    function process_publication_payment($immobile_id, $payment_data) {
        $immobile = get_post($immobile_id);
        
        if ($immobile) {
            // Verificar configurações para ver se aprovação automática está habilitada
            $require_approval = get_option('immobile_requires_approval', 'yes') === 'yes';
            
            if ($require_approval) {
                wp_update_post([
                    'ID' => $immobile_id,
                    'post_status' => 'pending'
                ]);
            } else {
                wp_update_post([
                    'ID' => $immobile_id,
                    'post_status' => 'publish'
                ]);
            }
            
            // Salvar informações de pagamento
            update_post_meta($immobile_id, 'payment_id', isset($payment_data['payment_id']) ? $payment_data['payment_id'] : '');
            update_post_meta($immobile_id, 'payment_date', current_time('mysql'));
        }
        
        return [
            'success' => true,
            'message' => 'Publicação do imóvel processada com sucesso!'
        ];
    }
} 