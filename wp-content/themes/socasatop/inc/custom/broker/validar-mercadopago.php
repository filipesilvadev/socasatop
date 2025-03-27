<?php
// Script para validar a configuração do MercadoPago

// Ativar saída detalhada para diagnóstico
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Definir constantes para testes
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(dirname(dirname(dirname(dirname(__FILE__))))));
}

// Tentar carregar o WordPress
$wp_load_path = ABSPATH . '/wp-load.php';
if (file_exists($wp_load_path)) {
    require_once($wp_load_path);
    echo "<p>WordPress carregado com sucesso.</p>";
} else {
    die("<p>Erro: Não foi possível encontrar o arquivo wp-load.php em " . $wp_load_path . "</p>");
}

// Verificar se as funções de MercadoPago existem
echo "<h2>Verificando funções do MercadoPago</h2>";

$functions_to_check = [
    'get_mercadopago_config',
    'register_mercadopago_admin_menu',
    'process_save_card',
    'get_user_mercadopago_cards',
    'create_payment_settings_js'
];

foreach ($functions_to_check as $function) {
    if (function_exists($function)) {
        echo "<p>Função <strong>{$function}</strong> encontrada.</p>";
    } else {
        echo "<p style='color: red;'>Função <strong>{$function}</strong> NÃO encontrada!</p>";
    }
}

// Verificar arquivo de configuração
$settings_file = ABSPATH . '/wp-content/themes/socasatop/inc/custom/broker/payment-settings.php';
if (file_exists($settings_file)) {
    echo "<p>Arquivo de configurações encontrado: " . $settings_file . "</p>";
    
    // Verificar se o arquivo foi incluído
    $included_files = get_included_files();
    if (in_array($settings_file, $included_files)) {
        echo "<p>Arquivo de configurações já está incluído.</p>";
    } else {
        echo "<p>Incluindo arquivo de configurações...</p>";
        include_once($settings_file);
        
        // Verificar novamente as funções
        echo "<h3>Verificando funções após incluir arquivo:</h3>";
        foreach ($functions_to_check as $function) {
            if (function_exists($function)) {
                echo "<p>Função <strong>{$function}</strong> encontrada após inclusão.</p>";
            } else {
                echo "<p style='color: red;'>Função <strong>{$function}</strong> ainda NÃO encontrada após inclusão!</p>";
            }
        }
    }
} else {
    echo "<p style='color: red;'>Arquivo de configurações NÃO encontrado: " . $settings_file . "</p>";
}

// Verificar configurações do MercadoPago
if (function_exists('get_mercadopago_config')) {
    echo "<h2>Configurações do MercadoPago</h2>";
    $config = get_mercadopago_config();
    
    echo "<pre>";
    // Omitir tokens sensíveis
    $safe_config = $config;
    if (isset($safe_config['access_token'])) $safe_config['access_token'] = substr($safe_config['access_token'], 0, 10) . '...';
    if (isset($safe_config['test_access_token'])) $safe_config['test_access_token'] = substr($safe_config['test_access_token'], 0, 10) . '...';
    print_r($safe_config);
    echo "</pre>";
    
    // Verificar se as chaves estão configuradas
    $test_mode = isset($config['test_mode']) && $config['test_mode'];
    $public_key = $test_mode ? (isset($config['test_public_key']) ? $config['test_public_key'] : '') : (isset($config['public_key']) ? $config['public_key'] : '');
    $access_token = $test_mode ? (isset($config['test_access_token']) ? $config['test_access_token'] : '') : (isset($config['access_token']) ? $config['access_token'] : '');
    
    echo "<h3>Status de Configuração</h3>";
    echo "<p>Modo de teste: " . ($test_mode ? 'Ativado' : 'Desativado') . "</p>";
    
    if (empty($public_key)) {
        echo "<p style='color: red;'>Chave pública não configurada para o ambiente " . ($test_mode ? 'de teste' : 'de produção') . ".</p>";
    } else {
        echo "<p style='color: green;'>Chave pública configurada para o ambiente " . ($test_mode ? 'de teste' : 'de produção') . ": " . substr($public_key, 0, 10) . "...</p>";
    }
    
    if (empty($access_token)) {
        echo "<p style='color: red;'>Token de acesso não configurado para o ambiente " . ($test_mode ? 'de teste' : 'de produção') . ".</p>";
    } else {
        echo "<p style='color: green;'>Token de acesso configurado para o ambiente " . ($test_mode ? 'de teste' : 'de produção') . ": " . substr($access_token, 0, 10) . "...</p>";
    }
} else {
    echo "<p style='color: red;'>Função get_mercadopago_config não encontrada, não é possível verificar as configurações.</p>";
}

