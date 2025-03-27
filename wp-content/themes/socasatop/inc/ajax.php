<?php
function delete_post_ajax_handler()
{
    if (!current_user_can('administrator')) {
        wp_send_json_error('You do not have permission to delete this location.', 401);
        wp_die();
    }

    check_ajax_referer('ajax_nonce', 'nonce');

    $post_id = (int) isset($request['id']) ? $_POST['id'] : $_POST['queried_id'];

    if ($post_id <= 0) {
        wp_send_json_error('ID inválido.', 400);
        wp_die();
    }

    if (wp_delete_post($post_id, true)) {
        wp_send_json_success('Deletado com sucesso', 200);
    } else {
        wp_send_json_error('Erro ao deletar', 500);
    }
    wp_die();
}

add_action('wp_ajax_delete_post', 'delete_post_ajax_handler');

function delete_broker_ajax_handler()
{
    if (!current_user_can('administrator')) {
        wp_send_json_error('You do not have permission to delete this location.', 401);
        wp_die();
    }

    check_ajax_referer('ajax_nonce', 'nonce');

    if (isset($_POST['user_id'])) {
        $user_id = intval($_POST['user_id']);
    }

    if ($user_id <= 0) {
        wp_send_json_error('ID inválido.', 400);
        wp_die();
    }

    if (wp_delete_user($user_id)) {
        wp_send_json_success('Deletado com sucesso', 200);
    } else {
        wp_send_json_error('Erro ao deletar', 500);
    }
    wp_die();
}

add_action('wp_ajax_delete_broker', 'delete_broker_ajax_handler');

//# Broker Endpoints
function sanitize_string($string)
{
    $string = strtr(
        $string,
        'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýþÿ',
        'AAAAAAACEEEEIIIIDNOOOOOOUUUUYbsaaaaaaaceeeeiiiidnoooooouuuuyby'
    );

    $string = strtolower($string);

    $string = str_replace(' ', '', $string);

    return $string;
}
function create_broker_ajax_handler()
{
    if (!current_user_can('administrator')) {
        wp_send_json_error('You do not have permission this in location.', 401);
        wp_die();
    }

    check_ajax_referer('ajax_nonce', 'nonce');

    $username = sanitize_string($_POST['name']);
    $email = $_POST['email'];

    if (username_exists($username) || email_exists($email)) {
        wp_send_json_error(array('message' => 'Username or email already exists.'));
    } else {
        $user_id = wp_create_user($username, $_POST['password'], $email);

        if (is_wp_error($user_id)) {
            wp_send_json_error('Erro ao cadastrar corretor: ' . $user_id->get_error_message(), 500);
        } else {
            $user_data = array(
                'ID' => $user_id,
                'first_name' => $_POST['name'],
                'role' => 'author'
            );
            $user_id = wp_update_user($user_data);
            update_user_meta($user_id, 'phone', sanitize_text_field($_POST['phone']));
            wp_send_json_success('Corretor', 200);
        }
    }
}
add_action('wp_ajax_create_broker', 'create_broker_ajax_handler');

function update_broker_ajax_handler()
{
    if (!current_user_can('administrator')) {
        wp_send_json_error('You do not have permission this in location.', 401);
        wp_die();
    }

    check_ajax_referer('ajax_nonce', 'nonce');

    $user_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

    if ($user_id <= 0) {
        wp_send_json_error('ID do corretor inválido.', 400);
        wp_die();
    }

    $user_data = array(
        'ID' => $user_id,
        'display_name' => $_POST['name'],
        'user_email' => $_POST['email'],
    );

    if (isset($_POST['password']) && !empty($_POST['password'])) {
        $user_data['user_pass'] = $_POST['password'];
    }

    $user_result = wp_update_user($user_data);

    if (is_wp_error($user_result)) {
        wp_send_json_error('Erro ao atualizar o corretor: ' . $user_result->get_error_message(), 500);
        wp_die();
    }
    update_user_meta($user_id, 'phone', sanitize_text_field($_POST['phone']));

    wp_send_json_success('Corretor atualizado com sucesso.', 200);
    wp_die();
}
add_action('wp_ajax_update_broker', 'update_broker_ajax_handler');

