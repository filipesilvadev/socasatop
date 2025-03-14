<?php
function debug_sponsored_listings_table() {
  global $wpdb;
  $table_name = $wpdb->prefix . 'sponsored_listings';
  
  error_log("=== INÍCIO DEBUG TABELA SPONSORED_LISTINGS ===");
  
  // Verifica se a tabela existe
  $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
  error_log("Tabela existe? " . ($table_exists ? 'Sim' : 'Não'));
  
  if ($table_exists) {
      // Verifica estrutura da tabela
      $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
      error_log("Estrutura da tabela: " . print_r($columns, true));
      
      // Verifica total de registros
      $total_records = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
      error_log("Total de registros: " . $total_records);
      
      // Verifica registros ativos
      $active_records = $wpdb->get_results("
          SELECT sl.*, p.post_title 
          FROM $table_name sl
          JOIN {$wpdb->posts} p ON sl.property_id = p.ID
          WHERE sl.status = 'active' 
          AND sl.end_date >= CURDATE()
      ");
      error_log("Registros ativos: " . print_r($active_records, true));
  }
  
  error_log("=== FIM DEBUG TABELA SPONSORED_LISTINGS ===");
}

// Adicionar hook para executar o debug
add_action('init', 'debug_sponsored_listings_table');

// ------------------ FIM DO DEBUG ------------------




// ------------------- DADOS DE TESTE -------------
function insert_test_sponsored_listings() {
  global $wpdb;
  $table_name = $wpdb->prefix . 'sponsored_listings';

  // Busca alguns imóveis existentes
  $properties = $wpdb->get_results("
      SELECT ID 
      FROM {$wpdb->posts} 
      WHERE post_type = 'immobile' 
      AND post_status = 'publish' 
      LIMIT 5
  ");

  if (empty($properties)) {
      error_log("Nenhum imóvel encontrado para teste");
      return;
  }

  $start_date = date('Y-m-d');
  $end_date = date('Y-m-d', strtotime('+30 days'));

  foreach ($properties as $property) {
      // Verifica se já existe patrocínio ativo para este imóvel
      $exists = $wpdb->get_var($wpdb->prepare(
          "SELECT id FROM $table_name WHERE property_id = %d AND status = 'active'",
          $property->ID
      ));

      if (!$exists) {
          $wpdb->insert(
              $table_name,
              [
                  'property_id' => $property->ID,
                  'payment_id' => 'TEST_' . uniqid(),
                  'start_date' => $start_date,
                  'end_date' => $end_date,
                  'status' => 'active'
              ],
              ['%d', '%s', '%s', '%s', '%s']
          );

          if ($wpdb->last_error) {
              error_log("Erro ao inserir patrocínio teste: " . $wpdb->last_error);
          } else {
              error_log("Patrocínio teste inserido para imóvel ID: " . $property->ID);
          }
      }
  }
}

// Para executar a função, descomente a linha abaixo temporariamente
add_action('init', 'insert_test_sponsored_listings');






function create_sponsored_listings_table() {
  global $wpdb;
  $table_name = $wpdb->prefix . 'sponsored_listings';
  
  $charset_collate = $wpdb->get_charset_collate();
  
  $sql = "CREATE TABLE IF NOT EXISTS $table_name (
      id bigint(20) NOT NULL AUTO_INCREMENT,
      property_id bigint(20) NOT NULL,
      payment_id varchar(100) NOT NULL,
      start_date date NOT NULL,
      end_date date NOT NULL,
      status varchar(20) NOT NULL,
      PRIMARY KEY  (id),
      KEY property_id (property_id),
      KEY status (status),
      KEY end_date (end_date)
  ) $charset_collate;";
  
  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
  dbDelta($sql);
}

add_action('init', 'create_sponsored_listings_table');

// Função para verificar se um imóvel está patrocinado
function is_property_sponsored($property_id) {
  global $wpdb;
  $table_name = $wpdb->prefix . 'sponsored_listings';
  
  $result = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM $table_name 
      WHERE property_id = %d 
      AND status = 'active' 
      AND end_date >= CURDATE()",
      $property_id
  ));
  
  return (bool)$result;
}

// Função para obter todos os imóveis patrocinados ativos
function get_active_sponsored_properties() {
  global $wpdb;
  $table_name = $wpdb->prefix . 'sponsored_listings';
  
  $results = $wpdb->get_results(
      "SELECT property_id FROM $table_name 
      WHERE status = 'active' 
      AND end_date >= CURDATE()"
  );
  
  return array_map(function($row) {
      return $row->property_id;
  }, $results);
}