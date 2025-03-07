(() => {
  document.addEventListener('DOMContentLoaded', () => {
    console.log('🔍 Broker Dashboard: DOMContentLoaded');
    console.log('✅ Chart.js disponível:', typeof Chart !== 'undefined');
    console.log('✅ React disponível:', typeof React !== 'undefined');
    console.log('✅ ReactDOM disponível:', typeof ReactDOM !== 'undefined');

    const { useState, useEffect } = React;

    const BrokerDashboard = () => {
      const [metrics, setMetrics] = useState([]);
      const [properties, setProperties] = useState([]);
      const [selectedProperties, setSelectedProperties] = useState([]);
      const [loading, setLoading] = useState(true);
      const [chartData, setChartData] = useState(null);

      const createChartData = (metricsData) => {
        return {
          labels: metricsData.map(m => m.date),
          datasets: [
            {
              label: 'Exibições',
              data: metricsData.map(m => m.views),
              borderColor: 'rgb(75, 192, 192)',
              tension: 0.1
            },
            {
              label: 'Acessos',
              data: metricsData.map(m => m.clicks),
              borderColor: 'rgb(54, 162, 235)',
              tension: 0.1
            },
            {
              label: 'Conversões',
              data: metricsData.map(m => m.conversions),
              borderColor: 'rgb(255, 99, 132)',
              tension: 0.1
            }
          ]
        };
      };

      const fetchMetrics = async () => {
        try {
          const response = await fetch(`${site.ajax_url}`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=get_broker_metrics&nonce=${site.nonce}`
          });
          const data = await response.json();
          if (data.success) {
            setMetrics(data.data.metrics);
            setChartData(createChartData(data.data.metrics));
          }
        } catch (error) {
          console.error('Erro ao carregar métricas:', error);
        }
      };

      const fetchProperties = async () => {
        try {
          const response = await fetch(`${site.ajax_url}`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=get_broker_properties&nonce=${site.nonce}`
          });
          const data = await response.json();
          if (data.success) {
            setProperties(data.data.properties);
          }
          setLoading(false);
        } catch (error) {
          console.error('Erro ao carregar imóveis:', error);
          setLoading(false);
        }
      };

      useEffect(() => {
        fetchMetrics();
        fetchProperties();
      }, []);

      useEffect(() => {
        if (chartData) {
          const timer = setTimeout(() => {
            const chartCanvas = document.getElementById('broker-metrics-chart');
            
            if (chartCanvas) {
              const ctx = chartCanvas.getContext('2d');
              new Chart(ctx, {
                type: 'line',
                data: chartData,
                options: {
                  responsive: true,
                  maintainAspectRatio: false,
                  scales: {
                    y: {
                      beginAtZero: true
                    }
                  }
                }
              });
            } else {
              console.error('Elemento do canvas não encontrado');
            }
          }, 100);

          return () => clearTimeout(timer);
        }
      }, [chartData]);

      const handlePropertySelect = (propertyId) => {
        setSelectedProperties(prev => 
          prev.includes(propertyId) 
            ? prev.filter(id => id !== propertyId)
            : [...prev, propertyId]
        );
      };

      const handleCheckout = () => {
        if (selectedProperties.length === 0) {
          alert('Selecione pelo menos um imóvel para patrocinar');
          return;
        }
        window.location.href = `/checkout?properties=${selectedProperties.join(',')}`;
      };

      if (loading) {
        return React.createElement('div', { className: 'text-center p-8' }, 'Carregando...');
      }

      return React.createElement('div', { className: 'w-full max-w-6xl mx-auto p-4' }, [
        chartData && React.createElement('div', { 
          className: 'mb-8 bg-white rounded-lg shadow p-4',
          key: 'metrics'
        }, [
          React.createElement('h2', { className: 'text-xl font-bold mb-4' }, 'Métricas de Desempenho'),
          React.createElement('div', { 
            className: 'relative h-96',
            key: 'chart-container'
          }, [
            React.createElement('canvas', {
              id: 'broker-metrics-chart',
              key: 'chart'
            })
          ]),
          React.createElement('div', { className: 'grid grid-cols-3 gap-4 mt-4' }, [
            React.createElement('div', { className: 'p-4 bg-blue-100 rounded' }, [
              React.createElement('h4', { className: 'font-bold' }, 'Total de Exibições'),
              React.createElement('p', { className: 'text-2xl' }, 
                metrics.reduce((sum, m) => sum + m.views, 0)
              )
            ]),
            React.createElement('div', { className: 'p-4 bg-green-100 rounded' }, [
              React.createElement('h4', { className: 'font-bold' }, 'Total de Acessos'),
              React.createElement('p', { className: 'text-2xl' }, 
                metrics.reduce((sum, m) => sum + m.clicks, 0)
              )
            ]),
            React.createElement('div', { className: 'p-4 bg-purple-100 rounded' }, [
              React.createElement('h4', { className: 'font-bold' }, 'Total de Conversões'),
              React.createElement('p', { className: 'text-2xl' }, 
                metrics.reduce((sum, m) => sum + m.conversions, 0)
              )
            ])
          ])
        ]),

        React.createElement('div', { 
          className: 'bg-white rounded-lg shadow p-4',
          key: 'properties'
        }, [
          React.createElement('div', { 
            className: 'flex justify-between items-center mb-4',
            key: 'header'
          }, [
            React.createElement('h2', { className: 'text-xl font-bold' }, 'Meus Imóveis'),
            React.createElement('button', {
              onClick: handleCheckout,
              disabled: selectedProperties.length === 0,
              className: 'px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-400'
            }, `Patrocinar ${selectedProperties.length > 0 ? `(${selectedProperties.length})` : ''}`)
          ]),
          React.createElement('div', { 
            className: 'grid gap-4',
            key: 'list'
          }, properties.map(property => 
            React.createElement('div', {
              key: property.id,
              className: 'flex items-center justify-between p-4 border rounded-lg'
            }, [
              React.createElement('div', { className: 'flex-1' }, [
                React.createElement('h3', { className: 'font-semibold' }, property.title),
                React.createElement('div', { className: 'grid grid-cols-3 gap-4 mt-2 text-sm text-gray-600' }, [
                  React.createElement('div', null, `Exibições: ${property.views}`),
                  React.createElement('div', null, `Acessos: ${property.clicks}`),
                  React.createElement('div', null, `Conversões: ${property.conversions}`)
                ])
              ]),
              React.createElement('div', { className: 'flex items-center gap-4' }, [
                property.sponsored && React.createElement('span', {
                  className: 'px-2 py-1 bg-green-100 text-green-800 rounded-full text-sm'
                }, 'Patrocinado'),
                React.createElement('input', {
                  type: 'checkbox',
                  checked: selectedProperties.includes(property.id),
                  onChange: () => handlePropertySelect(property.id),
                  className: 'w-5 h-5 text-blue-600'
                })
              ])
            ])
          ))
        ])
      ]);
    };

    const container = document.getElementById('broker-dashboard-root');
    if (container) {
      ReactDOM.render(React.createElement(BrokerDashboard), container);
    }
  });
})();