//# Immobile Endpoints
function create_immobile_ajax_handler()
{
    if (!current_user_can('administrator') && !current_user_can('author')) {
        wp_send_json_error('You do not have permission in this location.', 401);
        wp_die();
    }

    check_ajax_referer('ajax_nonce', 'nonce');

    $post_id = wp_insert_post(array(
        'post_type' => 'immobile',
        'post_title' => sanitize_text_field($_POST['title']),
        'post_status' => 'publish'
    ));


    if (is_wp_error($post_id)) {
        wp_send_json_error('Erro ao cadastrar imóvel: ' . $post_id->get_error_message(), 500);
    } else {
        wp_send_json_success('Imóvel', 200);
    }

    wp_die();
}
add_action('wp_ajax_create_immobile', 'create_immobile_ajax_handler');

function update_immobile_ajax_handler()
{
    check_ajax_referer('ajax_nonce', 'nonce');

    $post_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

    if ($post_id <= 0) {
        wp_send_json_error('ID do imóvel inválido.', 400);
        wp_die();
    }

    // Verificar se o post existe
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'immobile') {
        wp_send_json_error('Imóvel não encontrado.', 404);
        wp_die();
    }

    // Verificar se o usuário tem permissão para editar este imóvel
    $current_user_id = get_current_user_id();
    if (!current_user_can('administrator') && $post->post_author != $current_user_id) {
        wp_send_json_error('Você não tem permissão para editar este imóvel.', 401);
        wp_die();
    }

    $post_data = array('ID' => $post_id);

    if (isset($_POST['title'])) {
        $post_data['post_title'] = sanitize_text_field($_POST['title']);
    }

    $update_result = wp_update_post($post_data, true);

    if (is_wp_error($update_result)) {
        wp_send_json_error('Erro ao atualizar o imóvel: ' . $update_result->get_error_message(), 500);
        wp_die();
    }

    // Atualizar metadados
    $meta_fields = [
        'facade', 'offer_type', 'amount', 'bedrooms', 'size',
        'property_type', 'condominium', 'financing', 'committee',
        'committee_socasatop', 'details', 'link', 'location'
    ];

    foreach ($meta_fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
        }
    }

    wp_send_json_success('Imóvel atualizado com sucesso.', 200);
    wp_die();
}
add_action('wp_ajax_update_immobile', 'update_immobile_ajax_handler');

//# Lead Endpoints
function create_lead_ajax_handler()
{
    if (!current_user_can('administrator')) {
        wp_send_json_error('You do not have permission in this location.', 401);
        wp_die();
    }

    check_ajax_referer('ajax_nonce', 'nonce');

    $post_id = wp_insert_post(array(
        'post_type' => 'lead',
        'post_title' => sanitize_text_field($_POST['title']),
        'post_status' => 'publish',
    ));

    if (is_wp_error($post_id)) {
        wp_send_json_error('Erro ao cadastrar lead: ' . $post_id->get_error_message(), 500);
    } else {
        wp_send_json_success('Lead', 200);
    }

    wp_die();
}
add_action('wp_ajax_create_lead', 'create_lead_ajax_handler');

function update_lead_ajax_handler()
{
    if (!current_user_can('administrator')) {
        wp_send_json_error('You do not have permission in this location.', 401);
        wp_die();
    }

    check_ajax_referer('ajax_nonce', 'nonce');

    $post_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

    if ($post_id <= 0) {
        wp_send_json_error('ID do imóvel inválido.', 400);
        wp_die();
    }

    $post_data = array('ID' => $post_id);

    if (isset($_POST['title'])) {
        $post_data['post_title'] = sanitize_text_field($_POST['title']);
    }

    $update_result = wp_update_post($post_data, true);

    if (is_wp_error($update_result)) {
        wp_send_json_error('Erro ao atualizar o lead: ' . $update_result->get_error_message(), 500);
        wp_die();
    }

    wp_send_json_success('Lead atualizado com sucesso.', 200);
    wp_die();
}
add_action('wp_ajax_update_lead', 'update_lead_ajax_handler');

