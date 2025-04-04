<?php
class Immobile_Payment {
    private $config;
    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->config = [
            'sandbox' => true,
            'public_key' => 'TEST-70b46d06-add9-499a-942e-0f5c01b8769a',
            'access_token' => 'TEST-1105123470040162-010319-784660b8cba90a127251b50a9e066db6-242756635'
        ];
    }

    public function save_card_data($payment_data) {
        $trial_end = date('Y-m-d H:i:s', strtotime('+30 days'));
        $next_billing = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        foreach ($payment_data['immobile_list'] as $immobile) {
            $this->wpdb->insert(
                $this->wpdb->prefix . 'immobile_subscriptions',
                array(
                    'immobile_id' => $immobile['id'],
                    'broker_id' => get_current_user_id(),
                    'card_token' => $payment_data['token'],
                    'trial_ends_at' => $trial_end,
                    'next_billing_date' => $next_billing
                )
            );
        }

        return ['success' => true, 'message' => 'Cartão registrado com sucesso'];
    }

    public function process_subscription_payments() {
        $subscriptions = $this->wpdb->get_results("
            SELECT * FROM {$this->wpdb->prefix}immobile_subscriptions 
            WHERE subscription_status = 'active' 
            AND next_billing_date <= NOW()
        ");

        foreach ($subscriptions as $subscription) {
            $this->process_payment([
                'token' => $subscription->card_token,
                'transaction_amount' => 15.00,
                'installments' => 1,
                'description' => 'Renovação de Anúncio - Imóvel #' . $subscription->immobile_id,
                'payer' => [
                    'email' => wp_get_current_user()->user_email
                ]
            ]);

            $this->wpdb->update(
                $this->wpdb->prefix . 'immobile_subscriptions',
                ['next_billing_date' => date('Y-m-d H:i:s', strtotime('+30 days'))],
                ['id' => $subscription->id]
            );
        }
    }

    public function send_renewal_notifications() {
        $dates = [5, 3, 2, 1];
        
        foreach ($dates as $days) {
            $future_date = date('Y-m-d', strtotime("+$days days"));
            
            $subscriptions = $this->wpdb->get_results($this->wpdb->prepare("
                SELECT * FROM {$this->wpdb->prefix}immobile_subscriptions 
                WHERE DATE(next_billing_date) = %s
                AND subscription_status = 'active'
            ", $future_date));

            foreach ($subscriptions as $subscription) {
                $user = get_user_by('id', $subscription->broker_id);
                $immobile = get_post($subscription->immobile_id);
                
                $message = "Olá! Seu anúncio '{$immobile->post_title}' será renovado em $days dias. 
                           O valor de R$ 15,00 será debitado automaticamente do seu cartão cadastrado.";
                
                wp_mail(
                    $user->user_email,
                    "Renovação em $days dias - {$immobile->post_title}",
                    $message
                );
            }
        }
    }

    public function process_payment($payment_data) {
        $curl = curl_init();
        
        $idempotency_key = uniqid() . '-' . time();
        
        $payload = [
            'transaction_amount' => floatval($payment_data['transaction_amount']),
            'token' => $payment_data['token'],
            'description' => $payment_data['description'],
            'installments' => (int)$payment_data['installments'],
            'payment_method_id' => $payment_data['payment_method_id'],
            'payer' => [
                'email' => $payment_data['payer']['email']
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
    
        return json_decode($response, true);
    }
}