console.log('Debug do BrokerDashboard:', {
  react: typeof React,
  reactDOM: typeof ReactDOM,
  recharts: typeof Recharts,
  container: document.getElementById('broker-dashboard-root'),
  site: typeof site !== 'undefined' ? site : 'não definido'
});

(function($) {
    $(document).ready(function() {
        console.log('=== Diagnóstico de Página de Destaque ===');
        
        // Verificar se estamos na página de destaque
        const isHighlightPage = window.location.href.indexOf('destacar-imovel') > -1;
        console.log('Página de destaque detectada:', isHighlightPage);
        
        // Verificar se o ID do imóvel está presente
        const urlParams = new URLSearchParams(window.location.search);
        const immobileId = urlParams.get('immobile_id');
        console.log('ID do imóvel:', immobileId);
        
        // Verificar se a variável highlight_payment foi definida
        if (typeof highlight_payment !== 'undefined') {
            console.log('Variável highlight_payment definida:', highlight_payment);
        } else {
            console.log('ERRO: Variável highlight_payment não definida');
        }
        
        // Verificar se o SDK do MercadoPago está carregado
        if (typeof MercadoPago !== 'undefined') {
            console.log('SDK do MercadoPago carregado');
        } else {
            console.log('ERRO: SDK do MercadoPago não carregado');
        }
        
        // Verificar elementos do DOM importantes
        const elementsToCheck = [
            { selector: '.highlight-payment-container', name: 'Container do formulário' },
            { selector: '#cardNumberContainer', name: 'Campo de número do cartão' },
            { selector: '#new-card-panel', name: 'Painel de novo cartão' },
            { selector: '.highlight-button', name: 'Botão de destaque' }
        ];
        
        elementsToCheck.forEach(item => {
            const element = $(item.selector);
            console.log(`${item.name} (${item.selector}): ${element.length > 0 ? 'Encontrado' : 'NÃO ENCONTRADO'}`);
        });
        
        console.log('=== Fim do Diagnóstico ===');
    });
})(jQuery);