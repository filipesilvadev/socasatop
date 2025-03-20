(() => {
  console.log('🔍 Broker Dashboard: Script carregado');
  
  // Verificar se as variáveis globais necessárias estão disponíveis
  if (typeof site === 'undefined') {
    console.error('❌ Variável global "site" não encontrada');
    console.log('💡 Tentando inicializar com valores padrão');
    window.site = {
      ajax_url: '/wp-admin/admin-ajax.php',
      nonce: '',
      user_name: 'Corretor'
    };
  }
  
  // Dados de métricas estáticos para fallback
  const fallbackMetrics = [
    { date: '2023-03-01', views: 15, clicks: 8, conversions: 2 },
    { date: '2023-03-02', views: 18, clicks: 10, conversions: 3 },
    { date: '2023-03-03', views: 22, clicks: 12, conversions: 4 },
    { date: '2023-03-04', views: 20, clicks: 9, conversions: 3 },
    { date: '2023-03-05', views: 25, clicks: 15, conversions: 5 },
    { date: '2023-03-06', views: 30, clicks: 18, conversions: 7 },
    { date: '2023-03-07', views: 28, clicks: 16, conversions: 5 },
    { date: '2023-03-08', views: 32, clicks: 20, conversions: 6 },
    { date: '2023-03-09', views: 35, clicks: 22, conversions: 8 },
    { date: '2023-03-10', views: 30, clicks: 19, conversions: 7 },
    { date: '2023-03-11', views: 28, clicks: 17, conversions: 5 },
    { date: '2023-03-12', views: 33, clicks: 21, conversions: 7 },
    { date: '2023-03-13', views: 36, clicks: 24, conversions: 9 },
    { date: '2023-03-14', views: 38, clicks: 26, conversions: 10 }
  ];
  
  document.addEventListener('DOMContentLoaded', () => {
    console.log('🔍 Broker Dashboard: DOMContentLoaded');
    
    if (typeof site !== 'undefined') {
      console.log('📊 Informações do site:', site);
    }
    
    // Verificar se as bibliotecas necessárias estão disponíveis
    const reactAvailable = typeof React !== 'undefined';
    const reactDomAvailable = typeof ReactDOM !== 'undefined';
    const chartJsAvailable = typeof Chart !== 'undefined';
    const jQueryAvailable = typeof jQuery !== 'undefined';
    
    console.log('✅ React disponível:', reactAvailable);
    console.log('✅ ReactDOM disponível:', reactDomAvailable);
    console.log('✅ Chart.js disponível:', chartJsAvailable);
    console.log('✅ jQuery disponível:', jQueryAvailable);
    
    // Verificar se o contêiner do dashboard existe
    const dashboardContainer = document.querySelector('.broker-dashboard');
    if (!dashboardContainer) {
      console.error('❌ Contêiner do dashboard não encontrado');
      return;
    }
    
    // Verificar se o contêiner do gráfico existe
    const chartContainer = document.getElementById('broker-metrics-chart');
    if (!chartContainer) {
      console.error('❌ Contêiner do gráfico não encontrado');
      
      // Tentar criar o elemento canvas se não existir
      if (document.querySelector('.chart-container')) {
        const canvas = document.createElement('canvas');
        canvas.id = 'broker-metrics-chart';
        document.querySelector('.chart-container').appendChild(canvas);
        console.log('✅ Elemento canvas criado dinamicamente');
      }
    }
    
    // Função para renderizar o gráfico com fallback
    const renderChartWithFallback = () => {
      console.log('📈 Renderizando gráfico com dados de fallback');
      
      // Verificar novamente se o elemento canvas existe
      const ctx = document.getElementById('broker-metrics-chart');
      if (!ctx) {
        console.error('❌ Elemento do gráfico ainda não encontrado após tentativa de criação');
        return;
      }
      
      // Verificar se Chart.js está disponível
      if (typeof Chart === 'undefined') {
        console.error('❌ Chart.js não está disponível');
        
        // Tentar carregar Chart.js dinamicamente
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js';
        script.onload = function() {
          console.log('✅ Chart.js carregado dinamicamente');
          renderStaticChart();
        };
        document.head.appendChild(script);
        return;
      }
      
      renderStaticChart();
    };
    
    // Função para renderizar o gráfico estático
    const renderStaticChart = () => {
      const ctx = document.getElementById('broker-metrics-chart');
      
      // Verificar novamente se o contexto do canvas está disponível
      if (!ctx || !ctx.getContext) {
        console.error('❌ Contexto do canvas não disponível');
        return;
      }
      
      try {
        // Criar a instância do gráfico com dados estáticos
        const chartInstance = new Chart(ctx, {
          type: 'line',
          data: {
            labels: fallbackMetrics.map(item => item.date),
            datasets: [
              {
                label: 'Visualizações',
                data: fallbackMetrics.map(item => item.views),
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1,
                fill: true
              },
              {
                label: 'Acessos',
                data: fallbackMetrics.map(item => item.clicks),
                borderColor: 'rgb(255, 99, 132)',
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                tension: 0.1,
                fill: true
              },
              {
                label: 'Conversões',
                data: fallbackMetrics.map(item => item.conversions),
                borderColor: 'rgb(54, 162, 235)',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                tension: 0.1,
                fill: true
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                position: 'top',
              },
              title: {
                display: true,
                text: 'Desempenho nos Últimos 14 Dias'
              },
              tooltip: {
                callbacks: {
                  label: function(context) {
                    return `${context.dataset.label}: ${context.raw}`;
                  }
                }
              }
            },
            scales: {
              y: {
                beginAtZero: true,
                title: {
                  display: true,
                  text: 'Quantidade'
                }
              },
              x: {
                title: {
                  display: true,
                  text: 'Data'
                }
              }
            }
          }
        });
        
        console.log('✅ Gráfico estático renderizado com sucesso');
      } catch (error) {
        console.error('❌ Erro ao renderizar o gráfico:', error);
      }
    };
    
    // Função para renderizar o gráfico sem React
    const renderChartWithoutReact = () => {
      console.log('📈 Renderizando gráfico sem React');
      
      // Verificar novamente se o elemento canvas existe
      const ctx = document.getElementById('broker-metrics-chart');
      if (!ctx) {
        console.error('❌ Elemento do gráfico não encontrado');
        renderChartWithFallback();
        return;
      }
      
      // Verificar se jQuery está disponível
      if (typeof jQuery === 'undefined') {
        console.error('❌ jQuery não está disponível');
        renderChartWithFallback();
        return;
      }
      
      // Buscar dados de métricas via AJAX
      jQuery.ajax({
        url: site.ajax_url,
        type: 'POST',
        data: {
          action: 'get_broker_metrics',
          nonce: site.nonce
        },
        dataType: 'json',
        success: function(response) {
          console.log('📊 Dados de métricas recebidos:', response);
          
          if (response.success && response.data && response.data.metrics) {
            const metrics = response.data.metrics;
            
            try {
              // Criar a instância do gráfico
              const chartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                  labels: metrics.slice(0, 14).map(item => item.date),
                  datasets: [
                    {
                      label: 'Visualizações',
                      data: metrics.slice(0, 14).map(item => item.views),
                      borderColor: 'rgb(75, 192, 192)',
                      backgroundColor: 'rgba(75, 192, 192, 0.2)',
                      tension: 0.1,
                      fill: true
                    },
                    {
                      label: 'Acessos',
                      data: metrics.slice(0, 14).map(item => item.clicks),
                      borderColor: 'rgb(255, 99, 132)',
                      backgroundColor: 'rgba(255, 99, 132, 0.2)',
                      tension: 0.1,
                      fill: true
                    },
                    {
                      label: 'Conversões',
                      data: metrics.slice(0, 14).map(item => item.conversions),
                      borderColor: 'rgb(54, 162, 235)',
                      backgroundColor: 'rgba(54, 162, 235, 0.2)',
                      tension: 0.1,
                      fill: true
                    }
                  ]
                },
                options: {
                  responsive: true,
                  maintainAspectRatio: false,
                  plugins: {
                    legend: {
                      position: 'top',
                    },
                    title: {
                      display: true,
                      text: 'Desempenho nos Últimos 14 Dias'
                    },
                    tooltip: {
                      callbacks: {
                        label: function(context) {
                          return `${context.dataset.label}: ${context.raw}`;
                        }
                      }
                    }
                  },
                  scales: {
                    y: {
                      beginAtZero: true,
                      title: {
                        display: true,
                        text: 'Quantidade'
                      }
                    },
                    x: {
                      title: {
                        display: true,
                        text: 'Data'
                      }
                    }
                  }
                }
              });
              
              console.log('✅ Gráfico renderizado com sucesso');
            } catch (error) {
              console.error('❌ Erro ao renderizar o gráfico:', error);
              renderChartWithFallback();
            }
          } else {
            console.error('❌ Erro ao buscar métricas:', response);
            renderChartWithFallback();
          }
        },
        error: function(xhr, status, error) {
          console.error('❌ Erro na requisição AJAX:', error);
          renderChartWithFallback();
        }
      });
    };
    
    // Função para configurar handlers jQuery
    const setupJQueryHandlers = () => {
      console.log('🔧 Configurando handlers jQuery');
      
      if (typeof jQuery === 'undefined') {
        console.error('❌ jQuery não está disponível para configurar handlers');
        return;
      }
      
      const $ = jQuery;
      
      // Manipulador para seleção em massa
      $('#select-all-properties').on('change', function() {
        $('.property-checkbox').prop('checked', $(this).is(':checked'));
        updateBulkActions();
      });
      
      // Manipulador para checkboxes individuais
      $('.property-checkbox').on('change', function() {
        updateBulkActions();
      });
      
      // Atualizar visibilidade das ações em massa
      function updateBulkActions() {
        const hasChecked = $('.property-checkbox:checked').length > 0;
        $('.bulk-actions').toggleClass('visible', hasChecked);
      }
      
      // Manipulador para exclusão de imóvel
      $('.delete-button').on('click', function(e) {
        e.preventDefault();
        const propertyId = $(this).data('id');
        
        if (confirm('Tem certeza que deseja excluir este imóvel? Esta ação não pode ser desfeita.')) {
          deleteProperty(propertyId);
        }
      });
      
      // Adicionar handler para o botão de pausar destaque
      $(document).on('click', '.pause-highlight-button', function(e) {
        e.preventDefault();
        const propertyId = $(this).data('id');
        if (confirm('Tem certeza que deseja pausar o destaque deste imóvel?')) {
          pauseHighlight(propertyId, $(this));
        }
      });
      
      // Adicionar handler para o botão de reativar destaque
      $(document).on('click', '.highlight-button', function(e) {
        // Verificar se é um link de reativação (contém o parâmetro immobile_id na URL)
        const url = $(this).attr('href');
        if (url && url.includes('immobile_id=')) {
          e.preventDefault();
          const propertyId = url.split('immobile_id=')[1].split('&')[0];
          if (confirm('Tem certeza que deseja reativar o destaque deste imóvel?')) {
            reactivateHighlight(propertyId, $(this));
          }
        }
      });
      
      // Função para excluir imóvel
      function deleteProperty(propertyId) {
        $.ajax({
          url: site.ajax_url,
          type: 'POST',
          data: {
            action: 'delete_property',
            nonce: site.broker_dashboard_nonce,
            property_id: propertyId
          },
          beforeSend: function() {
            // Mostrar indicador de carregamento
            $(`button[data-id="${propertyId}"]`).html('<i class="fas fa-spinner fa-spin"></i>');
          },
          success: function(response) {
            if (response.success) {
              // Remover o elemento da lista
              $(`div[data-property-id="${propertyId}"]`).fadeOut(300, function() {
                $(this).remove();
              });
              
              // Mostrar mensagem de sucesso
              showNotification('Imóvel excluído com sucesso.', 'success');
            } else {
              // Mostrar mensagem de erro
              showNotification(`Erro ao excluir imóvel: ${response.data.message || 'Erro desconhecido'}`, 'error');
              
              // Restaurar o botão
              $(`button[data-id="${propertyId}"]`).html('<i class="fas fa-trash"></i>');
            }
          },
          error: function() {
            showNotification('Erro ao comunicar com o servidor. Tente novamente.', 'error');
            
            // Restaurar o botão
            $(`button[data-id="${propertyId}"]`).html('<i class="fas fa-trash"></i>');
          }
        });
      }
      
      // Função para pausar destaque
      function pauseHighlight(propertyId, $button) {
        $.ajax({
          url: site.ajax_url,
          type: 'POST',
          data: {
            action: 'toggle_highlight_pause',
            nonce: site.broker_dashboard_nonce || site.nonce,
            property_id: propertyId
          },
          beforeSend: function() {
            // Mostrar indicador de carregamento
            $button.html('<i class="fas fa-spinner fa-spin"></i> <span class="button-label">Processando...</span>');
            $button.prop('disabled', true);
            console.log('Enviando requisição para pausar destaque do imóvel ID:', propertyId);
          },
          success: function(response) {
            console.log('Resposta recebida para pausar destaque:', response);
            
            if (response.success) {
              // Atualizar a interface
              const $propertyItem = $(`div[data-property-id="${propertyId}"]`);
              
              // Adicionar classe 'paused' ao item
              $propertyItem.addClass('paused');
              
              // Remover a tag de destaque
              $propertyItem.find('.sponsored-tag').fadeOut(300, function() {
                $(this).remove();
              });
              
              // Substituir o botão de pausar por reativar
              const $newButton = $('<a></a>')
                .attr('href', `/corretores/destacar-imovel/?immobile_id=${propertyId}`)
                .addClass('action-button highlight-button')
                .attr('title', 'Reativar Destaque')
                .html('<i class="fas fa-star"></i> <span class="button-label">Reativar</span>');
              
              $button.replaceWith($newButton);
              
              // Mostrar mensagem de sucesso
              showNotification('Destaque pausado com sucesso.', 'success');
            } else {
              // Mostrar mensagem de erro
              showNotification(`Erro ao pausar destaque: ${response.data && response.data.message ? response.data.message : 'Erro desconhecido'}`, 'error');
              
              // Restaurar o botão
              $button.html('<i class="fas fa-pause"></i> <span class="button-label">Pausar</span>');
              $button.prop('disabled', false);
            }
          },
          error: function(xhr, status, error) {
            console.error('Erro na requisição AJAX:', xhr.responseText, status, error);
            // Mostrar mensagem de erro
            showNotification('Erro ao pausar destaque. Tente novamente.', 'error');
            
            // Restaurar o botão
            $button.html('<i class="fas fa-pause"></i> <span class="button-label">Pausar</span>');
            $button.prop('disabled', false);
          }
        });
      }
      
      // Função para reativar destaque
      function reactivateHighlight(propertyId, $button) {
        // Mostrar loader no botão
        const originalHtml = $button.html();
        $button.html('<i class="fas fa-spinner fa-spin"></i>');
        $button.prop('disabled', true);
        
        $.ajax({
          url: site.ajax_url,
          type: 'POST',
          data: {
            action: 'reactivate_immobile_highlight',
            nonce: site.nonce,
            property_id: propertyId
          },
          success: function(response) {
            if (response.success) {
              // Atualizar a interface
              const $propertyItem = $(`div[data-property-id="${propertyId}"]`);
              
              // Remover classe 'paused' do item
              $propertyItem.removeClass('paused');
              
              // Adicionar tag de destaque
              if ($propertyItem.find('.sponsored-tag').length === 0) {
                $propertyItem.find('.property-features').prepend('<span class="sponsored-tag">Destaque</span>');
              }
              
              // Substituir o botão de reativar por pausar
              const $newButton = $('<button></button>')
                .addClass('action-button pause-highlight-button')
                .attr('data-id', propertyId)
                .attr('title', 'Pausar Destaque')
                .html('<i class="fas fa-pause"></i> <span class="button-label">Pausar</span>');
              
              $button.replaceWith($newButton);
              
              // Mostrar mensagem de sucesso
              showNotification('Destaque reativado com sucesso.', 'success');
            } else {
              // Mostrar mensagem de erro
              showNotification(`Erro ao reativar destaque: ${response.data && response.data.message ? response.data.message : 'Erro desconhecido'}`, 'error');
              
              // Restaurar o botão
              $button.html(originalHtml);
              $button.prop('disabled', false);
            }
          },
          error: function(xhr, status, error) {
            console.error('Erro na requisição AJAX:', xhr.responseText, status, error);
            // Mostrar mensagem de erro
            showNotification('Erro ao reativar destaque. Tente novamente.', 'error');
            
            // Restaurar o botão
            $button.html(originalHtml);
            $button.prop('disabled', false);
          }
        });
      }
      
      // Função para exibir notificações
      function showNotification(message, type) {
        // Verificar se já existe uma div de notificação
        let $notification = $('.broker-notification');
        
        if ($notification.length === 0) {
          // Criar uma nova div de notificação
          $notification = $('<div></div>')
            .addClass('broker-notification')
            .appendTo('body');
        }
        
        // Definir a classe e o conteúdo
        $notification
          .removeClass('success error')
          .addClass(type)
          .html(message)
          .fadeIn(300);
        
        // Esconder a notificação após 3 segundos
        setTimeout(function() {
          $notification.fadeOut(300);
        }, 3000);
      }
      
      // Adicionar estilos para a notificação
      if (!$('#broker-notification-styles').length) {
        $('head').append(`
          <style id="broker-notification-styles">
            .broker-notification {
              position: fixed;
              top: 20px;
              right: 20px;
              padding: 15px 20px;
              border-radius: 4px;
              color: white;
              font-weight: 500;
              z-index: 9999;
              display: none;
              box-shadow: 0 4px 6px rgba(0,0,0,0.1);
              max-width: 300px;
            }
            
            .broker-notification.success {
              background-color: #4CAF50;
            }
            
            .broker-notification.error {
              background-color: #F44336;
            }
          </style>
        `);
      }
    };
    
    // Força a renderização inicial do gráfico, independentemente das condições
    // Esta chamada garante que pelo menos tentaremos renderizar o gráfico
    setTimeout(() => {
      console.log('⏱️ Tentando renderizar o gráfico após timeout');
      
      // Verificar se o gráfico já foi renderizado (verificando se há elementos criados dentro do canvas)
      const chartEl = document.getElementById('broker-metrics-chart');
      if (chartEl && (!chartEl.childNodes || chartEl.childNodes.length === 0)) {
        renderChartWithoutReact();
      }
    }, 500);
    
    // Se alguma biblioteca necessária não estiver disponível
    if (!chartJsAvailable || !jQueryAvailable) {
      console.error('❌ Algumas bibliotecas necessárias não estão disponíveis');
      console.log('💡 Tentando carregar bibliotecas dinamicamente');
      
      // Tentar carregar jQuery dinamicamente se não estiver disponível
      if (!jQueryAvailable) {
        const jqueryScript = document.createElement('script');
        jqueryScript.src = 'https://code.jquery.com/jquery-3.6.0.min.js';
        jqueryScript.onload = function() {
          console.log('✅ jQuery carregado dinamicamente');
          
          // Tentar carregar Chart.js dinamicamente se não estiver disponível
          if (!chartJsAvailable) {
            const chartScript = document.createElement('script');
            chartScript.src = 'https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js';
            chartScript.onload = function() {
              console.log('✅ Chart.js carregado dinamicamente');
              
              // Renderizar o gráfico quando ambas as bibliotecas estiverem carregadas
              renderChartWithoutReact();
              setupJQueryHandlers();
            };
            document.head.appendChild(chartScript);
          } else {
            // Se Chart.js já estiver disponível, apenas renderizar o gráfico
            renderChartWithoutReact();
            setupJQueryHandlers();
          }
        };
        document.head.appendChild(jqueryScript);
      } else if (!chartJsAvailable) {
        // Se apenas Chart.js não estiver disponível
        const chartScript = document.createElement('script');
        chartScript.src = 'https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js';
        chartScript.onload = function() {
          console.log('✅ Chart.js carregado dinamicamente');
          renderChartWithoutReact();
          setupJQueryHandlers();
        };
        document.head.appendChild(chartScript);
      }
      
      return;
    }
    
    // Se o Chart.js estiver disponível, renderizar o gráfico
    if (chartJsAvailable) {
      console.log('ℹ️ Chart.js disponível, renderizando gráfico');
      renderChartWithoutReact();
      setupJQueryHandlers();
      
      // Se o React não estiver disponível, encerrar aqui
      if (!reactAvailable || !reactDomAvailable) {
        return;
      }
    }
    
    // Continuar com a renderização React se disponível
    try {
      // Se tudo estiver disponível, inicializar o componente React
      const { useState, useEffect } = React;
      let chartInstance = null;
      
      const BrokerDashboard = () => {
        const [loading, setLoading] = useState(true);
        const [error, setError] = useState(null);
        const [metrics, setMetrics] = useState(null);
        const [properties, setProperties] = useState([]);
        
        useEffect(() => {
          fetchData();
        }, []);
        
        // Função para renderizar o gráfico de métricas
        useEffect(() => {
          if (metrics && metrics.metrics && !loading) {
            const ctx = document.getElementById('broker-metrics-chart');
            
            if (!ctx) {
              console.error('❌ Elemento do gráfico não encontrado');
              return;
            }
            
            console.log('📊 Renderizando gráfico com dados:', metrics.metrics);
            
            // Destruir instância anterior do gráfico se existir
            if (chartInstance) {
              chartInstance.destroy();
            }
            
            try {
              // Criar nova instância do gráfico
              chartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                  labels: metrics.metrics.slice(0, 14).map(item => item.date),
                  datasets: [
                    {
                      label: 'Visualizações',
                      data: metrics.metrics.slice(0, 14).map(item => item.views),
                      borderColor: 'rgb(75, 192, 192)',
                      backgroundColor: 'rgba(75, 192, 192, 0.2)',
                      tension: 0.1,
                      fill: true
                    },
                    {
                      label: 'Acessos',
                      data: metrics.metrics.slice(0, 14).map(item => item.clicks),
                      borderColor: 'rgb(255, 99, 132)',
                      backgroundColor: 'rgba(255, 99, 132, 0.2)',
                      tension: 0.1,
                      fill: true
                    },
                    {
                      label: 'Conversões',
                      data: metrics.metrics.slice(0, 14).map(item => item.conversions),
                      borderColor: 'rgb(54, 162, 235)',
                      backgroundColor: 'rgba(54, 162, 235, 0.2)',
                      tension: 0.1,
                      fill: true
                    }
                  ]
                },
                options: {
                  responsive: true,
                  maintainAspectRatio: false,
                  plugins: {
                    legend: {
                      position: 'top',
                    },
                    title: {
                      display: true,
                      text: 'Desempenho nos Últimos 14 Dias'
                    },
                    tooltip: {
                      callbacks: {
                        label: function(context) {
                          return `${context.dataset.label}: ${context.raw}`;
                        }
                      }
                    }
                  },
                  scales: {
                    y: {
                      beginAtZero: true,
                      title: {
                        display: true,
                        text: 'Quantidade'
                      }
                    },
                    x: {
                      title: {
                        display: true,
                        text: 'Data'
                      }
                    }
                  }
                }
              });
              
              console.log('✅ Gráfico renderizado com sucesso via React');
            } catch (error) {
              console.error('❌ Erro ao renderizar o gráfico via React:', error);
              renderChartWithFallback();
            }
          }
        }, [metrics, loading]);
        
        const fetchData = async () => {
          try {
            // Buscar métricas
            const metricsResponse = await fetch(`${site.ajax_url}`, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
              },
              body: `action=get_broker_metrics&nonce=${site.nonce}`
            });
            
            if (!metricsResponse.ok) {
              throw new Error('Erro ao buscar métricas');
            }
            
            const metricsData = await metricsResponse.json();
            if (!metricsData.success) {
              throw new Error(metricsData.data || 'Erro ao buscar métricas');
            }
            
            console.log('📊 Métricas recebidas:', metricsData.data);
            setMetrics(metricsData.data);
            
            // Buscar imóveis
            const propertiesResponse = await fetch(`${site.ajax_url}`, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
              },
              body: `action=get_broker_properties&nonce=${site.nonce}`
            });
            
            if (!propertiesResponse.ok) {
              throw new Error('Erro ao buscar imóveis');
            }
            
            const propertiesData = await propertiesResponse.json();
            if (!propertiesData.success) {
              throw new Error(propertiesData.data || 'Erro ao buscar imóveis');
            }
            
            setProperties(propertiesData.data);
            setLoading(false);
          } catch (err) {
            console.error('Erro ao carregar dados:', err);
            setError(err.message);
            setLoading(false);
            
            // Em caso de erro, renderizar gráfico com dados de fallback
            renderChartWithFallback();
          }
        };
        
        if (loading) {
          return React.createElement('div', { className: 'loading' }, 'Carregando...');
        }
        
        if (error) {
          return React.createElement('div', { className: 'notice notice-error' },
            React.createElement('p', null, 'Erro ao carregar o dashboard:'),
            React.createElement('p', null, error)
          );
        }
        
        return React.createElement('div', { className: 'broker-dashboard-content' },
          React.createElement('div', { className: 'property-list' },
            React.createElement('h2', null, 'Meus Imóveis'),
            properties.map(property => 
              React.createElement('div', { key: property.id, className: 'property-item' },
                React.createElement('div', { className: 'property-thumbnail' },
                  property.featured_image ? 
                    React.createElement('img', { src: property.featured_image, alt: property.title }) :
                    React.createElement('div', { className: 'no-thumbnail' }, 'Sem imagem')
                ),
                React.createElement('div', { className: 'property-details' },
                  React.createElement('h3', { className: 'property-title' }, property.title),
                  React.createElement('div', { className: 'property-meta' },
                    React.createElement('span', null, `Preço: R$ ${property.price}`),
                    React.createElement('span', null, `Visualizações: ${property.views}`),
                    React.createElement('span', null, `Status: ${property.status}`)
                  )
                ),
                React.createElement('div', { className: 'property-actions' },
                  React.createElement('a', { 
                    href: property.edit_link,
                    className: 'action-button edit-button',
                    title: 'Editar'
                  }, React.createElement('i', { className: 'fas fa-edit' })),
                  property.sponsored ?
                    React.createElement('button', {
                      className: 'action-button pause-highlight-button',
                      title: 'Pausar Destaque',
                      onClick: () => handlePauseHighlight(property.id)
                    }, React.createElement('i', { className: 'fas fa-pause' })) :
                    React.createElement('a', {
                      href: `/corretores/destacar-imovel/?immobile_id=${property.id}`,
                      className: 'action-button highlight-button',
                      title: 'Destacar'
                    }, React.createElement('i', { className: 'fas fa-star' })),
                  React.createElement('button', {
                    className: 'action-button delete-button',
                    title: 'Excluir',
                    onClick: () => handleDeleteProperty(property.id)
                  }, React.createElement('i', { className: 'fas fa-trash' }))
                )
              )
            )
          )
        );
      };
      
      // Renderizar o componente React
      const container = document.getElementById('react-broker-dashboard');
      if (container) {
        console.log('🚀 Iniciando renderização do React');
        ReactDOM.render(React.createElement(BrokerDashboard), container);
      } else {
        console.error('❌ Contêiner React não encontrado');
      }
      
    } catch (err) {
      console.error('❌ Erro ao inicializar o dashboard:', err);
      
      // Tentar renderizar apenas o gráfico em caso de erro
      renderChartWithoutReact();
    }
  });
})();