//# Location Endpoints
function create_location_ajax_handler()
{
    if (!current_user_can('administrator')) {
        wp_send_json_error('You do not have permission in this location.', 401);
        wp_die();
    }

    check_ajax_referer('ajax_nonce', 'nonce');

    $location_name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';

    $term = wp_insert_term($location_name, 'locations');

    if (is_wp_error($term)) {
        wp_send_json_error('Erro ao cadastrar localização: ' . $term->get_error_message(), 500);
    } else {
        wp_send_json_success('Localização', 200);
    }

    wp_die();
}
add_action('wp_ajax_create_location', 'create_location_ajax_handler');

function delete_location_ajax_handler()
{
    if (!current_user_can('administrator')) {
        wp_send_json_error('You do not have permission in this location.', 401);
        wp_die();
    }

    check_ajax_referer('ajax_nonce', 'nonce');

    $term_id = isset($_POST['queried_id']) ? intval($_POST['queried_id']) : 0;

    if ($term_id <= 0) {
        wp_send_json_error('ID inválido.', 400);
        wp_die();
    }

    $deleted = wp_delete_term($term_id, 'locations');

    if ($deleted instanceof WP_Error) {
        wp_send_json_error('Erro ao deletar a localização: ' . $deleted->get_error_message(), 500);
    } elseif ($deleted === false) {
        wp_send_json_error('Erro ao deletar a localização.', 500);
    } else {
        wp_send_json_success('Localização deletada com sucesso.', 200);
    }

    wp_die();
}
add_action('wp_ajax_delete_location', 'delete_location_ajax_handler');

//# View Endpoints
function create_link_listaimoveis_ajax_handler()
{
    if (!current_user_can('administrator')) {
        wp_send_json_error('You do not have permission in this location.', 401);
        wp_die();
    }

    check_ajax_referer('ajax_nonce', 'nonce');

    $post_id = wp_insert_post(array(
        'post_type' => 'listaimoveis',
        'post_status' => 'publish',
        'post_title' => sanitize_text_field($_POST['name']),
        'meta_input' => array(
            'immobile_ids' => sanitize_text_field($_POST['immobile_ids']),
        ),
    ));

    if (is_wp_error($post_id)) {
        wp_send_json_error('Erro ao gerar link: ' . $post_id->get_error_message(), 500);
    } else {
        $link = get_permalink($post_id);
        wp_send_json_success(array('link' => $link), 200);
    }

    wp_die();
}
add_action('wp_ajax_create_link_listaimoveis', 'create_link_listaimoveis_ajax_handler');

//# Send Message
function send_message_broker_ajax_handler()
{

    $post_id = isset($_POST['post_id']) ? $_POST['post_id'] : 0;

    $views = intval(get_post_meta($post_id, 'views', true));
    if ($views > 0 || is_user_logged_in()) {
        wp_send_json_success(array('hasView' => $views), 200);
        wp_die();
    }

    $post_ids = explode(',', get_post_meta($post_id, 'immobile_ids', true));

    $contacts = [];
    foreach($post_ids as $post_id){
        $post = get_post($post_id);
        $broker = get_post_meta($post_id, 'broker', true);
        $user = get_userdata($broker);

        $contacts[] = [
            "name"  => $user->display_name,
            "email" => $user->user_email,
            "phone" => get_user_meta($broker, 'phone', true),
            "title" => $post->post_title
        ];
    }

    $contactsJson = json_encode($contacts);
    wp_send_json_success(array('json' => $contactsJson), 200);
    wp_die();
}

add_action('wp_ajax_send_message_broker', 'send_message_broker_ajax_handler');
add_action('wp_ajax_nopriv_send_message_broker', 'send_message_broker_ajax_handler');

