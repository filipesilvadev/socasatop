<?php
/**
 * Template Name: Teste de Carregamento de Páginas
 * 
 * Script para testar o carregamento das páginas de imóveis
 */

// Garantir que apenas administradores possam acessar
if (!current_user_can('administrator')) {
    wp_die('Acesso restrito a administradores.');
}

get_header();
?>

<div class="container test-pages-container">
    <h1>Teste de Carregamento de Páginas</h1>
    
    <div class="test-form">
        <h2>URLs para Testar</h2>
        <div class="test-urls">
            <div class="test-url-item">
                <strong>Imóvel:</strong> 
                <span class="url">https://socasatop.com.br/imovel/oportunidade-unica-na-ql-22-lago-sul/</span>
                <button class="test-button" data-url="https://socasatop.com.br/imovel/oportunidade-unica-na-ql-22-lago-sul/">Testar</button>
            </div>
            <div class="test-url-item">
                <strong>Lista de Imóveis:</strong> 
                <span class="url">https://socasatop.com.br/listaimoveis/teste-13/</span>
                <button class="test-button" data-url="https://socasatop.com.br/listaimoveis/teste-13/">Testar</button>
            </div>
        </div>
        
        <div class="custom-url-test">
            <h3>Testar outra URL</h3>
            <input type="url" id="custom-url" placeholder="https://socasatop.com.br/..." class="custom-url-input">
            <button id="test-custom-url" class="test-button">Testar URL</button>
        </div>
    </div>
    
    <div class="test-results">
        <h2>Resultados do Teste</h2>
        <div id="results-container">
            <p>Clique em um botão "Testar" para verificar o carregamento de uma página.</p>
        </div>
    </div>
</div>

<style>
.test-pages-container {
    max-width: 800px;
    margin: 40px auto;
    padding: 20px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.test-pages-container h1 {
    text-align: center;
    margin-bottom: 30px;
    color: #333;
}

.test-form {
    margin-bottom: 30px;
}

.test-urls {
    margin-bottom: 20px;
}

.test-url-item {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
    padding: 10px;
    background: #f9f9f9;
    border-radius: 4px;
}

.test-url-item strong {
    min-width: 120px;
}

.test-url-item .url {
    flex-grow: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    padding: 0 10px;
    font-family: monospace;
}

.test-button {
    background: #0056b3;
    color: white;
    border: none;
    border-radius: 4px;
    padding: 8px 16px;
    cursor: pointer;
    transition: background 0.3s;
}

.test-button:hover {
    background: #003d7a;
}

.custom-url-test {
    margin-top: 20px;
    padding: 15px;
    background: #f5f5f5;
    border-radius: 4px;
}

.custom-url-input {
    width: 100%;
    padding: 8px;
    margin-bottom: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-family: monospace;
}

.test-results {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 4px;
}

.test-result-item {
    margin-bottom: 20px;
    padding: 15px;
    background: white;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.test-result-item .header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.test-result-item .url {
    font-weight: bold;
    word-break: break-all;
}

.test-result-item .timestamp {
    color: #666;
    font-size: 0.9em;
}

.test-result-item .status {
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 0.9em;
    font-weight: bold;
}

.status-success {
    background: #d4edda;
    color: #155724;
}

.status-error {
    background: #f8d7da;
    color: #721c24;
}

.test-result-item .details {
    margin-top: 10px;
    font-family: monospace;
    white-space: pre-wrap;
    background: #f5f5f5;
    padding: 10px;
    border-radius: 4px;
    max-height: 200px;
    overflow-y: auto;
}

.iframe-container {
    margin-top: 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    overflow: hidden;
    height: 300px;
}

.iframe-container iframe {
    width: 100%;
    height: 100%;
    border: none;
}
</style>

<script>
jQuery(document).ready(function($) {
    $('.test-button').on('click', function() {
        const url = $(this).data('url');
        testUrl(url);
    });
    
    $('#test-custom-url').on('click', function() {
        const url = $('#custom-url').val();
        if (url) {
            testUrl(url);
        } else {
            alert('Por favor, digite uma URL válida');
        }
    });
    
    function testUrl(url) {
        // Adicionar resultado de teste
        const resultId = 'result-' + Date.now();
        const timestamp = new Date().toLocaleString();
        
        $('#results-container').prepend(`
            <div class="test-result-item" id="${resultId}">
                <div class="header">
                    <div class="url">${url}</div>
                    <div class="timestamp">${timestamp}</div>
                </div>
                <div class="loading">Testando... <img src="/wp-admin/images/loading.gif" alt="Carregando"></div>
            </div>
        `);
        
        // Fazer a requisição
        $.ajax({
            url: url,
            type: 'GET',
            success: function(data, textStatus, xhr) {
                // Verificar se a página foi carregada corretamente
                const hasContent = data.includes('site-content') || data.includes('container');
                const hasStyles = data.includes('.css') || data.includes('<style');
                const hasScripts = data.includes('.js') || data.includes('<script');
                
                let status = 'success';
                let statusText = 'OK';
                let details = 'A página parece estar carregando corretamente:\n';
                
                if (!hasContent) {
                    status = 'error';
                    details += '- ERRO: Não foi encontrado o conteúdo principal da página.\n';
                } else {
                    details += '- OK: Conteúdo principal encontrado.\n';
                }
                
                if (!hasStyles) {
                    status = 'error';
                    details += '- ERRO: Não foram encontrados estilos CSS.\n';
                } else {
                    details += '- OK: Estilos CSS encontrados.\n';
                }
                
                if (!hasScripts) {
                    status = 'error';
                    details += '- ERRO: Não foram encontrados scripts JS.\n';
                } else {
                    details += '- OK: Scripts JS encontrados.\n';
                }
                
                details += `\nCódigo HTTP: ${xhr.status} ${xhr.statusText}`;
                
                $(`#${resultId} .loading`).remove();
                $(`#${resultId}`).append(`
                    <span class="status status-${status}">${status === 'success' ? 'SUCESSO' : 'ERRO'}</span>
                    <div class="details">${details}</div>
                    <div class="iframe-container">
                        <iframe src="${url}" title="Visualização da página"></iframe>
                    </div>
                `);
            },
            error: function(xhr, textStatus, errorThrown) {
                $(`#${resultId} .loading`).remove();
                $(`#${resultId}`).append(`
                    <span class="status status-error">ERRO</span>
                    <div class="details">Erro ao carregar a página: ${xhr.status} ${xhr.statusText}\n${errorThrown}</div>
                `);
            }
        });
    }
});
</script>

<?php get_footer(); ?> 