// Verificar se o script do MercadoPago está sendo registrado corretamente
echo "<h2>Verificando registro de scripts</h2>";

if (function_exists('create_payment_settings_js')) {
    echo "<p>Função create_payment_settings_js existe, verificando conteúdo...</p>";
    
    // Verificar se o arquivo JS existe
    $js_file = ABSPATH . '/wp-content/themes/socasatop/inc/custom/broker/assets/js/payment-settings.js';
    if (file_exists($js_file)) {
        echo "<p style='color: green;'>Arquivo JS existe: " . $js_file . "</p>";
        echo "<p>Tamanho do arquivo: " . filesize($js_file) . " bytes</p>";
        
        // Mostrar o conteúdo das primeiras linhas para diagnóstico
        $js_content = file_get_contents($js_file);
        $first_lines = implode("\n", array_slice(explode("\n", $js_content), 0, 20));
        echo "<h3>Primeiras linhas do arquivo JS:</h3>";
        echo "<pre>" . htmlspecialchars($first_lines) . "...</pre>";
        
        // Verificar menções ao SDK do MercadoPago
        if (strpos($js_content, 'MercadoPago') !== false) {
            echo "<p style='color: green;'>O arquivo JS contém referências ao SDK do MercadoPago.</p>";
        } else {
            echo "<p style='color: red;'>O arquivo JS NÃO contém referências ao SDK do MercadoPago!</p>";
        }
    } else {
        echo "<p style='color: red;'>Arquivo JS NÃO existe: " . $js_file . "</p>";
        
        // Tentar gerar o arquivo
        echo "<p>Tentando gerar o arquivo JS...</p>";
        if (function_exists('create_payment_settings_js')) {
            create_payment_settings_js();
            
            if (file_exists($js_file)) {
                echo "<p style='color: green;'>Arquivo JS gerado com sucesso!</p>";
            } else {
                echo "<p style='color: red;'>Falha ao gerar o arquivo JS.</p>";
            }
        }
    }
} else {
    echo "<p style='color: red;'>Função create_payment_settings_js não encontrada!</p>";
}

// Verificar opções no banco de dados
echo "<h2>Verificando opções no banco de dados</h2>";

$mercadopago_options = [
    'mercadopago_settings',
    'mercadopago_test_mode',
    'mercadopago_test_public_key',
    'mercadopago_test_access_token',
    'mercadopago_public_key',
    'mercadopago_access_token',
    'highlight_payment_price'
];

foreach ($mercadopago_options as $option) {
    $value = get_option($option);
    if ($value !== false) {
        if (strpos($option, 'token') !== false || strpos($option, 'key') !== false) {
            // Mascarar tokens e chaves por segurança
            if (is_string($value)) {
                $masked_value = substr($value, 0, 10) . '...';
            } else {
                $masked_value = '[Valor complexo]';
            }
            echo "<p>Opção <strong>{$option}</strong> encontrada no banco de dados. Valor: {$masked_value}</p>";
        } else {
            if (is_array($value) || is_object($value)) {
                echo "<p>Opção <strong>{$option}</strong> encontrada no banco de dados. Valor:</p>";
                echo "<pre>";
                print_r($value);
                echo "</pre>";
            } else {
                echo "<p>Opção <strong>{$option}</strong> encontrada no banco de dados. Valor: {$value}</p>";
            }
        }
    } else {
        echo "<p style='color: red;'>Opção <strong>{$option}</strong> NÃO encontrada no banco de dados!</p>";
    }
}

