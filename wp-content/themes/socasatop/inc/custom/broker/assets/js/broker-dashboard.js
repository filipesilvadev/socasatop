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
      
      // Selecionar todos os imóveis
      jQuery('#select-all-properties').on('change', function() {
        const isChecked = jQuery(this).prop('checked');
        jQuery('.property-checkbox').prop('checked', isChecked);
        
        // Mostrar ou ocultar ações em massa
        if (isChecked && jQuery('.property-checkbox:checked').length > 0) {
          jQuery('.bulk-actions').show();
        } else {
          jQuery('.bulk-actions').hide();
        }
      });
      
      // Selecionar imóvel individual
      jQuery(document).on('change', '.property-checkbox', function() {
        const anyChecked = jQuery('.property-checkbox:checked').length > 0;
        jQuery('.bulk-actions').toggle(anyChecked);
        
        // Atualizar checkbox "selecionar todos"
        const allChecked = jQuery('.property-checkbox:checked').length === jQuery('.property-checkbox').length;
        jQuery('#select-all-properties').prop('checked', allChecked);
      });
      
      // Exclusão em massa
      jQuery('#bulk-delete-btn').on('click', function() {
        const selectedIds = [];
        jQuery('.property-checkbox:checked').each(function() {
          selectedIds.push(jQuery(this).data('id'));
        });
        
        if (selectedIds.length === 0) {
          alert('Selecione pelo menos um imóvel para excluir.');
          return;
        }
        
        if (confirm(`Tem certeza que deseja excluir ${selectedIds.length} imóvel(is)?`)) {
          // Implementar lógica de exclusão em massa
          console.log('🗑️ Excluir imóveis:', selectedIds);
        }
      });
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