function process_immobile_payment() {
  check_ajax_referer('ajax_nonce', 'nonce');
  
  require_once get_stylesheet_directory() . '/inc/custom/search-ai/mercadopago.php';
  
  $payment_data = $_POST['payment_data'];
  $immobile_list = $_POST['immobile_list'];
  
  try {
      $mp_payment = new MP_Payment();
      $payment_response = $mp_payment->process_payment($payment_data, []);
      
      if ($payment_response['status'] === 'approved') {
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
                  // Adicionar meta para indicar que precisa de aprovação
                  update_post_meta($post_id, 'needs_approval', 'yes');
                  
                  // Enviar notificação para o administrador sobre o novo imóvel
                  if (function_exists('send_admin_notification_for_approval')) {
                      send_admin_notification_for_approval($post_id);
                  }
              }
          }
          
          wp_send_json_success(['message' => 'Pagamento processado e imóveis salvos com sucesso']);
      } else {
          wp_send_json_error(['message' => 'Falha no processamento do pagamento']);
      }
  } catch (Exception $e) {
      wp_send_json_error(['message' => $e->getMessage()]);
  }
  
  wp_die();
}
add_action('wp_ajax_process_immobile_payment', 'process_immobile_payment');

/**
 * Pausa o destaque de um imóvel
 */
function pause_immobile_highlight() {
    // Verificar se o nonce é válido
    check_ajax_referer('broker_dashboard_nonce', 'nonce');
    
    // Verificar se o ID do imóvel foi fornecido
    if (!isset($_POST['property_id']) || empty($_POST['property_id'])) {
        wp_send_json_error('ID do imóvel não fornecido.');
        return;
    }
    
    $property_id = intval($_POST['property_id']);
    $user_id = get_current_user_id();
    
    // Verificar se o usuário tem permissão para pausar o destaque deste imóvel
    $broker_id = get_post_meta($property_id, 'broker', true);
    if ($broker_id != $user_id) {
        wp_send_json_error('Você não tem permissão para pausar o destaque deste imóvel.');
        return;
    }
    
    // Verificar se o imóvel está em destaque
    $is_sponsored = get_post_meta($property_id, 'is_sponsored', true);
    if ($is_sponsored !== 'yes') {
        wp_send_json_error('Este imóvel não está em destaque.');
        return;
    }
    
    // Atualizar o status de pausa
    update_post_meta($property_id, 'highlight_paused', 'yes');
    
    // Atualizar também a tabela broker_immobile, se necessário
    global $wpdb;
    $broker_immobile_table = $wpdb->prefix . 'broker_immobile';
    $wpdb->update(
        $broker_immobile_table,
        array('is_paused' => 1),
        array(
            'broker_id' => $user_id,
            'immobile_id' => $property_id
        )
    );
    
    // Registrar log da operação
    error_log("Destaque pausado para o imóvel ID: {$property_id} pelo usuário ID: {$user_id}");
    
    // Retornar sucesso
    wp_send_json_success('Destaque pausado com sucesso.');
}
add_action('wp_ajax_pause_immobile_highlight', 'pause_immobile_highlight');

/**
 * Reativa o destaque de um imóvel que foi pausado
 */
