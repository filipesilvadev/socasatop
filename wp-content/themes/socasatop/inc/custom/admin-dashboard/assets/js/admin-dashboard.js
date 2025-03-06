document.addEventListener('DOMContentLoaded', function() {
  let chartInstance = null;

  const createMetricCard = (title, value) => {
    return React.createElement('div', {
        className: 'bg-white rounded-lg shadow p-6'
    }, [
        React.createElement('h3', {
            className: 'text-lg font-semibold mb-2'
        }, title),
        React.createElement('p', {
            className: 'text-3xl font-bold'
        }, value)
    ]);
};

  const AdminDashboard = () => {
      console.group('AdminDashboard Initialization');
      const [metrics, setMetrics] = React.useState(null);
      const [selectedBroker, setSelectedBroker] = React.useState('0');
      const [dateRange, setDateRange] = React.useState({
          start: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
          end: new Date().toISOString().split('T')[0]
      });

      console.log('Initial State:', { selectedBroker, dateRange });
      console.groupEnd();

      const loadMetrics = () => {
          console.group('Loading Metrics');
          console.log('Request Parameters:', {
              broker_id: selectedBroker,
              start_date: dateRange.start,
              end_date: dateRange.end
          });

          jQuery.ajax({
              url: siteAdmin.ajax_url,
              method: 'POST',
              data: {
                  action: 'get_admin_metrics',
                  nonce: siteAdmin.nonce,
                  broker_id: selectedBroker,
                  start_date: dateRange.start,
                  end_date: dateRange.end
              },
              success: function(response) {
                  console.log('API Response:', response);
                  if (response.success) {
                      setMetrics(response.data);
                      renderChart(response.data.daily_metrics);
                  }
              },
              error: function(error) {
                  console.error('API Error:', error);
              }
          });
          console.groupEnd();
      };

      React.useEffect(loadMetrics, [selectedBroker, dateRange]);

      const renderChart = (dailyMetrics) => {
          console.group('Renderizando Gráfico');
          const ctx = document.getElementById('metricsChart');
          if (!ctx) {
              console.error('Elemento do gráfico não encontrado');
              console.groupEnd();
              return;
          }
      
          console.log('Dados para o gráfico:', dailyMetrics);
      
          if (chartInstance) {
              chartInstance.destroy();
          }
      
          chartInstance = new Chart(ctx, {
              type: 'line',
              data: {
                  labels: dailyMetrics.map(d => d.date),
                  datasets: [
                      {
                          label: 'Visualizações',
                          data: dailyMetrics.map(d => parseInt(d.views)),
                          borderColor: 'rgb(75, 192, 192)',
                          tension: 0.1
                      },
                      {
                          label: 'Leads',
                          data: dailyMetrics.map(d => parseInt(d.leads)),
                          borderColor: 'rgb(255, 99, 132)',
                          tension: 0.1
                      },
                      {
                          label: 'Pesquisas',
                          data: dailyMetrics.map(d => parseInt(d.searches)),
                          borderColor: 'rgb(54, 162, 235)',
                          tension: 0.1
                      },
                      {
                          label: 'Corretores',
                          data: dailyMetrics.map(d => parseInt(d.brokers)),
                          borderColor: 'rgb(153, 102, 255)',
                          tension: 0.1
                      },
                      {
                          label: 'Patrocinados',
                          data: dailyMetrics.map(d => parseInt(d.sponsored)),
                          borderColor: 'rgb(255, 159, 64)',
                          tension: 0.1
                      }
                  ]
              },
              options: {
                  responsive: true,
                  interaction: {
                      intersect: false,
                  },
                  scales: {
                      y: {
                          beginAtZero: true
                      }
                  }
              }
          });
          console.log('Gráfico renderizado');
          console.groupEnd();
      };
      
      if (!metrics) {
          console.log('Carregando dados...');
          return React.createElement('div', {
              className: 'flex justify-center items-center min-h-screen'
          }, 'Carregando...');
      }
      
      console.log('Renderizando dashboard com métricas:', metrics);
      return React.createElement('div', {
          className: 'container mx-auto px-4'
      }, [
          React.createElement('div', {
              className: 'mb-8 flex items-center space-x-4'
          }, [
              React.createElement('select', {
                  className: 'p-2 border rounded flex-1',
                  value: selectedBroker,
                  onChange: (e) => setSelectedBroker(e.target.value)
              }, [
                  React.createElement('option', { value: '0' }, 'Todos os Corretores'),
                  ...metrics.brokers.map(broker =>
                      React.createElement('option', { value: broker.id, key: broker.id }, broker.name)
                  )
              ]),
              React.createElement('input', {
                  type: 'date',
                  className: 'p-2 border rounded flex-1',
                  value: dateRange.start,
                  onChange: (e) => setDateRange({ ...dateRange, start: e.target.value })
              }),
              React.createElement('input', {
                  type: 'date',
                  className: 'p-2 border rounded flex-1',
                  value: dateRange.end,
                  onChange: (e) => setDateRange({ ...dateRange, end: e.target.value })
              })
          ]),
          React.createElement('div', {
              className: 'bg-white p-6 rounded-lg shadow mb-8'
          }, [
              React.createElement('canvas', {
                  id: 'metricsChart',
                  style: { width: '100%', height: '300px' }
              })
          ]),
          React.createElement('div', {
              className: 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6'
          }, [
              createMetricCard('Corretores Ativos', metrics.active_brokers),
              createMetricCard('Leads Captados', metrics.total_leads),
              createMetricCard('Pesquisas Realizadas', metrics.total_searches),
              createMetricCard('Imóveis Visualizados', metrics.total_views),
              createMetricCard('Imóveis Patrocinados', metrics.sponsored_properties)
          ])
      ]);
  };

  const container = document.getElementById('admin-dashboard-root');
  if (container) {
      console.log('Container encontrado, iniciando renderização');
      ReactDOM.render(React.createElement(AdminDashboard), container);
  } else {
      console.error('Container não encontrado');
  }
});