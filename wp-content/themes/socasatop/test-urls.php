<?php
/**
 * Script para testar se as URLs estão funcionando corretamente.
 * Este script pode ser executado via terminal com:
 * php test-urls.php
 */

// Definir as URLs para testar
$urls = [
    'https://socasatop.com.br/imovel/oportunidade-unica-na-ql-22-lago-sul/?nocache=' . time(),
    'https://socasatop.com.br/listaimoveis/teste-13/?nocache=' . time()
];

// Função para verificar uma URL
function test_url($url) {
    echo "Testando URL: $url\n";
    
    // Usar curl para obter a resposta
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $header_size);
    $body = substr($response, $header_size);
    
    // Verificar se recebemos uma resposta
    if ($response === false) {
        echo "ERRO: " . curl_error($ch) . "\n";
        return false;
    }
    
    // Mostrar informações básicas
    echo "Código de status: $httpcode\n";
    echo "Tamanho do corpo: " . strlen($body) . " bytes\n";
    echo "Tamanho do cabeçalho: " . strlen($headers) . " bytes\n";
    
    // Verificar se o conteúdo da página parece válido
    $result = [
        'status' => $httpcode,
        'has_content' => strlen($body) > 0,
        'has_html' => stripos($body, '<html') !== false,
        'has_header' => stripos($body, '<header') !== false || stripos($body, '<head') !== false,
        'has_body' => stripos($body, '<body') !== false,
        'has_container' => stripos($body, 'container') !== false,
        'has_content_div' => stripos($body, 'content') !== false,
        'has_footer' => stripos($body, '<footer') !== false || stripos($body, 'footer') !== false,
    ];
    
    // Mostrar resultados das verificações
    echo "Análise de conteúdo:\n";
    foreach ($result as $key => $value) {
        echo "- $key: " . ($value ? "SIM" : "NÃO") . "\n";
    }
    
    // Determinar se parece uma página válida
    $is_valid = $result['has_content'] && $result['has_html'] && $result['has_body'];
    echo "Conclusão: " . ($is_valid ? "Página parece válida" : "Página parece INVÁLIDA") . "\n";
    
    // Mostrar os primeiros 500 caracteres da resposta para debug
    echo "\nPrimeiros 500 caracteres do corpo:\n";
    echo substr($body, 0, 500) . "\n";
    
    // Mostrar os primeiros 500 caracteres do cabeçalho para debug
    echo "\nPrimeiros 500 caracteres do cabeçalho:\n";
    echo substr($headers, 0, 500) . "\n";
    
    // Salvar o HTML para inspeção
    $output_file = 'page_output_' . md5($url) . '.html';
    file_put_contents($output_file, $body);
    echo "Conteúdo HTML salvo em: $output_file\n";
    
    echo "------------------------------\n\n";
    
    curl_close($ch);
    return $is_valid;
}

// Testar cada URL
$all_valid = true;
foreach ($urls as $url) {
    $result = test_url($url);
    if (!$result) {
        $all_valid = false;
    }
}

// Mostrar resultado final
if ($all_valid) {
    echo "RESULTADO FINAL: Todas as URLs passaram no teste!\n";
} else {
    echo "RESULTADO FINAL: Uma ou mais URLs apresentaram problemas!\n";
} 