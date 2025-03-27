console.log('Debug do BrokerDashboard:', {
  react: typeof React,
  reactDOM: typeof ReactDOM,
  recharts: typeof Recharts,
  container: document.getElementById('broker-dashboard-root'),
  site: typeof site !== 'undefined' ? site : 'não definido'
});

(function($) {
    $(document).ready(function() {
        console.log('[DEBUG] Script de diagnóstico carregado');
        
        // Criar elemento de diagnóstico na página
        if ($('#mp-debug-info').length === 0) {
            $('body').append('<div id="mp-debug-info" style="position: fixed; bottom: 10px; right: 10px; background: rgba(0,0,0,0.8); color: #fff; padding: 10px; max-width: 400px; max-height: 300px; overflow: auto; z-index: 9999; font-family: monospace; font-size: 12px;"><h4>Informações de Diagnóstico</h4><div id="mp-debug-content"></div></div>');
        }
        
        // Função para adicionar informações de diagnóstico
        window.mpDebug = function(message, data) {
            console.log('[MP DEBUG]', message, data);
            var debugContent = $('#mp-debug-content');
            if (debugContent.length) {
                var timestamp = new Date().toTimeString().substring(0, 8);
                var dataText = '';
                
                if (data !== undefined) {
                    try {
                        if (typeof data === 'object') {
                            dataText = JSON.stringify(data);
                        } else {
                            dataText = String(data);
                        }
                    } catch(e) {
                        dataText = '[Não foi possível converter dados]';
                    }
                }
                
                debugContent.append('<p><strong>' + timestamp + '</strong>: ' + message + (dataText ? ' - ' + dataText : '') + '</p>');
                
                // Manter o scroll no fundo para mostrar as mensagens mais recentes
                var debugInfo = $('#mp-debug-info');
                debugInfo.scrollTop(debugInfo[0].scrollHeight);
            }
        };
        
        // Verificar disponibilidade de scripts-chave
        mpDebug('Verificando scripts carregados');
        
        // Verificar jQuery
        mpDebug('jQuery disponível', typeof $ === 'function');
        
        // Verificar MercadoPago SDK
        mpDebug('SDK MercadoPago disponível', typeof MercadoPago !== 'undefined');
        
        // Verificar objetos de configuração
        mpDebug('Objeto highlight_payment disponível', typeof highlight_payment !== 'undefined');
        if (typeof highlight_payment !== 'undefined') {
            mpDebug('highlight_payment', highlight_payment);
        }
        
        // Verificar referência ao formulário de pagamento
        mpDebug('Formulário de pagamento presente', $('#payment-form').length > 0);
        mpDebug('Elementos do formulário', {
            cardNumber: $('#cardNumber').length > 0,
            securityCode: $('#securityCode').length > 0,
            cardExpirationMonth: $('#cardExpirationMonth').length > 0,
            cardExpirationYear: $('#cardExpirationYear').length > 0
        });
        
        // Coletar informações sobre a página atual
        var pageInfo = {
            url: window.location.href,
            path: window.location.pathname,
            params: window.location.search,
            scripts: []
        };
        
        // Listar scripts carregados
        $('script').each(function() {
            if (this.src) {
                pageInfo.scripts.push(this.src);
            }
        });
        
        mpDebug('Informações da página', pageInfo);
        
        // Monitorar erros JavaScript
        window.onerror = function(message, source, lineno, colno, error) {
            mpDebug('ERRO JS', {
                message: message,
                source: source,
                line: lineno,
                col: colno,
                stack: error && error.stack ? error.stack : 'Indisponível'
            });
            return false;
        };
        
        // Interceptar erros de API do MercadoPago
        if (typeof MercadoPago !== 'undefined') {
            mpDebug('Monitorando SDK do MercadoPago');
            
            // Monitorar eventos AJAX
            $(document).ajaxError(function(event, jqXHR, ajaxSettings, thrownError) {
                if (ajaxSettings.url && ajaxSettings.url.indexOf('mercadopago') > -1) {
                    mpDebug('Erro na requisição MercadoPago', {
                        url: ajaxSettings.url,
                        status: jqXHR.status,
                        statusText: jqXHR.statusText,
                        response: jqXHR.responseText
                    });
                }
            });
        }
        
        // Adicionar botão para mostrar/esconder painel de debug
        $('body').append('<button id="toggle-mp-debug" style="position: fixed; bottom: 10px; right: 10px; z-index: 10000; background: #f44336; color: white; border: none; padding: 5px 10px; cursor: pointer;">Toggle Debug</button>');
        
        $('#toggle-mp-debug').on('click', function() {
            $('#mp-debug-info').toggle();
        });
        
        // Esconder inicialmente o painel de debug
        $('#mp-debug-info').hide();
        
        mpDebug('Diagnóstico inicializado');
    });
})(jQuery);