function reactivate_immobile_highlight() {
    // Verificar se o nonce é válido
    check_ajax_referer('broker_dashboard_nonce', 'nonce');
    
    // Verificar se o ID do imóvel foi fornecido
    if (!isset($_POST['property_id']) || empty($_POST['property_id'])) {
        wp_send_json_error('ID do imóvel não fornecido.');
        return;
    }
    
    $property_id = intval($_POST['property_id']);
    $user_id = get_current_user_id();
    
    // Verificar se o usuário tem permissão para reativar o destaque deste imóvel
    $broker_id = get_post_meta($property_id, 'broker', true);
    if ($broker_id != $user_id) {
        wp_send_json_error('Você não tem permissão para reativar o destaque deste imóvel.');
        return;
    }
    
    // Verificar se o imóvel está em destaque e pausado
    $is_sponsored = get_post_meta($property_id, 'is_sponsored', true);
    $is_paused = get_post_meta($property_id, 'highlight_paused', true);
    
    if ($is_sponsored !== 'yes') {
        wp_send_json_error('Este imóvel não está em destaque.');
        return;
    }
    
    if ($is_paused !== 'yes') {
        wp_send_json_error('Este destaque não está pausado.');
        return;
    }
    
    // Atualizar o status de pausa
    update_post_meta($property_id, 'highlight_paused', 'no');
    
    // Atualizar também a tabela broker_immobile, se necessário
    global $wpdb;
    $broker_immobile_table = $wpdb->prefix . 'broker_immobile';
    $wpdb->update(
        $broker_immobile_table,
        array('is_paused' => 0),
        array(
            'broker_id' => $user_id,
            'immobile_id' => $property_id
        )
    );
    
    // Registrar log da operação
    error_log("Destaque reativado para o imóvel ID: {$property_id} pelo usuário ID: {$user_id}");
    
    // Retornar sucesso
    wp_send_json_success('Destaque reativado com sucesso.');
}
add_action('wp_ajax_reactivate_immobile_highlight', 'reactivate_immobile_highlight');

/**
 * Cancela uma assinatura no Mercado Pago
 */
function cancel_mercadopago_subscription($subscription_id) {
    // Obter configuração do Mercado Pago
    $mp_config = get_mercadopago_config();
    
    if (empty($mp_config) || empty($mp_config['access_token'])) {
        error_log('Token de acesso do Mercado Pago não configurado');
        return array(
            'success' => false,
            'message' => 'Token de acesso não configurado'
        );
    }
    
    // Configurar a requisição cURL para cancelar a assinatura
    $curl = curl_init();
    
    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.mercadopago.com/preapproval/{$subscription_id}",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_POSTFIELDS => json_encode(array('status' => 'cancelled')),
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer ' . $mp_config['access_token'],
            'Content-Type: application/json'
        ),
    ));
    
    $response = curl_exec($curl);
    $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    
    if (curl_errno($curl)) {
        error_log('Erro cURL ao cancelar assinatura: ' . curl_error($curl));
        curl_close($curl);
        return array(
            'success' => false,
            'message' => 'Erro na comunicação com o Mercado Pago: ' . curl_error($curl)
        );
    }
    
    curl_close($curl);
    
    $result = json_decode($response, true);
    error_log('Resposta do Mercado Pago ao cancelar assinatura: ' . print_r($result, true));
    
    // Verificar se o cancelamento foi bem-sucedido (código 200 ou 201)
    if ($http_status >= 200 && $http_status < 300) {
        return array(
            'success' => true,
            'message' => 'Assinatura cancelada com sucesso'
        );
    } else {
        $error_message = isset($result['message']) ? $result['message'] : 'Erro desconhecido';
        return array(
            'success' => false,
            'message' => 'Erro ao cancelar assinatura: ' . $error_message
        );
    }
}

/**
 * Excluir múltiplos imóveis de uma vez
 */