// Verificar enqueues dos scripts necessários
echo "<h2>Verificando scripts registrados</h2>";

// Listar scripts registrados
global $wp_scripts;
if (isset($wp_scripts) && is_object($wp_scripts)) {
    $registered_scripts = [];
    
    foreach ($wp_scripts->registered as $handle => $script) {
        if (strpos($handle, 'mercado') !== false || 
            strpos($handle, 'payment') !== false ||
            strpos($handle, 'highlight') !== false ||
            strpos($script->src, 'mercado') !== false || 
            strpos($script->src, 'payment') !== false ||
            strpos($script->src, 'highlight') !== false) {
            $registered_scripts[$handle] = $script->src;
        }
    }
    
    if (count($registered_scripts) > 0) {
        echo "<p>Scripts relacionados a pagamentos encontrados:</p>";
        echo "<ul>";
        foreach ($registered_scripts as $handle => $src) {
            echo "<li><strong>{$handle}</strong>: {$src}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>Nenhum script relacionado a pagamentos encontrado!</p>";
    }
} else {
    echo "<p style='color: red;'>Objeto wp_scripts não disponível!</p>";
}

// Tentar detectar o local onde o script do MercadoPago é carregado
echo "<h2>Buscando locais de inicialização do MercadoPago</h2>";

$theme_dir = ABSPATH . '/wp-content/themes/socasatop';
$files_to_check = [
    '/functions.php',
    '/inc/custom/broker/highlight-payment.php',
    '/inc/custom/broker/payment-settings.php',
    '/inc/custom/broker/payment-processors/mercadopago.php'
];

foreach ($files_to_check as $file) {
    $full_path = $theme_dir . $file;
    if (file_exists($full_path)) {
        $content = file_get_contents($full_path);
        
        // Procurar referências ao SDK do MercadoPago
        $mp_references = preg_match_all('/MercadoPago|mercadopago|mp\.createCardToken|mp\.getIdentificationTypes|sdk\.mercadopago\.com/i', $content, $matches);
        
        if ($mp_references > 0) {
            echo "<p>Arquivo <strong>{$file}</strong> contém {$mp_references} referências ao MercadoPago.</p>";
            
            // Mostrar as linhas específicas onde o MercadoPago é mencionado
            $lines = explode("\n", $content);
            $matching_lines = [];
            
            foreach ($lines as $i => $line) {
                if (preg_match('/MercadoPago|mercadopago|mp\.createCardToken|mp\.getIdentificationTypes|sdk\.mercadopago\.com/i', $line)) {
                    $matching_lines[] = "Linha " . ($i+1) . ": " . htmlspecialchars(trim($line));
                }
            }
            
            if (count($matching_lines) > 0) {
                echo "<p>Linhas relevantes:</p>";
                echo "<pre>" . implode("\n", array_slice($matching_lines, 0, 10)) . "</pre>";
                if (count($matching_lines) > 10) {
                    echo "<p>... e mais " . (count($matching_lines) - 10) . " linhas.</p>";
                }
            }
        } else {
            echo "<p>Arquivo <strong>{$file}</strong> não contém referências ao MercadoPago.</p>";
        }
    } else {
        echo "<p style='color: red;'>Arquivo <strong>{$file}</strong> não encontrado!</p>";
    }
}

echo "<h2>Conclusão</h2>";
echo "<p>Verificação completa das configurações do MercadoPago.</p>";
echo "<p>Timestamp: " . date('Y-m-d H:i:s') . "</p>";
?> 