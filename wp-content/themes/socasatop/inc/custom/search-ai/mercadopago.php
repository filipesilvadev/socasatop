<?php
class MP_Payment {
    private $config;

    public function __construct() {
        $this->config = [
            'sandbox' => true,
            'public_key' => 'TEST-70b46d06-add9-499a-942e-0f5c01b8769a',
            'access_token' => 'TEST-1105123470040162-010319-784660b8cba90a127251b50a9e066db6-242756635'
        ];
    }

    public function process_payment($payment_data, $properties) {
      $curl = curl_init();
      
      $idempotency_key = uniqid() . '-' . time();
      
      $payload = [
          'transaction_amount' => $payment_data['transaction_amount'],
          'token' => $payment_data['token'],
          'description' => 'Patrocínio de Imóveis',
          'installments' => $payment_data['installments'],
          'payment_method_id' => $payment_data['payment_method_id'],
          'issuer_id' => $payment_data['issuer_id'],
          'payer' => [
              'email' => $payment_data['payer']['email'],
              'identification' => [
                  'type' => $payment_data['payer']['identification']['type'],
                  'number' => $payment_data['payer']['identification']['number']
              ]
          ]
      ];
  
      curl_setopt_array($curl, [
          CURLOPT_URL => 'https://api.mercadopago.com/v1/payments',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS => json_encode($payload),
          CURLOPT_HTTPHEADER => [
              'Authorization: Bearer ' . $this->config['access_token'],
              'Content-Type: application/json',
              'X-Idempotency-Key: ' . $idempotency_key
          ],
      ]);
  
      $response = curl_exec($curl);
      $err = curl_error($curl);
      curl_close($curl);
  
      if ($err) {
          throw new Exception('Erro ao processar pagamento: ' . $err);
      }
  
      error_log('Mercado Pago Response: ' . $response);
      return json_decode($response, true);
  }
  
    public function register_sponsorship($payment_id, $properties) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sponsored_listings';
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d', strtotime('+30 days'));

        foreach ($properties as $property_id) {
            $wpdb->insert(
                $table_name,
                [
                    'property_id' => $property_id,
                    'payment_id' => $payment_id,
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'status' => 'active'
                ],
                ['%d', '%s', '%s', '%s', '%s']
            );
            update_post_meta($property_id, 'is_sponsored', 'yes');
        }
    }
}