function bulk_delete_immobiles() {
    // Verificar nonce - aceitando os dois tipos de nonce para compatibilidade
    if (isset($_POST['nonce'])) {
        check_ajax_referer('ajax_nonce', 'nonce');
    } else if (isset($_POST['security'])) {
        check_ajax_referer('broker_dashboard_nonce', 'security');
    } else {
        wp_send_json_error('Erro de segurança: nonce inválido');
        return;
    }
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Usuário não autenticado');
    }
    
    $user = wp_get_current_user();
    if (!in_array('author', (array) $user->roles)) {
        wp_send_json_error('Acesso restrito a corretores');
    }
    
    $user_id = get_current_user_id();
    // Verificar os dois possíveis nomes de parâmetro (para compatibilidade)
    $property_ids = isset($_POST['property_ids']) ? $_POST['property_ids'] : 
                    (isset($_POST['immobile_ids']) ? $_POST['immobile_ids'] : array());
    
    if (empty($property_ids) || !is_array($property_ids)) {
        wp_send_json_error('Nenhum imóvel selecionado para exclusão');
    }
    
    $success_count = 0;
    $error_count = 0;
    $errors = array();
    
    foreach ($property_ids as $property_id) {
        $property_id = intval($property_id);
        
        if (empty($property_id)) {
            continue;
        }
        
        // Verificar se o imóvel pertence ao corretor
        $broker_id = get_post_meta($property_id, 'broker', true);
        if ($broker_id != $user_id) {
            $error_count++;
            $errors[] = "Imóvel #$property_id: Você não tem permissão para excluir este imóvel";
            continue;
        }
        
        // Verificar se o imóvel está destacado e tem assinatura
        $is_sponsored = get_post_meta($property_id, 'is_sponsored', true) === 'yes';
        if ($is_sponsored) {
            $subscription_id = get_post_meta($property_id, 'mercadopago_subscription_id', true);
            if (!empty($subscription_id)) {
                // Cancelar assinatura no Mercado Pago
                cancel_mercadopago_subscription($subscription_id);
            }
        }
        
        // Tentar excluir o imóvel
        $deleted = wp_delete_post($property_id, true);
        
        if ($deleted) {
            $success_count++;
        } else {
            $error_count++;
            $errors[] = "Imóvel #$property_id: Erro ao tentar excluir";
        }
    }
    
    // Registrar a ação nos logs
    $log_message = sprintf(
        'Exclusão em massa de imóveis pelo usuário #%d. Sucesso: %d, Falhas: %d',
        $user_id,
        $success_count,
        $error_count
    );
    error_log($log_message);
    
    if ($success_count > 0) {
        wp_send_json_success(array(
            'success_count' => $success_count,
            'error_count' => $error_count,
            'errors' => $errors
        ));
    } else {
        wp_send_json_error('Não foi possível excluir nenhum dos imóveis selecionados');
    }
}
add_action('wp_ajax_bulk_delete_immobiles', 'bulk_delete_immobiles');

/**
 * Excluir um imóvel permanentemente
 */
function delete_immobile() {
    // Verificar nonce - aceitando os dois tipos de nonce para compatibilidade
    if (isset($_POST['nonce'])) {
        check_ajax_referer('ajax_nonce', 'nonce');
    } else if (isset($_POST['security'])) {
        check_ajax_referer('broker_dashboard_nonce', 'security');
    } else {
        wp_send_json_error('Erro de segurança: nonce inválido');
        return;
    }
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Usuário não autenticado');
    }
    
    $user = wp_get_current_user();
    if (!in_array('author', (array) $user->roles)) {
        wp_send_json_error('Acesso restrito a corretores');
    }
    
    $user_id = get_current_user_id();
    // Verificar os dois possíveis nomes de parâmetro (para compatibilidade)
    $property_id = isset($_POST['property_id']) ? intval($_POST['property_id']) : 
                   (isset($_POST['immobile_id']) ? intval($_POST['immobile_id']) : 0);
    
    if (empty($property_id)) {
        wp_send_json_error('ID do imóvel não fornecido');
    }
    
    // Verificar se o imóvel pertence ao corretor
    $broker_id = get_post_meta($property_id, 'broker', true);
    if ($broker_id != $user_id) {
        wp_send_json_error('Você não tem permissão para excluir este imóvel');
    }
    
    // Verificar se o imóvel está destacado e tem assinatura
    $is_sponsored = get_post_meta($property_id, 'is_sponsored', true) === 'yes';
    if ($is_sponsored) {
        $subscription_id = get_post_meta($property_id, 'mercadopago_subscription_id', true);
        if (!empty($subscription_id)) {
            // Cancelar assinatura no Mercado Pago
            cancel_mercadopago_subscription($subscription_id);
        }
    }
    
    // Excluir o imóvel
    $deleted = wp_delete_post($property_id, true);
    
    if (!$deleted) {
        wp_send_json_error('Erro ao excluir o imóvel');
    }
    
    // Registrar a ação nos logs
    $log_message = sprintf(
        'Imóvel #%d excluído pelo usuário #%d',
        $property_id,
        $user_id
    );
    error_log($log_message);
    
    wp_send_json_success('Imóvel excluído com sucesso');
}
add_action('wp_ajax_delete_immobile', 'delete_immobile');

