<?php
if (!defined('ABSPATH')) {
    exit;
}

class Smart_Search_API {
    private $required_fields = [
        'location' => 'localização',
        'type' => 'tipo do imóvel',
        'transaction' => 'tipo de transação (compra/aluguel)'
    ];

    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes() {
      register_rest_route('smart-search/v1', '/search', [
          'methods' => ['GET', 'POST'],
          'callback' => [$this, 'handle_search'],
          'permission_callback' => '__return_true',
      ]);
  }

  private function validate_search_terms($search_text) {
    $normalized_text = mb_strtolower($search_text);
    $validation = [
        'location' => false,
        'type' => false,
        'transaction' => false,
        'missing' => []
    ];

    $locations = ['asa norte', 'asa sul', 'noroeste', 'sudoeste', 'octogonal', 'cruzeiro', 'lago norte', 'lago sul', 'vicente pires', 'águas claras', 'taguatinga', 'guará', 'ceilândia', 'samambaia', 'recanto das emas', 'riacho fundo', 'riacho fundo ii', 'núcleo bandeirante', 'candangolândia', 'park way', 'parkway', 'brasília', 'paranoá', 'itapoã', 'varjão', 'sobradinho', 'sobradinho ii', 'planaltina', 'santa maria', 'gama', 'brazlândia', 'estrutural', 'jardim botânico', 'são sebastião', 'fercal', 'sol nascente', 'pôr do sol'];
    $validation['location'] = $this->checkTermPresence($normalized_text, $locations);

    $types = ['casa', 'apartamento', 'terreno', 'lote', 'sobrado', 'térrea', 'terrea'];
    $validation['type'] = $this->checkTermPresence($normalized_text, $types);

    $transactions = ['compra', 'comprar', 'venda', 'vender', 'aluguel', 'alugar'];
    $validation['transaction'] = $this->checkTermPresence($normalized_text, $transactions);

    foreach ($validation as $field => $value) {
        if ($field !== 'missing' && !$value) {
            $validation['missing'][] = $this->required_fields[$field];
        }
    }

    return $validation;
}

private function checkTermPresence($text, $terms) {
    foreach ($terms as $term) {
        if (mb_strpos($text, $term) !== false) {
            return true;
        }
    }
    return false;
}

    public function handle_search($request) {
        try {
          $search_text = sanitize_text_field($request->get_param('search'));
          if (empty($search_text)) {
              return new WP_Error('no_search_term', 'Por favor, informe o que você procura', ['status' => 400]);
          }

            error_log('Termo de busca recebido: ' . $search_text);

            $validation = $this->validate_search_terms($search_text);
            if (!empty($validation['missing'])) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Termos obrigatórios ausentes: ' . implode(', ', $validation['missing'])
                ], 400);
            }

            require_once __DIR__ . '/post.php';
            $search_ai = new Search_AI();
            $result = $search_ai->search_properties($search_text);
            error_log('SQL Debug - Resultado da busca: ' . print_r($result, true));
            
            if (empty($result)) {
                return new WP_REST_Response([], 200);
            }
            
            error_log('Resultado da busca: ' . print_r($result, true));
            return new WP_REST_Response($result, 200);

        } catch (Exception $e) {
            error_log('Erro na API: ' . $e->getMessage());
            return new WP_Error('search_error', $e->getMessage(), ['status' => 500]);
        }
    }

    public function handle_payment($request) {
        try {
            $body = json_decode($request->get_body(), true);
            $formData = $body['formData'];
            $properties = $body['properties'];
            
            error_log('Payment Request Data: ' . print_r($body, true));
            
            require_once __DIR__ . '/mercadopago.php';
            $mp = new MP_Payment();
            
            $payment_result = $mp->process_payment($formData, $properties);
            error_log('Payment Result: ' . print_r($payment_result, true));
            
            if (isset($payment_result['status']) && $payment_result['status'] === 'approved') {
                $mp->register_sponsorship($payment_result['id'], $properties);
                return new WP_REST_Response([
                    'success' => true,
                    'message' => 'Pagamento aprovado'
                ], 200);
            }
            
            return new WP_REST_Response([
                'success' => false,
                'error' => isset($payment_result['status_detail']) ? $payment_result['status_detail'] : 'Erro ao processar pagamento'
            ], 400);
            
        } catch (Exception $e) {
            error_log('Payment Error: ' . $e->getMessage());
            return new WP_REST_Response([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

new Smart_Search_API();