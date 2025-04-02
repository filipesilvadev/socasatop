<?php
require_once 'debug-logger.php';

$admin_logger->log("=== DEBUG PESQUISAS INICIADO ===");

// Debug total de pesquisas
$search_count_query = "
    SELECT COUNT(*) 
    FROM {$wpdb->posts} 
    WHERE post_type = 'smart-search' 
    AND post_status = 'publish'";
$total_searches = $wpdb->get_var($search_count_query);

$admin_logger->log("Query contagem total: " . $search_count_query);
$admin_logger->log("Resultado contagem total: " . $total_searches);

// Debug estrutura da tabela
$table_info = $wpdb->get_results("
    SHOW COLUMNS 
    FROM {$wpdb->posts} 
    WHERE Field IN ('post_type', 'post_status')");
$admin_logger->log("Estrutura das colunas relevantes:");
$admin_logger->log_metrics($table_info, 'table_structure');

// Debug amostra de registros
$sample_searches = $wpdb->get_results("
    SELECT ID, post_type, post_status, post_date 
    FROM {$wpdb->posts} 
    WHERE post_type = 'smart-search' 
    LIMIT 5");
$admin_logger->log("Amostra de registros de pesquisa:");
$admin_logger->log_metrics($sample_searches, 'sample_searches');

// Debug contagem por status
$status_count = $wpdb->get_results("
    SELECT post_status, COUNT(*) as total 
    FROM {$wpdb->posts} 
    WHERE post_type = 'smart-search' 
    GROUP BY post_status");
$admin_logger->log("Contagem por status:");
$admin_logger->log_metrics($status_count, 'status_count');

$admin_logger->log("=== DEBUG PESQUISAS FINALIZADO ===");