// Função para buscar métricas do corretor
if (!function_exists('get_broker_metrics')) {
    function get_broker_metrics() {
        error_log('Iniciando get_broker_metrics');
        
        check_ajax_referer('ajax_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            error_log('get_broker_metrics: Usuário não autenticado');
            wp_send_json_error('Usuário não autenticado');
        }

        $user = wp_get_current_user();
        error_log('get_broker_metrics: Usuário #' . $user->ID . ' com roles: ' . implode(', ', $user->roles));
        
        if (!in_array('author', (array) $user->roles) && !in_array('administrator', (array) $user->roles)) {
            error_log('get_broker_metrics: Usuário não tem permissão de corretor');
            wp_send_json_error('Acesso restrito a corretores');
        }

        $user_id = get_current_user_id();
        $last_30_days = array();
        
        for ($i = 0; $i < 30; $i++) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $views = get_user_meta($user_id, "metrics_views_{$date}", true) ?: 0;
            $clicks = get_user_meta($user_id, "metrics_clicks_{$date}", true) ?: 0;
            $conversions = get_user_meta($user_id, "metrics_conversions_{$date}", true) ?: 0;

            $last_30_days[] = array(
                'date' => $date,
                'views' => (int)$views,
                'clicks' => (int)$clicks,
                'conversions' => (int)$conversions
            );
        }

        error_log('get_broker_metrics: Retornando métricas para os últimos 30 dias');
        wp_send_json_success(array(
            'metrics' => array_reverse($last_30_days)
        ));
    }
}
add_action('wp_ajax_get_broker_metrics', 'get_broker_metrics');

// A função get_broker_properties já está definida em inc/custom/broker/dashboard.php
// Comentando a duplicação para evitar erro de sintaxe
/*
// Função para buscar imóveis do corretor
if (!function_exists('get_broker_properties')) {
    function get_broker_properties()
    {
        error_log('Iniciando get_broker_properties');
        
        check_ajax_referer('ajax_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            error_log('get_broker_properties: Usuário não autenticado');
            wp_send_json_error('Usuário não autenticado');
        }

        $user = wp_get_current_user();
        error_log('get_broker_properties: Usuário #' . $user->ID . ' com roles: ' . implode(', ', $user->roles));
        
        if (!in_array('author', (array) $user->roles) && !in_array('administrator', (array) $user->roles)) {
            error_log('get_broker_properties: Usuário não tem permissão de corretor');
            wp_send_json_error('Acesso restrito a corretores');
        }

        $user_id = get_current_user_id();
        
        $args = array(
            'post_type' => 'immobile',
            'posts_per_page' => -1,
            'author' => $user_id,
            'post_status' => array('publish', 'draft', 'pending'),
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        error_log('get_broker_properties: Buscando imóveis com args: ' . json_encode($args));
        
        $query = new WP_Query($args);
        $properties = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                
                $property = array(
                    'id' => $post_id,
                    'title' => get_the_title(),
                    'status' => get_post_status(),
                    'date' => get_the_date('Y-m-d H:i:s'),
                    'link' => get_permalink(),
                    'edit_link' => get_edit_post_link($post_id, 'raw'),
                    'featured_image' => get_the_post_thumbnail_url($post_id, 'thumbnail'),
                    'price' => get_post_meta($post_id, 'price', true),
                    'location' => get_post_meta($post_id, 'location', true),
                    'sponsored' => get_post_meta($post_id, 'is_sponsored', true) === 'yes',
                    'views' => (int)get_post_meta($post_id, 'total_views', true),
                    'clicks' => (int)get_post_meta($post_id, 'total_clicks', true)
                );
                
                $properties[] = $property;
            }
        }
        
        wp_reset_postdata();
        
        error_log('get_broker_properties: Retornando ' . count($properties) . ' imóveis');
        wp_send_json_success(array(
            'properties' => $properties
        ));
    }
}
add_action('wp_ajax_get_broker_properties', 'get_broker_properties');
*/